FROM php:8.2-apache

# Cài đặt các thư viện hệ thống cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli gd zip

# Kích hoạt mod_rewrite cho Apache (Piwigo cần cái này)
RUN a2enmod rewrite

# Sao chép mã nguồn vào thư mục web
COPY . /var/www/html/

# Cấp quyền cho thư mục (để Piwigo có thể ghi file)
RUN chown -R www-data:www-data /var/www/html

# Mở cổng 80
EXPOSE 80
