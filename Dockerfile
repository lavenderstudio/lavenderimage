FROM php:8.2-apache

# Cài đặt thư viện cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . .

# CHIÊU CUỐI: Tạo một thư mục tổng 'persistent_data' để chứa tất cả
# Sau đó nối (link) upload và local vào thư mục này
RUN mkdir -p /var/www/html/persistent_data/upload \
    && mkdir -p /var/www/html/persistent_data/local \
    && rm -rf /var/www/html/upload /var/www/html/local \
    && ln -s /var/www/html/persistent_data/upload /var/www/html/upload \
    && ln -s /var/www/html/persistent_data/local /var/www/html/local \
    && chown -R www-data:www-data /var/www/html/persistent_data

CMD ["apache2-foreground"]
