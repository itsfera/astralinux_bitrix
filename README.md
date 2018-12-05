# Настройка голого AstraLinux с репозиторием Smolensk для работы 1С-Битрикс и Битрикс24

<h2>Подготовка</h2>

```
apt-get install ca-certificates
apt-get install wget
apt-get install curl
apt-get install git
```
Создаем пользователя ```bitrix:bitrix``` с домашней директорией ```/home/bitrix/```, создаем папку ```/home/bitrix/www/```

<h2>Установка MariaDB (MySQL)</h2>

Получаем адрес свежего дистрибутива MariaDB. Например, получаем адрес tar.gz отсюда 
```https://downloads.mariadb.org/interstitial/mariadb-10.3.11/bintar-linux-x86_64/mariadb-10.3.11-linux-x86_64.tar.gz/from/http%3A//mirror.timeweb.ru/mariadb/?serve&change_mirror_from=1```

Полный адрес свежих релизов MariaDB тут
```https://downloads.mariadb.org/interstitial/mariadb-10.3.11/bintar-linux-x86_64/mariadb-10.3.11-linux-x86_64.tar.gz/from/http%3A//mirror.timeweb.ru/mariadb/```

Распаковываем 

```tar -zxf mariadb-10.3.11-linux-x86_64.tar.gz```

Создаем пользователя, прокидываем симлинк
```
groupadd mysql
useradd -g mysql mysql
cd /usr/local
tar -zxvpf /path-to/mariadb-VERSION-OS.tar.gz
ln -s mariadb-VERSION-OS mysql
cd mysql
```
Теперь запускаем скрипт установки

```./scripts/mysql_install_db --user=mysql```

После установки ставим права
```
chown -R root . 
chown -R mysql data
```
Добавляем в ```~/.bashrc ```

```
PATH=${PATH}:/usr/local/mysql/bin:/usr/local/nginx
export PATH 
``` 

Копируем скрипт управления сервисом

```cp support-files/mysql.server /etc/init.d/mysql.server```

Пробуем тестово запустить

```./bin/mysqld_safe --user=mysql &```

Если видим консоль Mysql, значит все хорошо, выходим ```quit```

Устанавливаем конфиг my.cnf и папку mysql с конфигами из архива

Создаем БД (пароль меняем):
```
mysql
CREATE DATABASE `lks`;
CREATE USER 'lks_user' IDENTIFIED BY '1J1QlFMMl9k';
GRANT USAGE ON *.* TO 'lks_user'@'%' IDENTIFIED BY '1J1QlFMMl9k';
GRANT ALL PRIVILEGES ON `lks`.* TO 'lks_user'@'%';
FLUSH PRIVILEGES;
SHOW GRANTS FOR 'lks_user'@localhost;
```

<h2>Установка PHP 7 и сервера PHP-FPM</h2>

```
apt-get install php7.0-fpm
```

Ставим необходимые модули php
```
apt-get -y --no-install-recommends install php-memcached
apt-get -y --no-install-recommends install php-memcache
apt-get -y --no-install-recommends install php-mysql
apt-get -y --no-install-recommends install php-intl
apt-get -y --no-install-recommends install php-interbase
apt-get -y --no-install-recommends install php-gd
apt-get -y --no-install-recommends install php-imagick
apt-get -y --no-install-recommends install php-mcrypt
apt-get -y --no-install-recommends install php-mbstring
apt-get -y --no-install-recommends install php-xml
apt-get -y --no-install-recommends install php-zip
apt-get -y --no-install-recommends install php-soap
apt-get -y --no-install-recommends install catdoc
```

Ставим права на логи
```
mkdir -p /var/log/php/
touch /var/log/php/error.log
chmod 775 /var/log/php/error.log
chown bitrix:bitrix /var/log/php/error.log
touch /var/log/php/opcache.log
chmod 775 /var/log/php/opcache.log
chown bitrix:bitrix /var/log/php/opcache.log
```

Заливаем конфиги в ```/etc/php/```

Собираем MSMTP (для отправки почты через внешний SMTP):
```
wget https://marlam.de/msmtp/releases/msmtp-1.8.0.tar.xz --no-check-certificate
tar -Jxf msmtp-1.8.0.tar.xz
cd msmtp-1.8.0
apt-get install pkg-config
apt-get install dh-autoreconf
apt-get install libgnutls30 libgnutls-openssl27 libgnutls-dane0 libgnutls28-dev libgnutlsxx28
autoreconf -i
./configure
make
sudo make install
```

Заливаем конфиг msmtprc из архива в ```~/.msmtprc``` и создаем симлинки ```/etc/msmtprc``` и ```/usr/local/etc/msmtprc```

Ставим права на конфиг и логи
```
chmod 0600 /etc/msmtprc 
chown bitrix:bitrix /etc/msmtprc
mkdir -p 	/var/log/msmtp/msmtp.log
touch /var/log/msmtp/msmtp.log
chmod 775 /var/log/msmtp/msmtp.log
chown bitrix:bitrix /var/log/msmtp/msmtp.log
```

Не забываем прописать в конфиге актуальные данные SMTP сервера!

Проверим почту 

```echo -e "test message" | /usr/local/bin/msmtp --debug -t -i name@site.ru```

В ответе ожидаем ```250 OK```

Управление сервисом:

```
systemctl status php7.0-fpm
systemctl start php7.0-fpm
systemctl stop php7.0-fpm
systemctl restart php7.0-fpm
```

<h2>Собираем nginx</h2>

Полезные инструкции (для справки)

```
https://docs.nginx.com/nginx/admin-guide/installing-nginx/installing-nginx-open-source/
https://dermanov.ru/exp/configure-push-and-pull-module-for-bitrix24/
https://onlinebd.ru/blog/1s-bitriks-nginx-php-fpm-kompozit
```

Устанавливаем нужные для сборки библиотеки 

```apt-get -y install build-essential zlib1g-dev libpcre3 libpcre3-dev libbz2-dev libssl-dev tar unzip```

Переходим в удобную папку, например, ```tmp``` и начинаем собирать nginx и библиотеки. Начинаем с PCRE:
```
wget ftp://ftp.csx.cam.ac.uk/pub/software/programming/pcre/pcre-8.42.tar.gz --no-check-certificate
tar -zxf pcre-8.42.tar.gz
cd pcre-8.42
./configure
make
sudo make install
```

Теперь zlib
```
wget http://zlib.net/zlib-1.2.11.tar.gz --no-check-certificate
tar -zxf zlib-1.2.11.tar.gz
cd zlib-1.2.11
./configure
make
sudo make install
```

OpenSSL:
```
wget http://www.openssl.org/source/openssl-1.0.2p.tar.gz --no-check-certificate
tar -zxf openssl-1.0.2p.tar.gz
cd openssl-1.0.2p
./Configure darwin64-x86_64-cc --prefix=/usr
make
sudo make install
```
Модуль для чатов:
```
wget https://github.com/wandenberg/nginx-push-stream-module/archive/0.4.1.tar.gz --no-check-certificate
tar -zxf 0.4.1.tar.gz
```

Качаем nginx:
```
wget http://nginx.org/download/nginx-1.15.7.tar.gz --no-check-certificate
tar -zxf nginx-1.15.7.tar.gz 
```

Собираем и устанавливаем

```
./configure --sbin-path=/usr/local/nginx/nginx --conf-path=/usr/local/nginx/nginx.conf --pid-path=/usr/local/nginx/nginx.pid --add-module=../nginx-push-stream-module-0.4.1 --with-zlib=../zlib-1.2.11 --with-openssl=../openssl-1.0.2p --with-pcre=../pcre-8.42 --with-http_ssl_module --with-http_realip_module  --with-http_addition_module  --with-http_sub_module  --with-http_dav_module  --with-http_flv_module  --with-http_mp4_module  --with-http_gunzip_module  --with-http_gzip_static_module  --with-http_random_index_module  --with-http_secure_link_module  --with-http_stub_status_module  --with-http_auth_request_module  --with-http_v2_module  --with-mail  --with-mail_ssl_module  --with-file-aio  --with-ipv6 

make
make install
```
Копируем конфиги из файла в ```/etc/nginx```

создаем ```/lib/systemd/system/nginx.service```

```
[Unit]
Description=The NGINX HTTP and reverse proxy server
After=syslog.target network.target remote-fs.target nss-lookup.target

[Service]
Type=forking
PIDFile=/run/nginx.pid
ExecStartPre=/usr/local/nginx/nginx -t
ExecStart=/usr/local/nginx/nginx
ExecReload=/usr/local/nginx/nginx -s reload
ExecStop=/bin/kill -s QUIT $MAINPID
PrivateTmp=true

[Install]
WantedBy=multi-user.target
```

Запускаем ```service nginx start```

Проверяем ```nginx -V``` и ```service nginx status```

<h3>Настройка memcached</h3>

Устанавливаем 
```
apt-get install memcached
```

Заливаем правильный `/etc/memcached.conf` из конфигов

Проверяем работу 
```
telnet localhost 11211
stats settings
quit
```

В битриксе настраиваем файлы (после внедрения битрикса)
В файле ```/bitrix/php_interface/dbconn.php```

```
define("BX_CACHE_TYPE", "memcache");
define("BX_CACHE_SID", $_SERVER["DOCUMENT_ROOT"]."#01");
define("BX_MEMCACHE_HOST", "localhost");
define("BX_MEMCACHE_PORT", "11211");
```

Создаем новый файл ```/bitrix/ файл .settings_extra.php```

```
<?php
return array (
  'cache' => array(
     'value' => array (
        'type' => 'memcache',
        'memcache' => array(
            'host' => 'localhost',
            'port' => 11211'
        ),
        'sid' => $_SERVER["DOCUMENT_ROOT"]."#01"
     ),
  ),
);
```

Проверяем корректность установки с помощью инструментов "Проверка сайта" и "Монитор производительности". 

<h3>Прочее</h3>

Необходимо наличие флагов +x у всех папок от корня до скриптов в ```/home/bitrix/www```
Проверить можно так ```namei -om /home/bitrix/www/index.php```

Файлы ```.htaccess``` сервером php не обрабатываются, при необходимости пользуемся конвертером 
```http://winginx.com/ru/htaccess```

Необходимо открыть порты HTTP 8893, 8895 и HTTPS порт 8894. 

Необходимо настроить SSL сертификат на сервере или роутере. 

<h3>Установка 1C-Битрикс</h3>

Залить в папку ```/home/bitrix/www``` скрипт ```restore.php```, запустить в браузере, "загрузить архив с дальнего сайта", восстановить БД. 
