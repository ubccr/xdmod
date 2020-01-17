# Integrations

Open XDMoD can be integrated with other applications such as [Open OnDemand](https://openondemand.org/)

To enable integration, the Open XDMoD instance must be configured to give explicit permission to the browser.
The Open XDMoD application settings to enabled this are described below.

## Open XDMoD Settings

### Cross-Origin Resource Sharing (CORS)
To allow CORS set a list of domains that are allowed to communicate with Open XDMoD via
the `domains` setting in the `cors` section of `portal_settings.ini`.

This is a comma separated list of both the scheme and authority
```
[cors]
domains=https://integratedapp.example.tld,https://dev-integratedapp.example.tld:8080
```

**NOTE: This setting can open up Open XDMoD to security risks if used improperly.
Only enable it if you know what you are doing**

## Integration Guide

### Single Sign-on (SSO) Embedding
Open XDMoD allows for single sign on embedding so that end users do not have to explicitly sign
in to XDMoD after they have already signed in to the other integrated application.
When [SSO](simpleSAMLphp.html) is configured with the same IdP XDMoD can be embedded in an
iframe at the login endpoint and iusers will be automatically logged in.

When Open XDMoD detects a login within an iframe it will send a `postMessage` to `window.top`

The following is an example of how to handle this

```javascript
window.addEventListener("message", receiveMessage, false);

function receiveMessage(event) {
    if (event.origin !== "{FQDN OF XDMOD INSTANCE WITH PROTOCOL AND PORT IF NEEDED}"){
        console.log('Received message from untrusted origin, discarding');
        return;
    }
    if(event.data.application == 'xdmod'){
        if(event.data.message == 'loginComplete'){
            console.log('XDMoD has logged in successfully');
        }
        if(event.data.message == 'error'){
            console.log('ERROR: ' + event.data.info);
        }
    }
}
```
