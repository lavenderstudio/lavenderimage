FROM php:8.2-cli

# 1. Cài đặt các thư viện đồ họa cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip exif

# 2. Thiết lập thư mục làm việc
WORKDIR /var/www/html
COPY . .

# 3. Cấu trúc Symlink "Bất tử" (Lưu vào Volume để không mất dữ liệu)
RUN mkdir -p persistent_data/upload persistent_data/local \
    && rm -rf upload local \
    && ln -s /var/www/html/persistent_data/upload /var/www/html/upload \
    && ln -s /var/www/html/persistent_data/local /var/www/html/local \
    && chown -R www-data:www-data /var/www/html

# 4. Chạy server trực tiếp trên Port của Railway cấp
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]
