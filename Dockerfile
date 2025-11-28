FROM php:8.2-apache

# Enable rewrite module
RUN a2enmod rewrite

# Copy application code
COPY . /var/www/html/

# Set Apache to listen on port 8080
ENV APACHE_LISTEN_PORT=8080

# Fix Apache site configuration (listen and document root permissions)
RUN sed -ri -e 's!Listen 80!Listen ${APACHE_LISTEN_PORT}!g' /etc/apache2/ports.conf \
    -e 's!<VirtualHost \*:80>!<VirtualHost \*:${APACHE_LISTEN_PORT}>!g' /etc/apache2/sites-available/000-default.conf

# Add Directory permissions to allow access
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/webroot.conf \
    && a2enconf webroot

EXPOSE 8080

CMD ["apache2-foreground"]
