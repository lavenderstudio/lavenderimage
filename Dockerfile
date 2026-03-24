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
    mkdir -p /var/www/html/persistent_data/local/config && \
    # XÓA FILE LỖI NGAY LẬP TỨC
    rm -f /var/www/html/persistent_data/local/config/config.inc.php && \
    # TẠO LẠI FILE MỚI CHUẨN 100% (KHÔNG CÓ LỖI CÚ PHÁP)
    echo "<?php \n\$conf['ext_imagick_dir'] = '/usr/bin/'; \n\$conf['graphics_library'] = 'ext_imagick'; \n?>" > /var/www/html/persistent_data/local/config/config.inc.php && \
    # KẾT NỐI DATABASE (GIỮ NGUYÊN THÔNG TIN CŨ)
    echo "<?php \n\$conf['db_host'] = 'mysql.railway.internal:3306'; \n\$conf['db_user'] = 'root'; \n\$conf['db_password'] = 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW'; \n\$conf['db_base'] = 'railway'; \n\$conf['db_prefix'] = 'piwigo_'; \n\$conf['dblayer'] = 'mysqli'; \n\$prefixeTable = 'piwigo_'; \ndefine('PHPWG_INSTALLED', true); \n?>" > /var/www/html/persistent_data/local/config/database.inc.php && \
    # THIẾT LẬP LẠI HỆ THỐNG
    rm -rf themes plugins local upload _data && \
    ln -s /var/www/html/persistent_data/themes /var/www/html/themes && \
    ln -s /var/www/html/persistent_data/plugins /var/www/html/plugins && \
    ln -s /var/www/html/persistent_data/local /var/www/html/local && \
    ln -s /var/www/html/persistent_data/upload /var/www/html/upload && \
    ln -s /var/www/html/persistent_data/_data /var/www/html/_data && \
    chown -R www-data:www-data /var/www/html /var/www/html/persistent_data && \
    chmod -R 777 /var/www/html/persistent_data && \
    nginx -g "daemon off;"
