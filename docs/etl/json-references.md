# XDMoD ETL Support For RFC-6901 JSON References

Reference handlers in JSON files will allow us to reference and include entities defined in external
files into the current file, supporting re-use. This work is largely based on the JSON schema with
some modifications to support entities defined outside of the current URI base. This is similar to
the way that JSON schema works, where the reference to `address` gets replaced by the contents of
the `address` definition (or properties in the case of the schema).

References:

- [Structuring a complex schema](https://spacetelescope.github.io/understanding-json-schema/structuring.html)
- [RFC-6901 JSON Pointer](https://tools.ietf.org/html/rfc6901)
- [IETF Draft JSON Reference](https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03)
- [IETF Draft JSON Schema](http://json-schema.org/latest/json-schema-core.html#rfc.section.7)
- [RFC-3896 URI](https://tools.ietf.org/html/rfc3986)
- [JSONLint](http://jsonlint.com/)

## JSON References

A reference follows the format specified in
[IETF Draft JSON Reference](https://tools.ietf.org/html/draft-pbryan-zyp-json-ref-03). A JSON
Reference is a JSON object containing a **single** key "$ref", which has a JSON string value that is
an [RFC-3896](https://tools.ietf.org/html/rfc3986) URI identifying the location of the JSON value
being referenced. If the URI does not specify a scheme, `file` is assumed.  Resolution of a JSON
Reference object **SHOULD** yield the referenced JSON value.  E.g., The entire object `{ "$ref":
"http://example.com/example.json#/foo/bar" }` gets logically replaced with the thing that it points
to.

For example:

- `{ "$ref": "table.json" }` includes the entire document defined in `table.json`
- `{ "$ref": "table.json#/foo/0" }` includes the the key "0" under "foo" if "foo" is an object or
  the first array element if "foo" is an array.
- `{ "$ref": "#/foo" }` is a reference to the key "foo" in the current document
- `{ "$ref": "http://example.com/example.json#/foo" }` is a reference to the key "foo" in the
  document found at http://example.com/example.json
- `{ "$ref": "example.json#/foo/bar" }` is a reference to the key "bar" under the key "foo" in the
  document `example.json` in the current (relative) location.
- `{ "$ref": "file:///path/to/example.json#/foo/bar" }` is a reference to the key "bar" under the
  key "foo" in the document `/path/to/example.json`.

Given the following two files:

`etl_tables.d/jobs/job_record.json`

```json
{
    "table_definition": {
        "name": "job_records",
        "engine": "MyISAM",
        "comment": "Request for resources by a user",
        "columns": [ ... ]
    },
    "source_query": {
        ...
    }
}
```

`etl_tables.d/jobs/job_task.json`

```json
{
    "table_definition": {
        "name": "job_tasks",
        "engine": "MyISAM",
        "comment": "Consumption for resources by a user",
        "columns": [ ... ]
    }
}
```

The references in this JSON

```json
{
    "table_definition": {
        "job_record": { "$ref": "etl_tables.d/jobs/job_record.json#/table_definition" },
        "job_task": { "$ref": "etl_tables.d/jobs/job_task.json#/table_definition" },
        "mumbo_jumbo": { "$ref": "etl_tables.d/jobs/job_task.json" }
    }
}
```

resolve to

```json
{
    "table_definition": {
        "job_record": {
            "name": "job_records",
            "engine": "MyISAM",
            "comment": "Request for resources by a user",
            "columns": [ ... ]
        },
        "job_task": {
            "name": "job_tasks",
            "engine": "MyISAM",
            "comment": "Consumption for resources by a user",
            "columns": [ ... ]
        },
        "mumbo_jumbo": {
            "table_definition": {
                "name": "job_tasks",
                "engine": "MyISAM",
                "comment": "Consumption for resources by a user",
                "columns": [ ... ]
            }
        }
    }
}
```

### Paths In JSON References

The URI (e.g., path) in JSON references can be specified in several ways. In addition, the XDMoD ETL
provides support for using macros within the URI.

- A fully qualified path URI including a scheme: `{ "$ref": "file:///fully/qualified/path.json" }`

- A relative path: `{ "$ref": "relative/path.json" }`. The path will be relative to the ETL
  configuration base directory, which will be added to the beginning of the path and the `file`
  scheme is assumed.

- A fully qualified path that includes a macro: `{ "$ref": "file://${MACRO}/some/path.json" }`. The
  macro must be a valid path macro defined by the ETL instractucture such as
  `${DEFINITION_FILE_DIR}` or `${DATA_DIR}` and it will be replaced by the value that it
  represents. **Note that this is only evailable for the `EtlConfiguration` class since the more
  general `Configuration` class does not know about paths.**

## Implementation

The `Configuration` class parses JSON files and uses the keys at the root level as the names of
configuration sections. To provide generic support for features such as JSON references and the
removal of comments, an implementation that recirsively traverses the document keys and defines
transformers to process matching keys will be used.

1. A "key transformer" must implement the `iConfigFileKeyTransformer` interface and supports
   transformation of a key that matches a string or a pattern. Multiple transformers could be
   defined for the same key.
2. Attach transformers to the a `Configuration` object. Transformers will be processed in the order
   that they are added.
3. After parsing the JSON file, traverse the keys checking all transformers against each key.
   3a. If the transformer matches the key, process the value for that key.
   3b. The key and/or value may be replaced by those returned by the transformer. Note that both
       keys and values may be altered and may be set to `null`.
            
       - If the returned key and value are both `null`, remove the key/value pair from the JSON.
       - If the key is `null` but the value is set, replace the **entire object** containing the
         key/value pair with the resulting value.
       - If the key and value are both set replace them both (either may be modified).
            
   3c. If a transformer returns `false` do not process any other transformers.
   3d. Recursively traverse keys in the returned JSON and apply transformers to the result.

### Transformer Interface Definition

Transformers must implement the `iConfigFileKeyTransformer` interface.

```php
interface iConfigFileKeyTransformer {
    /* ------------------------------------------------------------------------------------------
     * Return TRUE if the key is supported by this transformer
     *
     * @param string $key Key to check
     *
     * @return TRUE if they key is processed by this transformer
     * ------------------------------------------------------------------------------------------
     */

    public function keyMatches($key);

    /* ------------------------------------------------------------------------------------------
     * Transform the data. Both the key and the value may be modified and will be returned
     * by reference.
     *
     * @param string $key Reference to the key, may be altered.
     * @param mixed $value Reference to the value (scalar, object, array), may be altered.
     * @param stdClass $obj The object where the key was found.
     * @param Configuration $config The Configuration object that called this method.
     *
     * @return FALSE if transfomer processing should stop for this key, TRUE otherwise.
     * ------------------------------------------------------------------------------------------
     */

    public function transform(&$key, &$value, stdClass $obj, Configuration $config);
}
```

### Comment Transformer

The comment transformer strips comments from the JSON. Any key starting with a `#` is considered a
comment. Comment keys and their values are removed from the JSON file altogether and no further
processing is performed. Both the key and value sent to `processKeyValuePair()` are set to `null`
and `false` is returned.

For example, the comment below would be matched and sent to `processKeyValuePair()`. The transformer
would set `$key = null` and `$value = null` and return `false`. The matched key/value pair would be
removed from the JSON and the transformer would stop for that key.

```json
{
    "# comment": {
        "name": "Joe"
    }
}
```
### JSON Reference Transformer

The reference transformer interprets the value of any object containing a `$ref` key as JSON
reference and replaces the entire object containing the `$ref` with the contents of the evaluated
reference. It will also ensure that the value URI was not already parsed in a parent to avoid
recursion. Set the key to `null` and set the value to the value of the reference.

**Note that a reference must be in an object by itself with no other keys as per the RFC and the
entire object is considered to be the reference.**

**Reference URIs that do not contain a schema default to the `file` scheme.**

For example, given the definition of `job_tasks.json` and `config.json` below, the following result
would be generated. Notice that the entire value of the reference `"$ref":
"etl_tables.d/jobs/job_task.json#/table_definition"` is substituted for the reference.

job\_task.json:

```json
{
    "table_definition": {
        "name": "job_tasks",
        "engine": "MyISAM",
        "comment": "Consumption for resources by a user",
        "columns": [ ... ]
    }
}
```

config.json:

```json
{
    "table_definition": {
        "job_task": { "$ref": "etl_tables.d/jobs/job_task.json#/table_definition" },
        "mumbo_jumbo": { "$ref": "etl_tables.d/jobs/job_task.json" }
    }
}
```

Result:

```json
"table_definition": {
    "job_task": {
        "name": "job_tasks",
        "engine": "MyISAM",
        "comment": "Consumption for resources by a user",
        "columns": [ ... ]
    },
    "mumbo_jumbo": {
        "table_definition": {
            "name": "job_tasks",
            "engine": "MyISAM",
            "comment": "Consumption for resources by a user",
            "columns": [ ... ]
        }
    }
}
```

### JSON Reference Transformer With Macro Support

The `EtlConfiguration` class supports a "paths" key that defines a number of paths for local
directories used by the XDMoD ETL process. For example, SQL files, macros, table definitions, and
action definitions. It is useful to be able to use these values in a reference so this transformer
provides that support.

For example,

job\_task.json:

```json
{
    "table_definition": {
        "name": "job_tasks",
        "engine": "MyISAM",
        "comment": "Consumption for resources by a user"
    }
}
```

config.json:

```json
{
    "table_definition": {
        "job_task": { "$ref": "${definition_file_dir}/jobs/job_task.json#/table_definition" }
    }
}
```

Result:

```json
{
    "table_definition": {
        "job_task": {
            "name": "job_tasks",
            "engine": "MyISAM",
            "comment": "Consumption for resources by a user"
        }
    }
}
```
