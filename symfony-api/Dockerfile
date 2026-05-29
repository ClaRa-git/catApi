FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libxml2-dev zip curl \
    && docker-php-ext-install xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# CLI Symfony
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Utilisateur non-root
RUN useradd -m appuser

WORKDIR /app

# Donner /app à appuser AVANT de switcher        ← fix
RUN chown appuser:appuser /app

COPY --chown=appuser:appuser . .

USER appuser

RUN composer install --no-interaction --optimize-autoloader

EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]