FROM php:8.2-cli

# Cài đặt các thư viện hệ thống và extension PHP cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd zip

# Sao chép mã nguồn
COPY . /app
WORKDIR /app

# Cấp quyền cho các thư mục quan trọng
RUN chmod -R 777 upload _data local

# Chạy server PHP tích hợp trên cổng mà Railway cung cấp
CMD php -S 0.0.0.0:$PORT
