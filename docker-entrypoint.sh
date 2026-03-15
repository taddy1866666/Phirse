#!/bin/bash
# Replace port at runtime when Railway injects the PORT env var
sed -i "s/Listen 80/Listen ${PORT:-80}/g" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
apache2-foreground
