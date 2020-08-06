# Open XDMoD Module Assets

An Open XDMoD module may contain JavaScript and CSS assets that will be
included in the portal or internal (admin) dashboard.

The `portal` JavaScript and CSS paths must be relative to the `html` directory
and the `internal_dashboard` paths must be relative to the
`html/internal_dashboard` directory.

For a module named `example` create a file named `example.json` in the
`assets.d` directory.  e.g.:

```json
{
    "example": {
        "portal": {
            "js": [
                "gui/js/modules/Example.js",
                "gui/js/modules/example/ExamplePanel.js"
            ],
            "css": [
                "gui/css/example.css"
            ]
        },
        "internal_dashboard": {
            "js": [
                "js/Example/ExampleAdmin.js",
                "../gui/js/modules/example/ExamplePanel.js"
            ],
            "css": [
                "css/example.css",
                "../gui/css/example.css"
            ]
        }
    }
}
```

This example includes the following JavaScript and CSS files in the portal:

- `html/gui/js/modules/Example.js`
- `html/gui/js/modules/example/ExamplePanel.js`
- `html/gui/css/example.css`

And the following files in the internal dashboard:

- `html/internal_dashboard/js/Example/ExampleAdmin.js`
- `html/gui/js/modules/example/ExamplePanel.js`
- `html/internal_dashboard/css/example.css`
- `html/gui/css/example.css`
