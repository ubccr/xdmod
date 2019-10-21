---
title: HOWTO Change Summary Page Charts
---

To change the default charts for all users you need to update the
`roles.json` file (`/etc/xdmod/roles.json` if you installed the RPM or
`PREFIX/etc/roles.json` if you did a manual install) and change the JSON
for the charts. Since it is not obvious how to do this, I suggest
looking at the JSON created from the Metric Explorer for the changes you
need to make in `roles.json`.

First, log into the portal and create the desired charts using the
Metric Explorer.  You can add this chart to the summary page, but only
for that user, by checking "Show in Summary Tab".

After saving the chart, select from the table `UserProfiles` in the
database `moddb`.

    mysql> SELECT * FROM moddb.UserProfiles;

This will show the user profile data for all users including the chart
JSON serialized by PHP. If you are unsure of your `user_id`, check the
`Users` table.

    mysql> SELECT id, username FROM moddb.Users;

Next you'll need to deserialize the data then examine the JSON.  Copy
this code into a file and run it with the data from your user profile:

    <?php
    $data = 'a:1:{....'; // Copy your data into this variable.
    $profile = unserialize($data);
    $queries = json_decode($profile['queries'], true);
    foreach ($queries as $name => $query) {
        echo $name, ":\n";
        echo $query['config'], "\n\n";
    }

This will output the JSON for each chart saved for the user the data was
copied from.  Find the chart that you want to add to the default summary
page and copy it into `summary_charts` section of `roles.json`.  Add the
JSON object list after the name of the chart.

    "summary_charts": [
        {
            ...
        },
        {
            ...
        },
        ...
    ]

You should also add a title to JSON for the chart that will be displayed
on the summary page.

    "summary_charts": [
        {
            "title": "Chart Title",
            ...
        },
        ...
    ]

Note that it is possible to have a different set of charts for different
roles, but the default configuration uses a single set of charts for all
roles.
