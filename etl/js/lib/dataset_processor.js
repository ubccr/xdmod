/*node.js javascript document
 *
 * @authors: Amin Ghadersohi
 * @date: 3/3/2014
 *
 * Class to encapsulate functionality related to processing a dataset
 * as specified in an etl profile
 *
 * @requirements:
 *	node.js
 *
 */
var DatasetMap = require('./dataset_map.js');
var events = require('events');
var stream = require('stream');
var util = require('util');
var mysql = require('mysql');
var MongoClient = require('mongodb').MongoClient;
var metricErrors = require('./metric_errors.js');
var ce = require('cloneextend');
var defaultMaxProcessing = 2;
var defaultMaxMaxProcessing = 4;
var defaultPoolConnectionMax = 4;
var printDetailPer = 1000;
var volatileCacheSize = 1000000;
var cacheCheckPer = volatileCacheSize / 10;
var __debug = false;

var queryFormat = require('./mysql.js').queryFormat;

/**
 * Decodes the URL-encoded keys of an object and its descendants.
 * This function will modify the given object.
 *
 * @param obj The object with keys to decode.
 */
var decodeURLEncodedObjectKeys = function (obj) {
    if ((obj === undefined) || (obj === null)) {
        return;
    }

    if (Array.isArray(obj)) {
        obj.forEach(function (objElement) {
            decodeURLEncodedObjectKeys(objElement);
        });
    } else if (typeof (obj) === "object") {
        var objKeys = Object.keys(obj);
        objKeys.forEach(function (objKey) {
            var objValue = obj[objKey];

            var unescapedKey = decodeURIComponent(objKey);
            if (unescapedKey !== objKey) {
                obj[unescapedKey] = objValue;
                delete obj[objKey];
            }

            decodeURLEncodedObjectKeys(objValue);
        });
    }
};

/**
 * Stream that transforms objects with URL-encoded keys into objects with
 * decoded keys. This stream will modify the received objects, and conversion
 * will be done on all objects contained within received objects.
 */
var URLEncodedObjectKeysDecoderStream = function () {
    URLEncodedObjectKeysDecoderStream.super_.call(this, {
        objectMode: true
    });
};
util.inherits(URLEncodedObjectKeysDecoderStream, stream.Transform);

URLEncodedObjectKeysDecoderStream.prototype._transform = function (chunk, encoding, done) {
    decodeURLEncodedObjectKeys(chunk);
    this.push(chunk);
    done();
};

function typeOf(value) {
    var s = typeof value;
    if (s === 'object') {
        if (value) {
            if (typeof value.length === 'number' && !(value.propertyIsEnumerable('length')) && typeof value.splice === 'function') {
                s = 'array';
            }
        } else {
            s = 'null';
        }
    }
    return s;
};

function estimateSizeOfObject(value, level) {
    if (undefined === level)
        level = 0;

    var bytes = 0;

    if ('boolean' === typeOf(value))
        bytes = 4;
    else if ('string' === typeOf(value))
        bytes = value.length * 2;
    else if ('number' === typeOf(value))
        bytes = 8;
    else if ('object' === typeOf(value) || 'array' === typeOf(value)) {
        for (var i in value) {
            bytes += i.length * 2;
            bytes += 8; // an assumed existence overhead
            bytes += estimateSizeOfObject(value[i], 1)
        }
    }
    return bytes;
};

function formatByteSize(bytes) {
    if (bytes < 1024)
        return bytes + " bytes";
    else {
        var floatNum = bytes / 1024;
        return floatNum.toFixed(2) + " kb";
    }
};

var DatasetProcessor = module.exports = function (etlProfile, dataset, markProcessedRecords, poolConnectionMax) {
    events.EventEmitter.call(this);
    var self = this;
    this.stats = DatasetProcessor.initStats();

    this._processEnded = false;
    this.config = {
        maxProcessing: defaultMaxProcessing,
        poolConnectionMax: poolConnectionMax ? poolConnectionMax : defaultPoolConnectionMax,
        markProcessedRecords: markProcessedRecords
    };
    this.etlProfile = etlProfile;
    this.dataset = dataset;
    this.etlDetails = {};
    if (this.etlProfile.schema.etlLogging && this.etlProfile.schema.etlLogging.init) {
        this.etlProfile.schema.etlLogging.init.call(this.etlDetails);
    }
    try {
        this.datasetMap = new DatasetMap(this.etlProfile, dataset);
    } catch (exception) {
        throw exception;
    }
    //listen to message and error events that might happen during 
    // any of the resource mapping etlLogging.
    this.datasetMap.on('message', function (msg) {
        self.emit('message', self.dataset.name + ': ' + msg);
    });
    this.datasetMap.on('error', function (error) {
        self.emit('error', self.dataset.name + ': ' + error);
    });
    this.tables = this.datasetMap.getTables();
    if (this.tables.length > 1) {
        throw Error(this.dataset.name + ': ' + 'multi table insert: todo: implement');
    } else if (this.tables.length < 1) {
        throw Error(this.dataset.name + ': ' + 'no main tables to insert');
    }
    for (var t in this.tables) {
        var table = this.tables[t];
        table.insertStatement = table.getInsertStatement(true, false, table.columns, this.etlProfile.version);
        table.errorInsertStatement = table.getErrorInsertStatement(true, false, table.columns, self.etlProfile.version);
    }
    var mysqlPoolConfig = util._extend({
        queryFormat: queryFormat,
        multipleStatements: true,
        trace: __debug,
        connectionLimit: this.config.poolConnectionMax
    }, etlProfile.output.config);
    this.mysqlPool = mysql.createPool(mysqlPoolConfig);
    this.__volatile_cache = {};
};

util.inherits(DatasetProcessor, events.EventEmitter);

DatasetProcessor.initBaseStats = function () {
    return {
        processed: 0,
        currentlyProcessing: 0,
        currentlyMarking: 0,
        good: 0,
        transformErrorCount: 0,
        transformWarningCount: 0,
        dimInsertErrorCount: 0,
        derivedErrorCount: 0,
        insertErrorCount: 0,
        poolConnectErrorCount: 0,
        rate: 0
    };
}

DatasetProcessor.addStats = function (a, b) {
    for (var x in a) {
        if (!(typeof a.x === 'function')) {
            if (x in b) {
                if (x.indexOf('min') === 0) {
                    if (a[x] === null) {
                        a[x] = b[x];
                    } else if (b[x] !== null) {
                        a[x] = Math.min(a[x], b[x]);
                    }
                } else if (x.indexOf('max') === 0) {
                    if (a[x] === null) {
                        a[x] = b[x];
                    } else if (b[x] !== null) {
                        a[x] = Math.max(a[x], b[x]);
                    }
                } else {
                    a[x] += b[x];
                }
            }
        }
    }
}

DatasetProcessor.initStats = function () {
    var now_ts = Date.now();
    return util._extend({
        start_ts: now_ts,
        _profiling: {
            total: 0,
            trans: 0,
            get_conn: 0,
            rel_conn: 0,
            is: 0,
            df: 0,
            dim_insert: 0,
            der_fields: 0,
            insert_doc: 0,
            insert_error: 0,
            rec_prep: 0,
            mark: 0
        }
    }, DatasetProcessor.initBaseStats());
}

DatasetProcessor.prototype.process = function (totalCores, coreIndex) {
    var self = this;
    var multiCore = totalCores !== undefined && coreIndex !== undefined;
    if (this.dataset.input.dbEngine == 'mongodb') {
        MongoClient.connect(
            self.dataset.input.config.uri,
            {
                useNewUrlParser: true,
                useUnifiedTopology: true
            },
            function (err, client) {
            if (err) {
                self.emit("error", self.dataset.name + ': ' + "MongoClient Open Error: " + util.inspect(err));
                self._processEnded = true;
                self.onEndProcess();
                return;
            }

            self.client = client;
            var db = client.db();

            self.emit('message', self.dataset.name + ': connected to database \'' + db.databaseName + '\'');

            //look for jobs that haven't been processed yet,
            //or processed by an older version of this script
            var query;
            try {
                query = self.dataset.input.getQuery();
            } catch (err) {
                //catch user errors in getQuery
                self.emit('error', self.dataset.name + ': ' + "Error in dataset.input.getQuery(): " + err);
                return;
            }
            if(!query) {
                self.emit('error', self.dataset.name + ': ' + "Error dataset.input.getQuery() returned empty query");
                return;
            }

            self.collection = db.collection(self.dataset.input.config.collection);

            // Ensure the query is indexed
            var indexParams = {};
            for(var q in query) {
                if(query.hasOwnProperty(q)) {
                    indexParams[q] = 1;
                }
            }
            self.collection.createIndex(indexParams, function(err, idx) {
                if(err) {
                    self.emit('error', self.dataset.name + ': ' + "Create index " + idx + " returned " + err);
                    self._processEnded = true;
                    self.onEndProcess();
                    return;
                }

                self.emit('message', self.dataset.name + ': query \'' + self.collection.collectionName + '\' ' + util.inspect(query));

                self.stream = self.collection
                    .find(query)
                    .sort(self.dataset.input.sortQuery ? self.dataset.input.sortQuery : {})
                    .stream();

                if (self.dataset.input.config.url_encoded_keys) {
                    self.stream = self.stream.pipe(new URLEncodedObjectKeysDecoderStream());
                }

                self.stream.on("data", multiCore === true ?
                    function (doc) {
                        self.stats.currentlyProcessing += 1;
                        //todo: maybe consider using a real hashing mechanism. this might just do though.
                        var re = /(\d+)/;
                        var match = re.exec(doc._id);

                        if (match && parseInt(match[0], 10) % totalCores == coreIndex) {
                            self.onDoc(doc);
                        } else {
                            self.stats.currentlyProcessing -= 1;
                        }
                    } :
                    function (doc) {
                        self.stats.currentlyProcessing += 1;
                         self.onDoc(doc);
                    }
                )
                .on("error", function (err) {
                    self.emit('error', 'Stream Error: ' + self.dataset.name + ': ' + util.inspect(err));
                    self._processEnded = true;
                    if (self.stats.currentlyProcessing === 0) {
                        self.onEndProcess();
                    }
                })
                .on("end", function () {
                    self.emit('message', 'Stream End: ' + self.dataset.name);
                    self._processEnded = true;
                    if (self.stats.currentlyProcessing === 0) {
                        self.onEndProcess();
                    }
                });
            });
        });
    } else {
        //other db engines not yet supported.
        self.emit('error', self.dataset.input.dbEngine + ' is an unsupported dbEngine: ' + util.inspect(self.dataset));
    }
};
DatasetProcessor.prototype.getProcessingDetails = function () {
    var end_ts = Date.now();
    var timeSoFar = ((end_ts - this.stats.start_ts) / 1000);
    var processingDetails = ce.cloneextend(this.stats, {
        etlProfileName: this.etlProfile.name,
        etlProfileVersion: this.etlProfile.version,
        dataset: this.dataset.name,
        t: timeSoFar,
        outputQueue: this.mysqlPool._connectionQueue ? this.mysqlPool._connectionQueue.length : undefined,
        rate: this.stats.processed / timeSoFar,
    });
    processingDetails = ce.cloneextend(processingDetails, this.config);
    processingDetails.start = new Date(processingDetails.start_ts).toLocaleString();
    return processingDetails;
}

DatasetProcessor.prototype.printProcessingDetails = function (processingDetails) {
    processingDetails.start = new Date(processingDetails.start_ts).toLocaleString();
    delete processingDetails.start_ts;
    processingDetails.t = processingDetails.t + ' s';
    processingDetails.rate = processingDetails.rate.toFixed(2) + ' recs/\s';
    var sum = 0;
    //console.log(util.inspect(processingDetails));
    for (var tt in processingDetails._profiling) {
        if (tt !== 'total') {
            if (!(processingDetails._profiling[tt] instanceof Object)) {
                sum += processingDetails._profiling[tt];
                processingDetails._profiling[tt] = processingDetails._profiling[tt] + ' (' + ((processingDetails._profiling[tt] / processingDetails._profiling['total']) * 100.0).toFixed(2) + '%)';
            } else {
                for (var st in processingDetails._profiling[tt]) {
                    processingDetails._profiling[tt][st] = processingDetails._profiling[tt][st] + ' (' + ((processingDetails._profiling[tt][st] / processingDetails._profiling['total']) * 100.0).toFixed(2) + '%)';
                }
            }
        }
    }
    processingDetails._profiling.misc = processingDetails._profiling.total - sum;
    processingDetails._profiling.misc = processingDetails._profiling.misc + ' (' + ((processingDetails._profiling.misc / processingDetails._profiling['total']) * 100.0).toFixed(2) + '%)';
    this.emit('message', util.inspect(processingDetails));
}
DatasetProcessor.prototype.onEndProcess = function () {
    if (this.stats.currentlyMarking === 0) {
        this.mysqlPool.end();
        this.emit('message', this.dataset.name + ': mysqlPool closed');
        if (this.client) {
            this.client.close(true);
            this.emit('message', this.dataset.name + ': mongo connection closed');
        }

        var pd = this.getProcessingDetails();
        var etlLogging = null;
        if (this.etlProfile.schema.etlLogging && this.etlProfile.schema.etlLogging.onEndProcess) {
            etlLogging = this.etlProfile.schema.etlLogging.onEndProcess.call(this.etlDetails, pd);
        }
        this.emit('afterprocess', pd, etlLogging);

    }
}
DatasetProcessor.prototype.onBeginDoc = function () {
    if (!this.stream) {
        throw Error(self.dataset.name + ': ' + 'DatasetProcessor::onBeginDoc called before this.stream init');
    }
    if (this.stats.currentlyProcessing >= this.config.maxProcessing) {
        this.stream.pause(); //pause until doc is processed.
    }
}

//called after doc has finished processing
DatasetProcessor.prototype.onEndDoc = function (doc, start_ts, extraInfo) {
    var self = this;
    if (!self.stream) {
        throw Error(self.dataset.name + ': ' + 'DatasetProcessor::onEndDoc called before this.stream init');
    }

    function onBeforeEnd() {
        self.stats.currentlyProcessing--;
        //release the stream 
        if (self.stats.currentlyProcessing < self.config.maxProcessing) {
            self.stream.resume();
        }
    }

    function onEnd(er) {
        if (!er) self.stats.good++;
        self.stats.processed++;
        self.stats._profiling.total += ((Date.now()) - start_ts);
        if (self.stats.currentlyProcessing === 0 && self._processEnded === true) {
            self.onEndProcess();
        }
        if (self.stats.processed % printDetailPer === 0 && self._processEnded !== true) {
            if (self.stats.processed % cacheCheckPer === 0) {
                //clean the cache if needed.
                for(var connidx in self.__volatile_cache) {
                    if(self.__volatile_cache.hasOwnProperty(connidx)) {
                        if(Object.keys(self.__volatile_cache[connidx]).length > volatileCacheSize) {
                            self.__volatile_cache[connidx] = {};
                        }
                    }
                }
            }
            //adjust buffer size, kind of like tcp window
            if (self.mysqlPool._connectionQueue.length == 0 && self.config.maxProcessing < defaultMaxMaxProcessing) {
                self.config.maxProcessing++;
            } else if (self.mysqlPool._connectionQueue.length > 0 && self.config.maxProcessing > 1) {
                self.config.maxProcessing--;
            }
            var processingDetails = self.getProcessingDetails();
            self.printProcessingDetails(processingDetails);
        }
    }
    onBeforeEnd.call(self);
    //after processing, mark the original doc
    if (self.config.markProcessedRecords) {
        process.nextTick(function () {
            var __mark_s_ts = Date.now();
            self.stats.currentlyMarking++;
            self.dataset.input.markAsProcessed(self.collection, doc._id, extraInfo, function (errr) {
                self.stats._profiling.mark += ((Date.now()) - __mark_s_ts);
                if (errr) self.emit('error', self.dataset.name + ': ' + "Error marking processed doc in mongo: " + doc._id + ' ' + errr);
                self.stats.currentlyMarking--;
                onEnd.call(self, extraInfo.errors || errr);
            });
        });
    } else {
        onEnd.call(self, extraInfo.errors);
    }
}

DatasetProcessor.prototype.onDoc = function (doc) {
    var self = this;
    var warnings = undefined;
    var __start_ts = Date.now();
    //console.log('processing ' + doc._id + ' ' + formatByteSize(estimateSizeOfObject(doc)));
    self.onBeginDoc();

    //transform the doc 
    try {
        var _t_ts = Date.now();
        var transformed = self.datasetMap.transform(doc);

        self.stats._profiling.trans += ((Date.now()) - _t_ts);
    } catch (err) {
        self.emit('error', self.dataset.name + ': ' + 'Unhandled error occurred during transform step for doc: ' + doc._id + ':\n' + util.inspect(err));
        self.stats.transformErrorCount++;
        self.onEndDoc(doc, __start_ts, {
            errors: err
        });
        return;
    }
    if (transformed.errors) {
        self.emit('error', self.dataset.name + ': ' + 'Error(s) occurred during transform step for doc: ' + doc._id + ':\n' + util.inspect(transformed.errors));
        self.stats.transformErrorCount++;
        self.onEndDoc(doc, __start_ts, {
            errors: transformed.errors
        });
        return;
    }
    if (transformed.warnings) {
        warnings = transformed.warnings;
        self.emit('error', self.dataset.name + ': ' + 'Warning(s) encountered during transform step for doc: ' + doc._id + ':\n' + util.inspect(transformed.warnings));
        self.stats.transformWarningCount++;
    }
    // self.emit('message', transformed);
    // end transform


    var __df_s_ts = Date.now();
    var derivedFields = self.etlProfile.schema.getDerivedFields(transformed, queryFormat);

    self.stats._profiling.df += ((Date.now()) - __df_s_ts);
    //get a connection for inserting this document
    var __get_conn_s_ts = Date.now();
    this.mysqlPool.getConnection(function (err, conn) {

        var MULTI_STMT_SIZE = 500;

        self.stats._profiling.get_conn += ((Date.now()) - __get_conn_s_ts);
        if (err) {
            self.emit('error', self.dataset.name + ': ' + "MySQL Pool getConnection Error: " + util.inspect(err));
            self.stats.poolConnectErrorCount++;
            self.onEndDoc(doc, __start_ts, {
                errors: err,
                warnings: warnings
            });
            return;
        }

        // Maintain a separate query cache for each connection since there is no guarantee that
        // inserts in one session are seen immediately by all other sessions.
        if( ! self.__volatile_cache.hasOwnProperty(conn.threadId) ) {
            self.__volatile_cache[conn.threadId] = {};
        }

        var __is_s_ts = Date.now();
        //insert all the auxillary dimensions first
        var insertStatementsArr = self.datasetMap.getDimensionInsertStatements(transformed, queryFormat, self.__volatile_cache[conn.threadId]);
        self.stats._profiling.is += ((Date.now()) - __is_s_ts);

        var _dim_insert_s_ts = Date.now();
        if (insertStatementsArr.length == 0) {
            afterDimInsert(undefined, undefined);
        } else if (insertStatementsArr.length > MULTI_STMT_SIZE) {
            var insertStmtIndex = 0;
            multiInsert();
        } else {
            conn.query(insertStatementsArr.join('; '), afterDimInsert);
        }

        function multiInsert(err, result) {
            if (err) {
                conn.release();
                self.emit('error', self.dataset.name + ': ' + "Dim Multi Insert Error: " + err + ': ' + util.inspect(insertStatementsArr));
                self.stats.dimInsertErrorCount++;
                self.onEndDoc(doc, __start_ts, {
                    errors: err,
                    warnings: warnings
                });
                return;
            }

            if (insertStmtIndex < insertStatementsArr.length) {
                var upper = Math.min(insertStmtIndex + MULTI_STMT_SIZE, insertStatementsArr.length);
                var next = insertStatementsArr.slice(insertStmtIndex, upper).join('; ');
                insertStmtIndex = upper;
                conn.query(next, multiInsert);
            } else {
                afterDimInsert(err, result);
            }
        };

        function afterDimInsert(err, result) {
            self.stats._profiling.dim_insert += ((Date.now()) - _dim_insert_s_ts);
            if (err) {
                conn.release();
                self.emit('error', self.dataset.name + ': ' + "Dim Insert Error: " + err + ': ' + util.inspect(insertStatementsArr));
                self.stats.dimInsertErrorCount++;
                self.onEndDoc(doc, __start_ts, {
                    errors: err,
                    warnings: warnings
                });
                return;
            }

            function endDerivedFieldsFn() {
                var _rec_prep_s_ts = Date.now();
                var values = {};
                var errors = {};
                for (var f in derivedFields) {
                    var derivedField = derivedFields[f];
                    if (derivedField.value === undefined) {
                        derivedField.value = derivedField.schemaField.def;
                    }
                    if (derivedField.value === null && derivedField.schemaField.nullable === false) {
                        var err = self.dataset.name + ': ' + util.format('Attribute "%s" cannot be null: %s', f, util.inspect(derivedField));
                        self.emit('error', err);
                        self.stats.derivedErrorCount++;
                        conn.release();
                        self.onEndDoc(doc, __start_ts, {
                            errors: err,
                            warnings: warnings
                        });
                        return;
                    }
                    values[f] = derivedField.value;
                    errors[f] = (derivedField.schemaField.nullable === false && derivedField.schemaField.def === null) ? derivedField.value : (derivedField.error ? derivedField.error : 0);
                }

                for (var t in transformed.data) {
                    values[t] = transformed.data[t].value;
                    errors[t] = (transformed.schema[t].nullable === false && transformed.schema[t].def === null) ? transformed.data[t].value : transformed.data[t].error;
                }
                self.stats._profiling.rec_prep += ((Date.now()) - _rec_prep_s_ts);
                //todo: this currently works due to only one table, 
                //make sure in case of multiple tables
                var _s_insert_ts = Date.now();
                var tables = self.tables;
                for (var t in tables) {
                    var table = tables[t];
                    var insertStatement = queryFormat(table.insertStatement, values);
                    conn.query(insertStatement, function (err, result) {
                        self.stats._profiling.insert_doc += ((Date.now()) - _s_insert_ts);
                        //todo: check for warnings						
                        if (err) {
                            self.emit('error', self.dataset.name + ': ' + err + ': insertStatement: ' + insertStatement);
                            self.stats.insertErrorCount++;
                            conn.release();
                            self.onEndDoc(doc, __start_ts, {
                                errors: err + ': ' + insertStatement,
                                warnings: warnings
                            });
                            return;
                        }
                        var _s_insert_error_ts = Date.now();
                        var _id = result.insertId;
                        if (!_id) {
                            throw Error(self.dataset.name + ': ' + 'result.insertId undefined');
                        }

                        errors['_id'] = _id;
                        var errorInsertStatement = queryFormat(table.errorInsertStatement, errors);
                        conn.query(errorInsertStatement, function (err, result) {
                            self.stats._profiling.insert_error += ((Date.now()) - _s_insert_error_ts);
                            if (err) {
                                self.emit('error', self.dataset.name + ': ' + err + ': errorInsertStatement: ' + errorInsertStatement);
                                self.stats.insertErrorCount++;
                                self.onEndDoc(doc, __start_ts, {
                                    errors: err + ': ' + errorInsertStatement,
                                    warnings: warnings
                                });
                                return;
                            }
                            var _rel_conn_s_ts = Date.now();
                            conn.release();
                            self.stats._profiling.rel_conn += ((Date.now()) - _rel_conn_s_ts);
                            var extraData = {
                                errors: undefined,
                                warnings: warnings
                            };
                            if (__debug) {
                                extraData.info = {
                                    insert: insertStatement,
                                    insertError: errorInsertStatement
                                }
                            };
                            if (self.etlProfile.schema.etlLogging && self.etlProfile.schema.etlLogging.onEndDoc) {
                                self.etlProfile.schema.etlLogging.onEndDoc.call(self.etlDetails, values);
                            }
                            self.onEndDoc(doc, __start_ts, extraData);
                        });
                    });
                }
            }

            function queryDerivedFields(derivedFieldKeys, endDerivedFieldsFn, __volatile_cache) {
                if (derivedFieldKeys.length == 0) {
                    endDerivedFieldsFn.call(self);
                    return;
                }
                var _der_fields_s_ts = Date.now();
                var key = derivedFieldKeys[0];
                var curField = derivedFields[key];
                derivedFieldKeys = derivedFieldKeys.slice(1);

                if ("value" in curField) {
                    var _delta_ts = ((Date.now()) - _der_fields_s_ts);

                    self.stats._profiling['der_fields'] += _delta_ts;
                    if (false) {
                        if (self.stats._profiling._der_fields === undefined) self.stats._profiling._der_fields = {};
                        if (self.stats._profiling._der_fields['der_fields_' + key] === undefined) self.stats._profiling._der_fields['der_fields_' + key] = 0;
                        self.stats._profiling._der_fields['der_fields_' + key] += _delta_ts;

                    }
                    queryDerivedFields(derivedFieldKeys, endDerivedFieldsFn, __volatile_cache);
                } else {
                    function onQueryResults(err, result) {
                        var _delta_ts = ((Date.now()) - _der_fields_s_ts);
                        self.stats._profiling['der_fields'] += _delta_ts;
                        if (false) {
                            if (self.stats._profiling._der_fields === undefined) self.stats._profiling._der_fields = {};
                            if (self.stats._profiling._der_fields['der_fields_' + key] === undefined) self.stats._profiling._der_fields['der_fields_' + key] = 0;
                            self.stats._profiling._der_fields['der_fields_' + key] += _delta_ts;
                        }
                        if (err) {
                            if (!("value" in curField)) {
                                curField.error |= metricErrors.codes.metricDeriveQueryError.value;
                            }
                            //set error value, which will be overridden if
                            //a subsequent query fined the value without error
                            self.emit('error', self.dataset.name + ': ' + err + ': ' + curField.query);
                        } else {
                            if(result.length == 1) {
                                if(curField.cacheable) {
                                    __volatile_cache[curField.query] = result;
                                }

                                var resKeys = Object.keys(result[0]);

                                for (var resKey in resKeys) {
                                    derivedFields[resKeys[resKey]].value = result[0][resKeys[resKey]];
                                    //reset to no error since value found
                                    derivedFields[resKeys[resKey]].error = 0;
                                }
                            } else if (result.length > 1 && __debug) {
                                self.emit('error', self.dataset.name + ': non-unique foreign key - multiple rows returned for: ' + curField.query);
                            }
                        }
                        queryDerivedFields(derivedFieldKeys, endDerivedFieldsFn, __volatile_cache);
                    }
                    if (curField.cacheable && curField.query in __volatile_cache) {
                        onQueryResults(undefined, __volatile_cache[curField.query]);
                    } else {
                        conn.query(curField.query, onQueryResults);
                    }
                }
            }

            queryDerivedFields(Object.keys(derivedFields), endDerivedFieldsFn, self.__volatile_cache[conn.threadId]);
        }
    });
}
