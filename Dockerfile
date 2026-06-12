FROM php:8.2-apache
# installe les outils necessaire pour preparer l'intallation de l'extension MONGODB PHP && dl + install
RUN apt-get update && apt-get install -y libssl-dev pkg-config && pecl install mongodb && docker-php-ext-enable mongodb && docker-php-ext-install pdo pdo_mysql
#active le module apache rewrite pour les redirection et les regle .htacces
RUN a2enmod rewrite

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

