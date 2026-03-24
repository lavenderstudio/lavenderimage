FROM php:8.2-apache

# 1. Cài đặt các thư viện đồ họa cần thiết cho Piwigo
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. XÓA BỎ XUNG ĐỘT MPM: Xóa sạch danh sách module mpm đang bật và chỉ bật duy nhất prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && a2enmod mpm_prefork rewrite

# 3. Thiết lập thư mục làm việc
WORKDIR /var/www/html
COPY . .

# 4. Cấu trúc lưu trữ vào Volume (persistent_data)
RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s persistent_data/upload upload \
    && ln -s persistent_data/local local \
    && chown -R www-data:www-data /var/www/html

# 5. Ép chạy cổng 80 (Bạn nhớ chỉnh Port trên Railway thành 80)
EXPOSE 80

CMD ["apache2-foreground"]
