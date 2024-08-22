You can use the XDMoD API to image export your saved metric explorer charts. A local XDMoD account is **required** to authenticate through the API.

The following Python script will export your saved metric explorer charts. The `dotenv` library is recommended when authenticating through XDMoD API. You can install the `dotenv` library using:

`$ pip install python-dotenv`

Before running the script,

1. Create a `.env` file with your local XDMoD account credentials in the same directory as the script
1. Declare `center_url` within the script with the appropriate information
1. Confirm the `image_format` within the script.

The script will export your saved metric explorer charts to the current working directory.

```python
#!/usr/bin/env python3
import os
import requests
import json
import urllib
from dotenv import load_dotenv

load_dotenv()

username = os.getenv('XDMOD_USERNAME')
password = os.getenv('XDMOD_PASSWORD')
center_url = ""
image_format = "svg"

session = requests.Session()

auth_response = session.post(f'{center_url}/rest/auth/login', auth=(username, password))

if auth_response.status_code != 200:
    print('Authentication failed. Check provided credentials and check if you have a local XDMoD account')
    quit()

auth_response = auth_response.json()

header = {
  'Token': auth_response['results']['token'],
  'Authorization': auth_response['results']['token'],
  'Content-Type': 'application/x-www-form-urlencoded'
}

saved_charts = session.get(f'{center_url}/rest/v1/metrics/explorer/queries', headers=header, cookies=session.cookies)
saved_charts_data = saved_charts.json()

for idx, chart in enumerate(saved_charts_data['data']):
    if 'config' in chart:
        chart_json = json.loads(chart['config'])
        for attribute in chart_json:
            if (isinstance(chart_json[attribute], dict)):
                if 'data' in attribute:
                    encoded_str = urllib.parse.quote_plus(str(chart_json[attribute]['data']))
                else:
                    encoded_str = urllib.parse.quote_plus(str(chart_json[attribute]))
                encoded_str = encoded_str.replace('%27','%22').replace('False', 'false').replace('True', 'true').replace('None', 'null')
                chart_json[attribute] = encoded_str
            if chart_json[attribute] in (True, False, None):
                chart_json[attribute] = str(chart_json[attribute]).replace('False', 'false').replace('True', 'true').replace('None', 'null')

        chart_json['operation'] = "get_data"
        chart_json['controller_module'] = "metric_explorer"
        chart_json['show_title'] = "y"
        chart_json['format'] = image_format
        chart_json['width'] = 916
        chart_json['height'] = 484

        chart_response = session.post(f'{center_url}/controllers/metric_explorer.php', data=chart_json, headers=header, cookies=session.cookies)
        chart_name = f"{chart['name']}.{image_format}" if ('name' in chart) else f"xdmod_API_export_{idx}.{image_format}"

        with open(chart_name, "wb") as f:
            f.write(chart_response.content)
```

The default image format is `svg`, but `png` and `pdf` formats are also supported. Refer to the XDMoD [Metric Explorer Tab Controller API](rest.html#tag/Metric-Explorer/paths/~1controllers~1metric_explorer.php/post) `get_data` operation information on the request body schema.
