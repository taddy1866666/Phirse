FROM php:8.2-cli

# Install required extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Create uploads directories
RUN mkdir -p uploads/products uploads/payment_proofs uploads/logos uploads/pdfs

# Create entrypoint script
RUN echo '#!/bin/sh\nphp -S 0.0.0.0:${PORT:-8000}' > /entrypoint.sh && chmod +x /entrypoint.sh

# Start PHP server
ENTRYPOINT ["/bin/sh"]
CMD ["/entrypoint.sh"]
