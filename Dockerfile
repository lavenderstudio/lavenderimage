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

# Thiết lập thư mục làm việc
WORKDIR /app

# Sao chép toàn bộ mã nguồn vào container
COPY . /app

# Tạo các thư mục cần thiết (nếu chưa có) và cấp quyền truy cập
RUN mkdir -p upload _data local \
    && chmod -R 777 upload _data local

# Chạy server PHP tích hợp
CMD php -S 0.0.0.0:8080
