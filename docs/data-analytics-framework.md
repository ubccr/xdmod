---
title: Data Analytics Framework
---

The XDMoD Data Analytics Framework provides REST API access to the XDMoD data warehouse. Users can access the API programmatically using the [xdmod-data](https://github.com/ubccr/xdmod-data) package. To use the API, users must generate API Tokens for themselves through the portal interface.

## Configuration

The configuration settings for the Data Analytics Framework are set in the `portal_settings.ini` file.

### API Token Expiration

The `expiration_interval` setting in the `api_token` section specifies how long a token stays valid before it is automatically revoked. The value of this setting must follow [PHP relative date/time formats](https://www.php.net/manual/en/datetime.formats.relative.php).

```ini
[api_token]
expiration_interval = "6 months"
```

### Raw Data Limit

API requests for raw data are limited to a maximum number of rows per request. This is configured by the `rest_raw_row_limit` setting in the `datawarehouse` section.

```ini
[datawarehouse]
rest_raw_row_limit = "10000"
```

## API Token Generation

Users should follow the steps below to generate an API Token.

1. First, if you are not already signed in to the portal, sign in in the top-left corner:

    ![Screenshot of "Sign In" button]({{ site.baseurl }}/assets/images/api-token/sign-in.jpg)

1. Next, click the "My Profile" button in the top-right corner:

    ![Screenshot of "My Profile" button]({{ site.baseurl }}/assets/images/api-token/my-profile.jpg)

1. The "My Profile" window will appear. Click the "API Token" tab:

    ![Screenshot of "API Token" tab]({{ site.baseurl }}/assets/images/api-token/api-token-tab.jpg)

    **Note:** If the "API Token" tab does not appear, it means this instance of XDMoD is not configured for the Data Analytics Framework.

1. If you already have an existing token, delete it:

    ![Screenshot of "Delete API Token" button]({{ site.baseurl }}/assets/images/api-token/delete.jpg)

1. Click the "Generate API Token" button:

    ![Screenshot of "Generate API Token" button]({{ site.baseurl }}/assets/images/api-token/generate.jpg)

1. Copy the token to your clipboard. Make sure to paste it somewhere for saving, as you will not be able to see the token again once you close the window:

    ![Screenshot of "Copy API Token to Clipboard" button]({{ site.baseurl }}/assets/images/api-token/copy.jpg)

    **Note:** If you lose your token, simply delete it and generate a new one.
