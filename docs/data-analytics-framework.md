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

Users should follow [these steps](https://github.com/ubccr/xdmod-data#api-token-access) to generate an API Token.
