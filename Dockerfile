FROM php:8.2-apache

# Cài đặt thư viện đồ họa và nén
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# FIX LỖI MPM: Vô hiệu hóa mpm_event (thường gây xung đột) và bật mpm_prefork
RUN a2dismod mpm_event || true && a2enmod mpm_prefork rewrite

# Thiết lập thư mục làm việc
WORKDIR /var/www/html
COPY . .

# Tạo cấu trúc lưu trữ vĩnh viễn (Chống mất dữ liệu và cài lại)
RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s persistent_data/upload upload \
    && ln -s persistent_data/local local \
    && chown -R www-data:www-data /var/www/html

# Port 80 là chuẩn của Apache
EXPOSE 80

CMD ["apache2-foreground"]
