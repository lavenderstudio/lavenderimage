FROM php:8.2-fpm

# 1. Cài đặt thư viện
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

# 3. Lưu trữ code gốc
RUN mkdir -p /orig/themes /orig/plugins /orig/local /orig/upload && \
    cp -rn themes/* /orig/themes/ && \
    cp -rn plugins/* /orig/plugins/ && \
    mkdir -p persistent_data

# 4. CMD: Khởi chạy và Bảo vệ
EXPOSE 80
CMD php-fpm -D && \
    mkdir -p /var/www/html/persistent_data/themes /var/www/html/persistent_data/plugins /var/www/html/persistent_data/local/config /var/www/html/persistent_data/upload /var/www/html/persistent_data/_data && \
    [ "$(ls -A /var/www/html/persistent_data/themes)" ] || cp -rn /orig/themes/* /var/www/html/persistent_data/themes/ && \
    [ "$(ls -A /var/www/html/persistent_data/plugins)" ] || cp -rn /orig/plugins/* /var/www/html/persistent_data/plugins/ && \
    # CHỐT CHẶN: Ghi đè file cấu hình CHUẨN mỗi khi khởi động (Fix lỗi Parse Error vĩnh viễn)
    echo "<?php \n\$conf['ext_imagick_dir'] = '/usr/bin/'; \n\$conf['graphics_library'] = 'ext_imagick'; \n?>" > /var/www/html/persistent_data/local/config/config.inc.php && \
    echo "<?php \n\$conf['db_host'] = 'mysql.railway.internal:3306'; \n\$conf['db_user'] = 'root'; \n\$conf['db_password'] = 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW'; \n\$conf['db_base'] = 'railway'; \n\$conf['db_prefix'] = 'piwigo_'; \n\$conf['dblayer'] = 'mysqli'; \n\$prefixeTable = 'piwigo_'; \ndefine('PHPWG_INSTALLED', true); \n?>" > /var/www/html/persistent_data/local/config/database.inc.php && \
    # Thiết lập Symlink
    rm -rf themes plugins local upload _data && \
    ln -s /var/www/html/persistent_data/themes /var/www/html/themes && \
    ln -s /var/www/html/persistent_data/plugins /var/www/html/plugins && \
    ln -s /var/www/html/persistent_data/local /var/www/html/local && \
    ln -s /var/www/html/persistent_data/upload /var/www/html/upload && \
    ln -s /var/www/html/persistent_data/_data /var/www/html/_data && \
    chown -R www-data:www-data /var/www/html /var/www/html/persistent_data && \
    chmod -R 777 /var/www/html/persistent_data && \
    nginx -g "daemon off;"
