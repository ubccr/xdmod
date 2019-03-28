#!/bin/bash
sed -i -- 's/subject_prefix = ""/subject_prefix = "SEND BEN MONEY"/' /etc/xdmod/portal_settings.ini
host=`jq -r .url -- .secrets.json`
curl "${host}controllers/mailer.php" --compressed -H 'X-Requested-With: XMLHttpRequest' -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' --data 'operation=contact&username=__public__&token=public&timestamp=1&reason=contact&name=Contact%20Test&email=test%40example.com&message=test%40example.com' -s > /dev/null

sed -i -- 's/subject_prefix = "SEND BEN MONEY"/subject_prefix = ""/' /etc/xdmod/portal_settings.ini

emailsubjects=`grep 'SEND BEN MONEY' /var/mail/root -c`

if [ $emailsubjects != 2 ]; then
    echo "Email Subject Test returned bad results.  Mail contents:"
    cat /var/mail/root
    exit 123
fi
