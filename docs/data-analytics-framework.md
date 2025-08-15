---
title: Data Analytics Framework
---

The Data Analytics Framework for XDMoD provides REST API access to the XDMoD
data warehouse. Users can access the API programmatically using the
[xdmod-data](https://github.com/ubccr/xdmod-data) package. XDMoD can be
configured to provide one-click access to a hosted JupyterLab environment with
the `xdmod-data` package pre-installed and authentication happening
automatically. Users can also authenticate outside of such an environment by
generating an API token through the XDMoD portal interface; see the "API Token
Generation" section below.

## Configuration

### API Token Expiration

The `expiration_interval` setting in the `api_token` section of
`portal_settings.ini` specifies how long an API token stays valid before it is
automatically revoked. The value of this setting must follow [PHP relative
date/time
formats](https://www.php.net/manual/en/datetime.formats.relative.php).

```ini
[api_token]
expiration_interval = "6 months"
```

## API Token Generation

Users can obtain an API token as follows.

1. First, if you are not already signed in to the portal, sign in in the
   top-left corner:

    ![Screenshot of "Sign In" button]({{ site.baseurl }}/assets/images/api-token/sign-in.jpg)

1. Next, click the "My Profile" button in the top-right corner:

    ![Screenshot of "My Profile" button]({{ site.baseurl }}/assets/images/api-token/my-profile.jpg)

1. The "My Profile" window will appear. Click the "API Token" tab:

    ![Screenshot of "API Token" tab]({{ site.baseurl }}/assets/images/api-token/api-token-tab.jpg)

    **Note:** If the "API Token" tab does not appear, it means this instance of
    XDMoD is not configured for the Data Analytics Framework.

1. If you already have an existing token, delete it:

    ![Screenshot of "Delete API Token" button]({{ site.baseurl }}/assets/images/api-token/delete.jpg))

1. Click the "Generate API Token" button:

    ![Screenshot of "Generate API Token" button]({{ site.baseurl }}/assets/images/api-token/generate.jpg))

1. Copy the token to your clipboard. Make sure to paste it somewhere secure for
   saving, as you will not be able to see the token again once you close the
   window:

    ![Screenshot of "Copy API Token to Clipboard" button]({{ site.baseurl }}/assets/images/api-token/copy.jpg))

     **Note:** If you lose your token, simply delete it and generate a new one.

## API Token Revocation

Users can revoke their own tokens through the "My Profile" window:

1. Sign in on the XDMoD portal.
1. Click the "My Profile" button.
1. Click the "API Token" tab.
1. Click "Delete API Token."
1. Click "Yes."

Admins can revoke a user's token by logging in as them and revoking their token:

1. Sign in on the XDMoD portal.
1. Click the "Admin Dashboard" button.
1. Click the "User Management" tab.
1. Click "Existing Users."
1. Search for the user in the filter box.
1. Select the user whose token you wish to revoke.
1. Click "Log In As Selected User."
1. Follow the steps above for revoking the user's token.
