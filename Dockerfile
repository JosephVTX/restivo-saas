# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# 1. Build frontend assets
# ---------------------------------------------------------------------------
FROM node:24-alpine AS assets
WORKDIR /app
RUN corepack enable
COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm build

# ---------------------------------------------------------------------------
# 2. Install PHP dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---------------------------------------------------------------------------
# 3. Runtime: FrankenPHP + Octane (high concurrency, low memory)
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.3 AS runtime

RUN install-php-extensions pdo_mysql redis intl zip opcache pcntl soap gd bcmath

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN php artisan storage:link || true

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=auto", "--task-workers=auto", "--max-requests=500"]
