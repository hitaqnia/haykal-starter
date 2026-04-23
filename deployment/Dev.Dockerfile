# Development Dockerfile for a HiTaqnia Haykal application.
# Optimized for local development with hot-reload and volume mounting.

ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1.11
ARG COMPOSER_VERSION=2.8

FROM composer:${COMPOSER_VERSION} AS vendor

FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine

LABEL maintainer="HiTaqnia <admin@hitaqnia.com>"
LABEL org.opencontainers.image.title="Haykal App (Dev)"

ARG USER_ID=1000
ARG GROUP_ID=1000
ARG TZ=UTC

ENV TERM=xterm-256color \
    TZ=${TZ} \
    USER=laravel \
    ROOT=/var/www/html \
    APP_ENV=local \
    COMPOSER_FUND=0 \
    COMPOSER_MAX_PARALLEL_HTTP=48

ENV ENV_USER=${USER} ENV_ROOT=${ROOT}
ENV XDG_CONFIG_HOME=${ROOT}/.config XDG_DATA_HOME=${ROOT}/.data

WORKDIR ${ROOT}

SHELL ["/bin/sh", "-eou", "pipefail", "-c"]

RUN ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

RUN apk update; \
    apk upgrade; \
    apk add --no-cache \
    curl \
    wget \
    vim \
    git \
    tzdata \
    ncdu \
    procps \
    unzip \
    ca-certificates \
    bash \
    supervisor \
    libsodium-dev \
    nodejs \
    npm \
    && curl -fsSL https://bun.sh/install | BUN_INSTALL=/usr bash \
    && install-php-extensions \
    apcu \
    bz2 \
    pcntl \
    mbstring \
    bcmath \
    sockets \
    pdo_pgsql \
    opcache \
    exif \
    pdo_mysql \
    zip \
    uv \
    intl \
    gd \
    redis \
    rdkafka \
    ffi \
    ldap \
    xdebug \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN addgroup -g ${GROUP_ID} ${USER} \
    && adduser -D -G ${USER} -u ${USER_ID} -s /bin/sh ${USER}

# Use development PHP config
RUN cp ${PHP_INI_DIR}/php.ini-development ${PHP_INI_DIR}/php.ini

COPY --link --from=vendor /usr/bin/composer /usr/bin/composer
COPY --link deployment/php.dev.ini ${PHP_INI_DIR}/conf.d/99-php.ini

# Create necessary directories
RUN mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache \
    && chown -R ${USER_ID}:${GROUP_ID} ${ROOT}

USER ${USER}

EXPOSE 8000
EXPOSE 5173

# Default command runs PHP built-in server (simple and reliable for dev)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
