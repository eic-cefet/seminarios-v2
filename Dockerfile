# Build frontend assets
FROM node:lts AS assetbuild

RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /build

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY vite.config.ts tsconfig.json tsconfig.node.json .
COPY resources ./resources
COPY public ./public

RUN pnpm run build

# PHP application with FrankenPHP
FROM dunglas/frankenphp:1.11-builder-php8.4.17 AS runner

# Install PHP extensions
RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    mbstring \
    exif \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application code
COPY . .

# Complete composer install with autoloader
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Copy built assets from assetbuild stage
COPY --from=assetbuild /build/public/build ./public/build

# Copy entrypoint scripts and make them executable
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/scheduler.sh /usr/local/bin/scheduler.sh
COPY docker/worker.sh /usr/local/bin/worker.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/scheduler.sh /usr/local/bin/worker.sh

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 443

ENTRYPOINT ["entrypoint.sh"]
