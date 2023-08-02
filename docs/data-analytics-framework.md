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

### Raw Data Request Limit

REST requests for raw data are limited to a maximum number of rows per request. This is configured by the `rest_raw_row_limit` setting in the `datawarehouse` section. Note that the `xdmod-data` API is configured to make multiple requests until all rows are obtained, so this limit is transparent to the end user of the framework.

```ini
[datawarehouse]
rest_raw_row_limit = "10000"
```

## API Token Generation

Users should follow [these steps](https://github.com/ubccr/xdmod-data#api-token-access) to generate an API Token.

## API Token Revocation

Users can revoke their own tokens through the "My Profile" window:
1. Sign in on the XDMoD portal.
1. Click the "My Profile" button.
1. Click the "API Token" tab.
1. Click "Delete API Token".
1. Click "Yes".

Admins can revoke a user's token by logging in as them and revoking their token:
1. Sign in on the XDMoD portal.
1. Click the "Admin Dashboard" button.
1. Click the "User Management" tab.
1. Click "Existing Users".
1. Search for the user in the filter box.
1. Select the user whose token you wish to revoke.
1. Click "Log In As Selected User".
1. Follow the steps above for revoking the user's token.
