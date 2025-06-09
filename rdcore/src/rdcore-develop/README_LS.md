### Additional processing required during installation
To enable the additional features, the following command must be executed during installation.

```
$ cd /var/www/html/cake3/rd_cake
$ vendor/bin/phinx migrate
$ php vendor/bin/composer require google/apiclient:^2.12.1 --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-bcmath
$ php vendor/bin/composer require phpseclib/mcrypt_compat --ignore-platform-req=ext-bcmath --ignore-platform-req=ext-bcmath
```
