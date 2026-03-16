FROM php:8.2-cli

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Create uploads directories
RUN mkdir -p uploads/products uploads/payment_proofs uploads/logos uploads/pdfs

# Expose port (will be overridden by Railway)
EXPOSE 8000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:${PORT:-8000}"]
