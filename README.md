# Your own private cloud
## Deine eigene private Cloud
*by Andreas Gunsch*

***

### system requirement

* Only one user should have root access.
* PHP 5.3.2 > & MySQL 5.1 or SQLite apache modul
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

* sqlite or mysql
* disk quota
* responsive design
* no cookies or sessions
* auto logout
* uploads private or public
* user registration without mail's

### installation

copy all files and change the **"cfg.inc.php"**
The folder **"files"** needs write and read protection,
only apache should have access (webservice)

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

### apache configuration

**.htaccess**
```
php_value upload_max_filesize 1G
php_value post_max_size 1G
RewriteBase /
```

### php installation (sqlite)
```
apt-get install php5-sqlite
service apache2 restart
```

### php configuration (tmp)

changes recommended in **"php.ini"**
The best here is a directory on the same partition.
Because if the file is uploaded, it's moving.
It's better than copy the file, and You don't need the double space.

**/etc/php5/apache2/php.ini**
```
upload_tmp_dir = "/var/www/tmp"
```

### SSL configuration (https)

SSL is recommended, own key is better than nothing!

[https://www.debian-administration.org/article/349/Setting_up_an_SSL_server_with_Apache2]
[https://www.digitalocean.com/community/tutorials/how-to-create-a-ssl-certificate-on-apache-for-ubuntu-12-04]

**short notes**
```
mkdir /etc/apache2/ssl
cd /etc/apache2/ssl
openssl genrsa -des3 -out server.key 2048
openssl rsa -in server.key -out server.key.unsecure
openssl req -new -key server.key -out server.csr
openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt
```

**/etc/apache2/ports.conf**
look for port 443

**/etc/apache2/sites-enabled/your.conf**
```
  NameVirtualHost 127.0.0.1:443

  <VirtualHost *:443>
    DocumentRoot /var/www/
    SSLEngine on
    SSLCertificateKeyFile /etc/apache2/ssl/server.key.unsecure
    SSLCertificateFile /etc/apache2/ssl/server.crt
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
for chrome download "server.crt" and import this in "Authorities", open a new tab.
The Certificate should contain the correct domain (CN)
In our example 127.0.0.1, then the "lock" is light up green
