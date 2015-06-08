# Your own private cloud
## Deine eigene private Cloud
*by Andreas Gunsch*

***

### system requirement

* Only one user should have root access.
* PHP 5.3.2 > & MySQL 5.1 or SQLite modul
* ssl is recommended
* Desktop Computer (Chrome, Firefox, Safari)
* Mobile Phone ios, android (only Firefox)
* [https://code.google.com/p/android/issues/detail?id=3492#c60]

### Script's from others (thanks)

- jQuery [http://jquery.com]
- jQuery File Upload Plugin [https://github.com/blueimp/jQuery-File-Upload]
- jQuery UI Widget [https://github.com/blueimp/jQuery-File-Upload]
- jQuery Iframe Transport Plugin [https://github.com/blueimp/jQuery-File-Upload]
- jQuery Knob [https://github.com/aterrien/jQuery-Knob]
- Mini AJAX File Upload Form [http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/]

### features

* apache2 or nginx
* sqlite or mysql
* disk quota
* responsive design
* no cookies or sessions
* auto logout
* uploads private or public
* user registration without mail's

### installation

copy all files and change the **"cfg.inc.php"**
The folder **"files"** & ***"db"*** needs write and read protection,
only apache/nginx should have access (www-data)

*The first user ID:1 is automatically the administrator.*

You can use MySQL or SQLite

Create a MySQL Database and import this tables **"files"** and **"users"**

```sql
CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(32) NOT NULL,
  `file` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cuser` varchar(20) NOT NULL,
  `cpass` varchar(102) NOT NULL,
  `locked` int(11) NOT NULL,
  `uuid` varchar(32) NOT NULL,
  `groups` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cuser` (`cuser`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

If you use SQLite the database file is automatically created as **"db/data.sqlite"**
Check the file permissions on folder **"db"**

### apache & nginx configuration

### php installation for apache2 & nginx (sqlite)
```bash
apt-get install php5-sqlite
```

### php configuration (tmp & sizes)

changes recommended in **"php.ini"**
The best here is a directory on the same partition.
Because if the file is uploaded, it's moving.
It's better than copy the file, and You don't need the double diskspace.

**/etc/php5/apache2/php.ini**

**/etc/php5/fpm/php.ini**
```ini
upload_tmp_dir = "/var/www/tmp"
upload_max_filesize = 1G
post_max_size = 1G
```

### SSL configuration for apache2 or nginx (https)

SSL is recommended, own key is better than nothing!

[https://www.debian-administration.org/article/349/Setting_up_an_SSL_server_with_Apache2]
[https://www.digitalocean.com/community/tutorials/how-to-create-a-ssl-certificate-on-apache-for-ubuntu-12-04]

**short notes**
```bash
cd /etc/ssl
openssl genrsa -des3 -out server.key 2048
openssl rsa -in server.key -out server.key.unsecure
openssl req -new -key server.key -out server.csr
openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
```

**/etc/apache2/ports.conf**

look for port 443

**/etc/apache2/sites-enabled/your.conf**
```apache
NameVirtualHost 127.0.0.1:443

<VirtualHost *:443>
  DocumentRoot /var/www/
  SSLEngine on
  SSLCertificateKeyFile /etc/ssl/server.key.unsecure
  SSLCertificateFile /etc/ssl/server.crt
  <Directory />
    Options FollowSymLinks
    AllowOverride None
  </Directory>
  <Directory /var/www/>
    Options Indexes FollowSymLinks MultiViews
    AllowOverride All
    Order allow,deny
    allow from all
  </Directory>
  ErrorLog /var/log/apache2/error.log
  CustomLog /var/log/apache2/access.log combined
  LogLevel warn
</VirtualHost>
```
```
a2enmod ssl
/etc/init.d/apache2 restart
```

**nginx**
```bash
apt-get install nginx php5-fpm
```

**/etc/nginx/sites-available/default**

example: If Your cloud is on **https: //YourDomain.com/cloud/**
```nginx
server {
  listen 443;
  server_name YourDomain.com;

  root html;
  index index.html index.htm index.php;

  ssl on;
  ssl_certificate /etc/ssl/server.crt;
  ssl_certificate_key /etc/ssl/server.key.unsecure;

  ssl_session_timeout 5m;

  ssl_protocols SSLv3 TLSv1 TLSv1.1 TLSv1.2;
  ssl_ciphers "HIGH:!aNULL:!MD5 or HIGH:!aNULL:!MD5:!3DES";
  ssl_prefer_server_ciphers on;

  location ~ \.php$ {
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/var/run/php5-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
  }

  location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
  }

  location /cloud/ {
    rewrite ^/cloud/download-(.*)$ /cloud/index.php?d=$1 last;
  }

  location /cloud/db/ {
    deny all;
  }

  location /cloud/files/ {
    deny all;
  }
}
```
for chrome download "server.crt" and import this in "Authorities", open a new tab.
The Certificate should contain the correct domain (CN) then the "lock" is light up green
