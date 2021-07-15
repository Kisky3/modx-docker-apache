# modx-docker-apache

## set up
docker-compose up

## Web
http://localhost

## phpMyAdmin
http://localhost:8080


run these into docker apache container to install modx evolution

```
curl -o modx.zip -SL https://github.com/modxcms-jp/evolution-jp/archive/master.zip
apt-get update
apt-get install unzip
unzip modx.zip
mv /var/www/html/evolution-jp-master /var/www/html/modx
// mv sample.htaccess .htaccess
sed -i "5c RewriteBase /modx" sample.htaccess
// cd /var/www/html/modx/manager
// mv ht.access .htaccess
```
