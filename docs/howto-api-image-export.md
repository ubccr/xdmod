You can use the XDMoD API to export your saved metric explorer charts. A local XDMoD account is required to authenticate through the API.

The following python script can be used to export the saved metric explorer charts. The `dotenv` library is recommend when authenticating through XDMoD API. You can install the `dotenv` library using:
`$ pip install python-dotenv`

The script will write the images to the current working directory. The format of the returned image can be changed, the default used here is 'svg'.

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

session = requests.Session()

auth_response = session.post('YOUR_CENTER_URL/rest/auth/login', auth=(username, password))

if auth_response.status_code != 200:
    print('Authentication failed. Check provided credentials and check if you have a local XDMoD account')
    quit()

auth_response = auth_response.json()

header = {
  'Token': auth_response['results']['token'],
  'Authorization': auth_response['results']['token'],
  'Content-Type': 'application/x-www-form-urlencoded'
}

saved_charts = session.get('YOUR_CENTER_URL/rest/v1/metrics/explorer/queries', headers=header, cookies=session.cookies)
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
        chart_json['format'] = "svg"

        chart_response = session.post('YOUR_CENTER_URL/controllers/metric_explorer.php', data=chart_json, headers=header, cookies=session.cookies)
        chart_name = f"{chart['name']}.{chart_json['format']}" if ('name' in chart) else f"xdmod_API_export_{idx}.{chart_json['format']}"

        with open(chart_name, "w") as f:
            f.write(chart_response.text)
```
