FROM php:8.2-fpm

# 1. Cài đặt thư viện đồ họa (GD, ImageMagick)
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

# 3. Tạo thư mục tạm để lưu code gốc (Tránh bị rm -rf làm mất dữ liệu)
RUN mkdir -p /orig/themes /orig/plugins /orig/local /orig/upload

# 4. CHỐT CHẶN: Di chuyển dữ liệu mặc định vào vùng an toàn trước khi xóa thư mục chính
RUN cp -rn themes/* /orig/themes/ && \
    cp -rn plugins/* /orig/plugins/ && \
    mkdir -p persistent_data

# 5. CMD: Cơ chế tự động khôi phục dữ liệu nếu Volume trống
EXPOSE 80
CMD php-fpm -D && \
    # Tạo cấu trúc thư mục trong Volume nếu chưa có
    mkdir -p persistent_data/themes persistent_data/plugins persistent_data/local/config persistent_data/upload persistent_data/_data && \
    # Nếu Volume trống (lần đầu hoặc redeploy), nạp lại Themes và Plugins mặc định
    [ "$(ls -A persistent_data/themes)" ] || cp -rn /orig/themes/* persistent_data/themes/ && \
    [ "$(ls -A persistent_data/plugins)" ] || cp -rn /orig/plugins/* persistent_data/plugins/ && \
    # Xóa các thư mục tĩnh và Symlink vào Volume
    rm -rf themes plugins local upload _data && \
    ln -s /var/www/html/persistent_data/themes /var/www/html/themes && \
    ln -s /var/www/html/persistent_data/plugins /var/www/html/plugins && \
    ln -s /var/www/html/persistent_data/local /var/www/html/local && \
    ln -s /var/www/html/persistent_data/upload /var/www/html/upload && \
    ln -s /var/www/html/persistent_data/_data /var/www/html/_data && \
    # Ghi file cấu hình Database
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
    # Phân quyền cuối cùng
    chown -R www-data:www-data /var/www/html /var/www/html/persistent_data && \
    chmod -R 777 /var/www/html/persistent_data && \
    nginx -g "daemon off;"
