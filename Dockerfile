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

# 3. CHUẨN BỊ CẤU TRÚC (Không xóa ngay để tí nữa copy dữ liệu gốc)
RUN mkdir -p persistent_data/upload persistent_data/local persistent_data/_data persistent_data/plugins persistent_data/themes

# 4. SIÊU BIẾN PHÁP CMD: Xử lý thông minh khi khởi chạy
EXPOSE 80
CMD php-fpm -D && \
    # Bước A: Nếu Volume trống, copy dữ liệu mặc định từ code vào Volume
    cp -rn plugins/* persistent_data/plugins/ 2>/dev/null || true && \
    cp -rn themes/* persistent_data/themes/ 2>/dev/null || true && \
    cp -rn local/* persistent_data/local/ 2>/dev/null || true && \
    # Bước B: Xóa thư mục tạm trong Container và tạo Symlink tới Volume
    rm -rf upload local _data plugins themes && \
    ln -s /var/www/html/persistent_data/upload /var/www/html/upload && \
    ln -s /var/www/html/persistent_data/local /var/www/html/local && \
    ln -s /var/www/html/persistent_data/_data /var/www/html/_data && \
    ln -s /var/www/html/persistent_data/plugins /var/www/html/plugins && \
    ln -s /var/www/html/persistent_data/themes /var/www/html/themes && \
    # Bước C: Ghi file cấu hình DB (Luôn đảm bảo kết nối)
    mkdir -p /var/www/html/persistent_data/local/config && \
    echo "<?php \n\
\$conf['db_host'] = 'mysql.railway.internal:3306'; \n\
\$conf['db_user'] = 'root'; \n\
\$conf['db_password'] = 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW'; \n\
\$conf['db_base'] = 'railway'; \n\
\$conf['db_prefix'] = 'piwigo_'; \n\
\$conf['dblayer'] = 'mysqli'; \n\
\$prefixeTable = 'piwigo_'; \n\
define('PHPWG_INSTALLED', true); \n\
?>" > /var/www/html/persistent_data/local/config/database.inc.php && \
    # Bước D: Phân quyền và chạy web
    chown -R www-data:www-data /var/www/html && \
    chmod -R 777 /var/www/html/persistent_data && \
    nginx -g "daemon off;"
