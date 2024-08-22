The following Python script can be used to export your saved Metric Explorer charts to image files using the XDMoD REST API. An XDMoD username and password are required to run the script.
Before running the script,
1. Install the required `python-dotenv` and `requests` Python dependencies (e.g., using `pip`).
1. Create a `.env` file in the same directory as the script that contains the following contents, replacing `<username>` with your XDMoD username and `<password>` with your XDMoD password — make sure to secure this file with read-only access.
    ```
    XDMOD_USERNAME=<username>
    XDMOD_PASSWORD=<password>
    ```
1. Update the value of `site_address` at the top of the script with the URL associated with your XDMoD portal.
1. Update the value of `export_dir` at the top of the script with the desired directory path where the images will be written.
1. Confirm the desired values for `image_format`, `width`, and `height` at the top of the script. The default image format is `svg`, but `png` and `pdf` formats are also supported.

By default, the script will download all of your saved Metric Explorer charts. You can have it instead download a single chart by providing the `-n` or `--name` option followed by the name of the saved chart.

Refer to the XDMoD [Metric Explorer Tab Controller REST API](rest.html#tag/Metric-Explorer/paths/~1controllers~1metric_explorer.php/post) `get_data` operation for more information on the REST request body schema.

```python
#!/usr/bin/env python3
import os
import requests
import json
import urllib
import argparse
from dotenv import load_dotenv
import sys

site_address = ''
export_dir = '.'
image_format = 'svg'
width = 916
height = 484

if site_address == '':
    print('Please edit the script to specify a site_address.', file=sys.stderr)
    sys.exit(1)

load_dotenv()
username = os.getenv('XDMOD_USERNAME')
password = os.getenv('XDMOD_PASSWORD')

parser = argparse.ArgumentParser(description='Export XDMoD saved Metric Explorer charts with the REST API.')
parser.add_argument('-n', '--name', help='Specify the chart name of a saved chart to export.')
args = parser.parse_args()

session = requests.Session()

auth_response = session.post(f'{site_address}/rest/auth/login', auth=(username, password))

if auth_response.status_code != 200:
    print('Authentication failed. Check provided credentials.', file=sys.stderr)
    quit(1)

auth_response = auth_response.json()

header = {
    'Token': auth_response['results']['token'],
    'Authorization': auth_response['results']['token'],
    'Content-Type': 'application/x-www-form-urlencoded'
}

saved_charts = session.get(f'{site_address}/rest/v1/metrics/explorer/queries', headers=header)
saved_charts_data = saved_charts.json()

if args.name is not None and not any(chart_obj['name'] == args.name for chart_obj in saved_charts_data['data']):
    print('Specified chart not found.', file=sys.stderr)
    exit(1)

for idx, chart in enumerate(saved_charts_data['data']):
    if args.name is not None and args.name != chart['name']:
        continue
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

        chart_json['operation'] = 'get_data'
        chart_json['controller_module'] = 'metric_explorer'
        chart_json['show_title'] = 'y'
        chart_json['format'] = image_format
        chart_json['width'] = width
        chart_json['height'] = height

        chart_response = session.post(f'{site_address}/controllers/metric_explorer.php', data=chart_json, headers=header)
        chart_name = f"{chart['name']}.{image_format}" if ('name' in chart) else f'xdmod_API_export_{idx}.{image_format}'

        with open(export_dir + '/' + chart_name, 'wb') as f:
            f.write(chart_response.content)
            print('Wrote ' + export_dir + '/' + chart_name)
```
