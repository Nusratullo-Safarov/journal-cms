# Барои кор дар компютер ё VPS. Дар хостинги оддӣ Docker лозим нест — ба README.md нигаред.
FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite headers expires \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && printf 'upload_max_filesize=50M\npost_max_size=64M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/journal.ini
COPY . /var/www/html/
RUN cp -n /var/www/html/includes/config.example.php /var/www/html/includes/config.php \
 && chown -R www-data:www-data /var/www/html/uploads /var/www/html/storage
VOLUME ["/var/www/html/uploads", "/var/www/html/storage"]
