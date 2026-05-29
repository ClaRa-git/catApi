FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libxml2-dev zip curl \
    && docker-php-ext-install xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

RUN useradd -m appuser

WORKDIR /app

RUN chown appuser:appuser /app

COPY --chown=appuser:appuser . .

USER appuser

RUN composer install --no-interaction --optimize-autoloader --no-scripts

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]