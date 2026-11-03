# SPCA Guardian Pro: PHP 8.3 + Apache (same setup as most shared hosts, including InfinityFree)
FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
 && a2enmod rewrite headers \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && echo 'DirectoryIndex index.php Homepage.html' > /etc/apache2/conf-enabled/spca.conf

COPY . /var/www/html/
RUN rm -f /var/www/html/config.php \
 && mkdir -p /var/www/html/logs /var/www/html/uploads \
 && chown -R www-data:www-data /var/www/html/logs /var/www/html/uploads

EXPOSE 80
