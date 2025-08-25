# Multi-stage build for smaller final image (Composer deps separated)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-scripts --no-interaction || true

FROM php:8.2-apache

LABEL org.opencontainers.image.source="https://github.com/Dinil-Thilakarathne/ecoCycle" \
      org.opencontainers.image.title="ecoCycle" \
      org.opencontainers.image.description="ecoCycle custom PHP framework application" \
      maintainer="ecoCycle"

# Install system dependencies & PHP extensions
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
     libzip-dev zip unzip git libicu-dev \
  && docker-php-ext-install pdo_mysql intl \
  && a2enmod rewrite headers expires \
  && rm -rf /var/lib/apt/lists/*

# Copy PHP dependency vendor directory from build stage
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY composer.json composer.lock* ./

# Copy application source
COPY . .

# Apache config hardening (serve from public)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && chown -R www-data:www-data /var/www/html
  # Ensure .htaccess overrides are honored for pretty URLs
RUN printf "<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-enabled/zzz-override.conf

# Production PHP settings overrides (minimal)
RUN { \
  echo 'expose_php=0'; \
  echo 'display_errors=0'; \
  echo 'log_errors=1'; \
  echo 'memory_limit=256M'; \
  echo 'upload_max_filesize=16M'; \
  echo 'post_max_size=16M'; \
  echo 'session.cookie_httponly=1'; \
  echo 'session.use_strict_mode=1'; \
} > /usr/local/etc/php/conf.d/99-ecocycle.ini

# Add ServerName to suppress Apache warning
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf && a2enconf servername

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 CMD curl -f http://localhost/ || exit 1

# Default command
CMD ["apache2-foreground"]
