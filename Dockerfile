# Use official PHP image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files into Apache root
COPY . /var/www/html/

# Expose port
EXPOSE 10000

# Render uses $PORT so set Apache to listen on that
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Start Apache
CMD ["apache2-foreground"]
