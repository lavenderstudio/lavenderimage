FROM php:8.2-apache

# 1. Cài đặt thư viện
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. Sửa lỗi MPM (Dùng phương pháp triệt để nhất)
RUN a2dismod mpm_event || true && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html
COPY . .

# 3. Cấu trúc Volume và CẤP QUYỀN (Quan trọng nhất)
# Lệnh này đảm bảo thư mục persistent_data luôn thuộc về user www-data
RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s /var/www/html/persistent_data/upload /var/www/html/upload \
    && ln -s /var/www/html/persistent_data/local /var/www/html/local \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/persistent_data

# 4. Chạy cổng 80 (Nhớ chỉnh Networking trên Railway thành 80)
EXPOSE 80
CMD ["apache2-foreground"]
