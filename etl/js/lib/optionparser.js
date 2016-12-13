#!/usr/bin/env nodejs
/*  This file is part of Open XDMoD.
 *
 *  Open XDMoD is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Open XDMoD is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Open XDMoD.  If not, see <http://www.gnu.org/licenses/>.
 */

/* optionparser: supports gnu-style long and short options and also supports
 * multi-character short options.
 * usage:
 *
 * var op = require('optionparser')({
 *     shortopts: ['x', 'y', 'zed'],
 *     shortflags: ['a', 'b', 'cee'],
 *     longopts: ['xaxis', 'yaxis'],
 *     longflags: ['help']
 * })
 * var errorCallback = function(unknown) {
 *    // process unknown option
 * };
 * var args = op(process.argv.slice(1), errorCallback);
 *
 * The parser stops processing after the first non-option argument or after a --
 * The subsequent non option arguments are placed in args._
 * The short* and long* configuration settings indicate the option should have
 * a single or double dash respectively.  The *flags and *opts configuration
 * settings describe whether the option is a boolean flag or expects an option
 * argument.
 */
module.exports = function(config) {

    var findIndexInArray = function(list, predicate) {
        var length = list.length;
        var value;

        for (var i = 0; i < length; i++) {
            value = list[i];
            if (predicate(value, i, list)) {
                return i;
            }
        }
        return -1;
    };

    var allshortopts = config.shortopts.concat(config.shortflags);
    var alllongopts = config.longopts.concat(config.longflags);

    var shortopts = new RegExp("^(" + allshortopts.join(")=?(.*)$|^(") + ")(.*)$");
    var longopts = new RegExp("^(" + alllongopts.join(")=?(.*)$|^(") + ")(.*)$");

    var flags = {};
    var i;
    for (i = 0; i < config.shortflags.length; i++) {
        flags[config.shortflags[i]] = 1;
    }
    for (i = 0; i < config.longflags.length; i++) {
        flags[config.longflags[i]] = 1;
    }

    var states = {
        SEARCH: 1,
        OPTARG: 2,
        END_OPTIONS: 3,
        NON_OPTION: 4
    };

    return function(tokens, unknown) {

        var result = {};
        var state = states.SEARCH;
        var i;
        var option;

        var processMatches = function(mtch) {
            var index;

            if (mtch) {
                index = findIndexInArray(mtch, function(elem, arrind) { return arrind > 0 ? elem : false; });

                if (flags.hasOwnProperty(mtch[index])) {
                    state = states.SEARCH;
                    result[mtch[index]] = true;
                } else if (mtch[index + 1]) {
                    state = states.SEARCH;
                    result[mtch[index]] = mtch[index + 1];
                } else {
                    state = states.OPTARG;
                    option = mtch[index];
                }
            } else {
                if (unknown) {
                    unknown(tokens[i]);
                }
                state = states.NON_OPTION;
            }
        };

        for (i = 0; i < tokens.length; i++) {
            switch (state) {
                case states.SEARCH:
                    if (tokens[i] == '--') {
                        state = states.END_OPTIONS;
                    } else if (tokens[i].charAt(0) == '-') {
                        if (tokens[i].charAt(1) == '-') {
                            processMatches(longopts.exec(tokens[i].slice(2)));
                        } else {
                            processMatches(shortopts.exec(tokens[i].slice(1)));
                        }
                    } else {
                        state = states.NON_OPTION;
                    }
                    break;
                case states.OPTARG:
                    result[option] = tokens[i];
                    state = states.SEARCH;
                    break;
                case states.END_OPTIONS:
                    state = states.NON_OPTION;
                    break;
            }
            if (state == states.NON_OPTION) {
                result._ = tokens.slice(i);
                break;
            }
        }
        return result;
    };
};
