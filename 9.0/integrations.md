# Integrations

Open XDMoD can be integrated with other applications such as [Open OnDemand](https://openondemand.org/)

To enable integration, the Open XDMoD instance must be configured to give explicit permission to the browser.
The Open XDMoD application settings to enabled this are described below.

## Open XDMoD Settings

### Cross-Origin Resource Sharing (CORS)
To allow CORS  a list of domains that are allowed to communicate with Open XDMoD is configured in
the `domains` setting in the `cors` section of `portal_settings.ini`.

This setting is a comma separated list where each item matches *exactly* what is in the `Origin` header sent by the browser.
This includes the schema, host, and non standard ports.

```ini
[cors]
domains=https://integratedapp.example.tld,https://dev-integratedapp.example.tld:8080
```

**NOTE: This setting can open up Open XDMoD to security risks if used improperly.**

## Integration Guide

### Single Sign-on (SSO) Embedding
Open XDMoD allows for single sign on embedding so that end users do not have to explicitly sign
in to XDMoD after they have already signed in to the other integrated application.
When [SSO](simpleSAMLphp.html) is configured with the same IdP XDMoD can be embedded in an
iframe at the login endpoint and iusers will be automatically logged in.

When Open XDMoD detects a login within an iframe it will send a `postMessage` to `window.top`

The application that is integrating with Open XDMoD should contain a page *like* the following:

```html
<html>
<head>
    <title>Open XDMoD Integration</title>
    <script>
        /**
         * This will happen after a user has been confirmed to be logged in to Open XDMoD.
         * Modify this to do what you need.
         */
        function xdmodLoggedIn(){
            var loginStatus = document.createElement('p');
            loginStatus.innerText = 'Open XDMoD user is logged in';
            document.body.appendChild(loginStatus);
        }
        /**
         * Automatically fetch the default Open XDMoD SSO url and initialize the login
         * if the user is not already logged in.
         */
        var xdmodUrl = 'https://{FQDN OF XDMOD INSTANCE}';
        fetch(xdmodUrl + '/rest/v1/users/current', { credentials: 'include' })
            .then((response) => {
                if(!response.ok){
                    fetch(xdmodUrl + '/rest/auth/idpredirect?returnTo=%2Fgui%2Fgeneral%2Flogin.php')
                        .then(response => response.json())
                        .then((data) => {
                            var xdmodLogin = document.createElement('iframe');
                            /**
                             * Do not use display:none or it wont work in Firefox
                             */
                            xdmodLogin.style = 'visibility: hidden; position: absolute;left: -1000px';
                            xdmodLogin.src = data;
                            document.body.appendChild(xdmodLogin);
                    });
                }
                else {
                    xdmodLoggedIn();
                }
            });

        window.addEventListener("message", receiveMessage, false);

        function receiveMessage(event) {
            if (event.origin !== xdmodUrl){
                console.log('Received message from untrusted origin, discarding');
                return;
            }
            if(event.data.application == 'xdmod'){
                if(event.data.action == 'loginComplete'){
                    xdmodLoggedIn();
                }
                if(event.data.action == 'error'){
                    console.log('ERROR: ' + event.data.info);
                }
            }
        }
    </script>
</head>
<body>
</body>
</html>
```
