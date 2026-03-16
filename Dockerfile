FROM php:8.2-apache

# Install PHP extensions needed for the app
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Disable MPM event and prefork to avoid conflicts, enable mpm_prefork
RUN a2dismod mpm_event && a2enmod mpm_prefork

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Allow .htaccess overrides and disable directory listing for uploads
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html/uploads>\n    Options -Indexes\n</Directory>' >> /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Create uploads directories if they don't exist
RUN mkdir -p /var/www/html/uploads/products \
    /var/www/html/uploads/product_pdfs \
    /var/www/html/uploads/payment_proofs \
    /var/www/html/uploads/logos \
    /var/www/html/uploads/gcash

# Set proper permissions for uploads
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 755 /var/www/html/uploads

# Fix Windows line endings and make entrypoint executable
RUN sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh

# Use entrypoint to configure PORT at runtime
CMD ["/var/www/html/docker-entrypoint.sh"]
