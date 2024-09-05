The following Python script will export your saved Metric Explorer charts. An XDMoD username and password is required to run the script.
Before running the script,
1. Install the required `python-dotenv` and `requests` dependencies.
1. Create a `.env` file in the same directory as the script that contains the following contents, replacing `<username>` with your XDMoD username and `<password>` with your XDMoD password — make sure to secure this file with read-only access.
    ```
    XDMOD_USERNAME=<username>
    XDMOD_PASSWORD=<password>
    ```
1. Update the value of `site_address` within the script with the URL associated with your XDMoD portal.
1. Update the value of `export_path` within the script with the desired path to export the images.
1. Confirm the `image_format` within the script.

The default image format is `svg`, but `png` and `pdf` formats are also supported. Refer to the XDMoD [Metric Explorer Tab Controller API](rest.html#tag/Metric-Explorer/paths/~1controllers~1metric_explorer.php/post) `get_data` operation for more information on the request body schema.

```python
#!/usr/bin/env python3
import os
import requests
import json
import urllib
import argparse
from dotenv import load_dotenv

site_address = ''
export_path = ''
image_format = 'svg'
image_width = 916
image_height = 484

load_dotenv()
username = os.getenv('XDMOD_USERNAME')
password = os.getenv('XDMOD_PASSWORD')

parser = argparse.ArgumentParser(description='Export XDMoD saved Metric Explorer charts with the REST API.')
parser.add_argument('-n', '--name',type=str, default='', help='Specify the chart name of a saved chart to export.')
args = parser.parse_args()

session = requests.Session()

auth_response = session.post(f'{site_address}/rest/auth/login', auth=(username, password))

if auth_response.status_code != 200:
    print('Authentication failed. Check provided credentials and check if you have a local XDMoD account')
    quit()

auth_response = auth_response.json()

header = {
    'Token': auth_response['results']['token'],
    'Authorization': auth_response['results']['token'],
    'Content-Type': 'application/x-www-form-urlencoded'
}

saved_charts = session.get(f'{site_address}/rest/v1/metrics/explorer/queries', headers=header)
saved_charts_data = saved_charts.json()

if args.name != '' and not any(chart_obj['name'] == args.name for chart_obj in saved_charts_data['data']):
    print('Specified chart not found.')
    exit()

for idx, chart in enumerate(saved_charts_data['data']):
    if 'config' in chart:
        chart_json = json.loads(chart['config'])
        for attribute in chart_json:
            chart_parameter = chart_json[attribute]
            if (isinstance(chart_parameter, dict)):
                if 'data' in attribute:
                    encoded_str = urllib.parse.quote_plus(str(chart_parameter['data']))
                else:
                    encoded_str = urllib.parse.quote_plus(str(chart_parameter))
                encoded_str = encoded_str.replace('%27','%22').replace('False', 'false').replace('True', 'true').replace('None', 'null')
                chart_json[attribute] = encoded_str
            if chart_parameter in (True, False, None):
                chart_json[attribute] = str(chart_parameter).replace('False', 'false').replace('True', 'true').replace('None', 'null')

        chart_json['operation'] = "get_data"
        chart_json['controller_module'] = "metric_explorer"
        chart_json['show_title'] = "y"
        chart_json['format'] = image_format
        chart_json['width'] = image_width
        chart_json['height'] = image_height

        chart_response = session.post(f'{site_address}/controllers/metric_explorer.php', data=chart_json, headers=header)
        chart_name = f"{chart['name']}.{image_format}" if ('name' in chart) else f"xdmod_API_export_{idx}.{image_format}"

        if args.name != '' and args.name == chart['name']:
            with open(export_path + chart_name, "wb") as f:
                f.write(chart_response.content)
                exit()
        else:
            with open(export_path + chart_name, "wb") as f:
                f.write(chart_response.content)
```
