FROM php:8.4-cli

# Extensões exigidas pelo Laravel e pelo PostgreSQL.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libzip-dev libicu-dev \
    && docker-php-ext-install pdo_pgsql pgsql zip intl bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# O código vem por volume de uma pasta do Windows, e cada requisição do
# Laravel abre centenas de arquivos. Sem opcache, o custo de travessia do
# bind mount torna o ciclo de desenvolvimento inviável — chegava a 15s por
# página. `validate_timestamps=1` com revalidação de 2s mantém o recarregar
# ao salvar, cobrando a verificação uma vez a cada dois segundos em vez de
# uma vez por arquivo por requisição.
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
