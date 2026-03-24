FROM php:8.2-apache

# 1. Cài đặt các thư viện đồ họa
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. DIỆT TẬN GỐC LỖI MPM: Xóa sạch thư mục cấu hình mpm để Apache không thể nạp sai
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" > /etc/apache2/mods-enabled/mpm_prefork.load \
    && a2enmod rewrite

# 3. Thiết lập thư mục làm việc
WORKDIR /var/www/html
COPY . .

# 4. Cấu trúc Symlink để lưu dữ liệu vĩnh viễn (Chống cài lại)
RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s persistent_data/upload upload \
    && ln -s persistent_data/local local \
    && chown -R www-data:www-data /var/www/html

# 5. Ép chạy cổng 80 (Chỉnh Port trên Railway thành 80)
EXPOSE 80

CMD ["apache2-foreground"]
