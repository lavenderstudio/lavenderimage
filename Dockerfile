FROM php:8.2-fpm

# 1. Cài đặt Nginx và thư viện
RUN apt-get update && apt-get install -y \
    nginx libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. Cấu hình Nginx
RUN echo 'server { \
    listen 80; \
    root /var/www/html; \
    index index.php index.html; \
    client_max_body_size 100M; \
    location / { try_files $uri $uri/ /index.php?$args; } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

WORKDIR /var/www/html
COPY . .

# 3. THIẾT LẬP VOLUME TRƯỚC
RUN mkdir -p persistent_data/upload persistent_data/local/config \
    && rm -rf upload local \
    && ln -s /var/www/html/persistent_data/upload /var/www/html/upload \
    && ln -s /var/www/html/persistent_data/local /var/www/html/local

# 4. GHI FILE CẤU HÌNH VÀO ĐÚNG VỊ TRÍ TRONG VOLUME
# Lưu ý: Ghi trực tiếp vào persistent_data/local/config/
RUN echo '<?php \
$conf["db_host"] = "mysql.railway.internal:3306"; \
$conf["db_user"] = "root"; \
$conf["db_password"] = "yEaKItfAreoFBaWShRQAhOvZaBZiqgvW"; \
$conf["db_base"] = "railway"; \
$conf["db_prefix"] = "piwigo_"; \
define("PHPWG_INSTALLED", true); \
?>' > persistent_data/local/config/database.inc.php

# 5. PHÂN QUYỀN VÀ KHỞI CHẠY
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/persistent_data

EXPOSE 80
CMD php-fpm -D && nginx -g "daemon off;"
