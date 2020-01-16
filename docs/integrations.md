# Integrations

XDMoD can be integrated with other Applications such as [Open OnDemand](https://openondemand.org/)

## Settings

### Cross-Origin Resource Sharing (CORS)
To allow CORS set a list of domains that are allowed to communicate with XDMoD via
the `domains` setting in the `cors` section of `portal_settings.ini`.

**NOTE: This setting can open up XDMoD to security risks if used wrong.
Only enable it if you know what you are doing**

## Single Sign-on (SSO) Embedding
XDMoD allows for single sign on embedding.  When [SSO](simpleSAMLphp.html) is configured with the same IDP
XDMoD can be embedded in an iframe at the login endpoint and automatically logged in.

When XDMoD detects login within an iframe it will send a `postMessage` to `window.top`

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
