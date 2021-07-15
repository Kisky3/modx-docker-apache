# modx-docker-apache

[Modx Evolution](https://modx.jp/) docker environment
apache + php + phpmyadmin + mysql


## set up
```
docker system prune  //clean up
docker-compose up   // run docker
```

## Web
http://localhost

## phpMyAdmin
http://localhost:8080


## modx evolution
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

## modx db
```
HOST: db
USER: root
PASSWORD: root_pass_fB3uWvTS
```

