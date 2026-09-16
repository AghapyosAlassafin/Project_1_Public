FROM php:8.2-apache

# تثبيت المتطلبات الأساسية
RUN apt-get update && apt-get install -y libzip-dev zip unzip

# تثبيت إضافات PHP اللازمة لقواعد البيانات
RUN docker-php-ext-install pdo_mysql zip

# تفعيل mod_rewrite الخاص بـ Apache
RUN a2enmod rewrite

# توجيه السيرفر ليقرأ من مجلد public الخاص بـ Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# نسخ ملفات المشروع
COPY . /var/www/html
WORKDIR /var/www/html

# تثبيت حزم Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# إعطاء الصلاحيات اللازمة لمجلدات التخزين
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache