---
title: nginx
---

***This web server is not currently supported***
***While we have some instances running nginx it has not been fully vetted at this time***
***If you have updates please share, but there will be limited help if you use nginx at this time***

The following assumes you have a JSON format setup for logging.  To do that add the following to your /etc/nginx.conf

```conf
log_format  json  '{'
  '"time": "$time_iso8601", '
  '"request_method": "$request_method", '
  '"request": "$request",'
  '"status": $status,'
  '"request_time": "$request_time", '
  '"remote_addr": "$remote_addr",'
  '"remote_user": "$remote_user",'
  '"body_bytes_sent": $body_bytes_sent,'
  '"http_referer": "$http_referer",'
  '"http_user_agent": "$http_user_agent",'
  '"http_x_forwarded_for": "$http_x_forwarded_for"'
 '}';
```

You will need to change the locations of the html roots in the configuration to match your Open XDMoD installation as well as the server_name to match your host.

sites-available/openxdmod:

```conf
server {
  listen [::]:80 ipv6only=off default_server;
  server_name  oxdm.example.com;
  return 301 https://$server_name$request_uri;
  include  conf.d/restrictions.conf;
}

server {
  listen [::]:443 ssl http2 ipv6only=off;
  server_name  oxdm.example.com;
  include  conf.d/restrictions.conf;
  
  # use https://mozilla.github.io/server-side-tls/ssl-config-generator/ to help
  
  # certs sent to the client in SERVER HELLO are concatenated in ssl_certificate
  ssl_certificate /etc/nginx/ssl/star.ben.dev.crt.pem;
  ssl_certificate_key /etc/nginx/ssl/star.ben.dev.key.pem;
  ssl_session_cache shared:SSL:100m;
  ssl_session_timeout 60m;

  # modern configuration. tweak to your needs.
  ssl_ciphers 'ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA256';
  ssl_prefer_server_ciphers on;
  ssl_protocols TLSv1.2;

  access_log  /var/log/nginx/oxdm.example.com.access.log  json;
  error_log   /var/log/nginx/oxdm.example.com.error.log  debug;

  location ~ ^/rest {
    root       /usr/xdmod/share/html;
    try_files  (.*) /rest/index.php;
    include    conf.d/php-fpm;
  }
  location ~ ^/extrest {
    root       /usr/xdmod/share/html;
    try_files  (.*) /extrest/index.php;
    include    conf.d/php-fpm;
  }

  location ~ / {
    root      /usr/xdmod/share/html/;
    include   /etc/nginx/conf.d/php-fpm;
  }

}
```

/etc/nginx/conf.d/php-fpm.conf

```conf
location ~ \.php$ {
    try_files      $uri = 404;
    fastcgi_pass   127.0.0.1:9000;
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include        fastcgi_params;
}
```

/etc/nginx/conf.d/restrictions.conf

```conf
# Global restrictions configuration file.
# Designed to be included in any server {} block.</p>
location = /favicon.ico {
  log_not_found off;
  access_log off;
}

location = /robots.txt {
  allow all;
  log_not_found off;
  access_log off;
}

# Deny all attempts to access hidden files such as .htaccess, .htpasswd, .DS_Store (Mac).
# Keep logging the requests to parse later (or to pass to firewall utilities such as fail2ban)
location ~ /\. {
  deny all;
}
```
