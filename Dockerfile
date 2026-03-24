FROM php:8.2-apache

# 1. Cài đặt các thư viện cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. XÓA BỎ XUNG ĐỘT MPM: Xóa sạch các file config mpm cũ và chỉ bật duy nhất mpm_prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && a2enmod mpm_prefork rewrite

# 3. Thiết lập thư mục làm việc
WORKDIR /var/www/html
COPY . .

# 4. Cấu trúc Symlink để lưu dữ liệu vĩnh viễn vào Volume
RUN mkdir -p persistent_data/upload persistent_data/local \
    && [ -d upload ] && mv upload/* persistent_data/upload/ || true \
    && [ -d local ] && mv local/* persistent_data/local/ || true \
    && rm -rf upload local \
    && ln -s persistent_data/upload upload \
    && ln -s persistent_data/local local \
    && chown -R www-data:www-data /var/www/html

# 5. Ép Apache chạy trên cổng 80 (nhớ chỉnh Port trên Railway thành 80)
EXPOSE 80

CMD ["apache2-foreground"]
