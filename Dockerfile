FROM php:8.2-fpm

# 1. Cài đặt thư viện & Kích hoạt OpCache để tăng tốc PHP
RUN apt-get update && apt-get install -y \
    nginx libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif opcache

# 2. Cấu hình Nginx với "Xé Gió" Caching (Lưu cache ảnh, CSS, JS 30 ngày)
RUN echo 'server { \
    listen 80; \
    root /var/www/html; \
    index index.php index.html; \
    client_max_body_size 100M; \
    location / { try_files $uri $uri/ /index.php?$args; } \
    # Bộ lọc Caching cực mạnh cho file tĩnh \
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|webp|woff|woff2|ttf|otf)$ { \
        expires 30d; \
        add_header Cache-Control "public, no-transform"; \
        access_log off; \
        log_not_found off; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

WORKDIR /var/www/html
COPY . .

# 3. Lưu trữ code gốc & Chuẩn bị môi trường
RUN mkdir -p /orig/themes /orig/plugins /orig/local /orig/upload && \
    cp -rn themes/* /orig/themes/ || true && \
    cp -rn plugins/* /orig/plugins/ || true && \
    mkdir -p persistent_data

# 4. CMD: Kích hoạt hệ thống & Bảo vệ vĩnh viễn
EXPOSE 80
CMD php-fpm -D && \
    # Thiết lập OpCache tối ưu cho RAM 
    echo "opcache.enable=1\nopcache.memory_consumption=128\nopcache.interned_strings_buffer=8\nopcache.max_accelerated_files=4000\nopcache.revalidate_freq=60" > /usr/local/etc/php/conf.d/opcache-optimized.ini && \
    # Đảm bảo cấu trúc thư mục Volume
    mkdir -p persistent_data/themes persistent_data/plugins persistent_data/local/config persistent_data/upload persistent_data/_data && \
    # Phục hồi dữ liệu nếu Volume mới
    [ "$(ls -A /var/www/html/persistent_data/themes)" ] || cp -rn /orig/themes/* /var/www/html/persistent_data/themes/ && \
    [ "$(ls -A /var/www/html/persistent_data/plugins)" ] || cp -rn /orig/plugins/* /var/www/html/persistent_data/plugins/ && \
    # CHỐT CHẶN: Ghi đè file cấu hình CHUẨN (Fix lỗi Parse Error & Ép dùng ImageMagick)
    echo "<?php \n\$conf['ext_imagick_dir'] = '/usr/bin/'; \n\$conf['graphics_library'] = 'ext_imagick'; \n?>" > /var/www/html/persistent_data/local/config/config.inc.php && \
    echo "<?php \n\$conf['db_host'] = 'mysql.railway.internal:3306'; \n\$conf['db_user'] = 'root'; \n\$conf['db_password'] = 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW'; \n\$conf['db_base'] = 'railway'; \n\$conf['db_prefix'] = 'piwigo_'; \n\$conf['dblayer'] = 'mysqli'; \n\$prefixeTable = 'piwigo_'; \ndefine('PHPWG_INSTALLED', true); \n?>" > /var/www/html/persistent_data/local/config/database.inc.php && \
    # Thiết lập Symlink vĩnh viễn
    rm -rf themes plugins local upload _data && \
    ln -s /var/www/html/persistent_data/themes /var/www/html/themes && \
    ln -s /var/www/html/persistent_data/plugins /var/www/html/plugins && \
    ln -s /var/www/html/persistent_data/local /var/www/html/local && \
    ln -s /var/www/html/persistent_data/upload /var/www/html/upload && \
    ln -s /var/www/html/persistent_data/_data /var/www/html/_data && \
    # Cấp quyền cho www-data
    chown -R www-data:www-data /var/www/html /var/www/html/persistent_data && \
    chmod -R 777 /var/www/html/persistent_data && \
    nginx -g "daemon off;"
