FROM php:8.2-apache

# Enable mod_rewrite (optional but nice)
RUN a2enmod rewrite

# Copy your PHP files into Apache web root
COPY . /var/www/html/

# Make Apache listen on port 8080 inside the container
ENV APACHE_LISTEN_PORT=8080
RUN sed -ri -e 's!Listen 80!Listen ${APACHE_LISTEN_PORT}!g' /etc/apache2/ports.conf \
    -e 's!<VirtualHost \*:80>!<VirtualHost \*:${APACHE_LISTEN_PORT}>!g' /etc/apache2/sites-available/000-default.conf

# Expose 8080 (Render will map this to the public URL port)
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]
