FROM php:8.2-fpm

# 1. Cài đặt Nginx và các thư viện đồ họa
RUN apt-get update && apt-get install -y nginx libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. Tạo cấu hình Nginx tối giản để chạy Piwigo
RUN echo 'server { \
    listen 80; \
    root /var/www/html; \
    index index.php index.html; \
    location / { try_files $uri $uri/ /index.php?$args; } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

# 3. Thiết lập thư mục và Volume (Symlink để giữ dữ liệu vĩnh viễn)
WORKDIR /var/www/html
COPY . .

RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s /var/www/html/persistent_data/upload /var/www/html/upload \
    && ln -s /var/www/html/persistent_data/local /var/www/html/local \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/persistent_data

# 4. Lệnh khởi chạy cả PHP-FPM và Nginx
EXPOSE 80
CMD php-fpm -D && nginx -g "daemon off;"
