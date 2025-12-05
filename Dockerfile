FROM debian:bookworm AS webprep
WORKDIR /src
COPY ./ ./
RUN rm -rf web/sites/default/files \
  && rm -rf .git \
  && rm -f .env composer.auth.json deployment.yml \
  && find /src -type f -name '*.pem' -delete

FROM debian:bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
  ca-certificates apt-transport-https lsb-release curl gnupg2 openssl && \
  curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /etc/apt/trusted.gpg.d/sury.gpg && \
  echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
  apt-get update && apt-get install -y --no-install-recommends \
  nginx git unzip patch bash \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-gd php8.3-intl php8.3-bcmath php8.3-soap \
  php8.3-xml php8.3-zip php8.3-mbstring php8.3-tidy php8.3-gmp php8.3-xsl php8.3-curl \
  php8.3-exif php8.3-sockets php8.3-opcache php8.3-readline \
  libicu-dev libxml2-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
  libwebp-dev libxpm-dev libzip-dev zlib1g-dev libxslt1-dev libgmp-dev libssl-dev \
  libtidy-dev libffi-dev libsodium-dev libonig-dev && \
  apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/*

ENV COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_MEMORY_LIMIT=-1 \
  COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/drupal
COPY --from=webprep /src/ /var/www/drupal/

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
  composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader && \
  rm -rf "$COMPOSER_HOME"/cache/* /tmp/*

RUN mkdir -p /run/php && chown -R www-data:www-data /run/php

RUN mkdir -p /run/php /var/www/drupal/web/sites/default/files /tmp && \
  chown -R www-data:www-data /run/php /tmp /var/www/drupal/web/sites/default && \
  chmod -R 775 /var/www/drupal/web/sites/default/files /tmp

RUN mkdir -p /etc/php/8.3/fpm/pool.d && \
  printf '%s\n' \
  '[www]' \
  'user = www-data' \
  'group = www-data' \
  'listen = /run/php/php8.3-fpm.sock' \
  'listen.owner = www-data' \
  'listen.group = www-data' \
  'pm = dynamic' \
  'pm.max_children = 10' \
  'pm.start_servers = 2' \
  'pm.min_spare_servers = 1' \
  'pm.max_spare_servers = 5' \
  'catch_workers_output = yes' \
  'clear_env = no' \
  'env[SITE_ENVIRONMENT] = $SITE_ENVIRONMENT' \
  'env[DATABASE_DATABASE] = $DATABASE_DATABASE' \
  'env[DATABASE_USER] = $DATABASE_USER' \
  'env[DATABASE_PASSWORD] = $DATABASE_PASSWORD' \
  'env[DATABASE_HOSTNAME] = $DATABASE_HOSTNAME' \
  'env[DATABASE_PORT] = $DATABASE_PORT' \
  'env[DATABASE_DRIVER] = $DATABASE_DRIVER' \
  'env[DRUPAL_HASH_SALT] = $DRUPAL_HASH_SALT' \
  'env[DRUPAL_TRUSTED_HOST] = $DRUPAL_TRUSTED_HOST' \
  'env[IS_DDEV_PROJECT] = $IS_DDEV_PROJECT' \
  'env[S3_ACCESS_KEY] = $S3_ACCESS_KEY' \
  'env[S3_SECRET_KEY] = $S3_SECRET_KEY' \
  'env[S3_BUCKET] = $S3_BUCKET' \
  'env[S3_REGION] = $S3_REGION' \
  > /etc/php/8.3/fpm/pool.d/www.conf

RUN printf '%s\n' \
  'expose_php = Off' \
  'memory_limit = 512M' \
  'upload_max_filesize = 64M' \
  'post_max_size = 64M' \
  'max_execution_time = 300' \
  'max_input_time = 300' \
  'max_input_vars = 5000' \
  'session.gc_maxlifetime = 2880' \
  'session.cookie_httponly = 1' \
  'session.cookie_secure = 0' \
  'display_errors = Off' \
  'log_errors = On' \
  'error_log = /proc/self/fd/2' \
  'cgi.fix_pathinfo = 0' \
  > /etc/php/8.3/fpm/conf.d/99-drupal.ini

RUN rm -f /etc/nginx/sites-enabled/default && \
  printf '%s\n' \
  'server {' \
  '  listen 8080;' \
  '  listen 8443 ssl;' \
  '  ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;' \
  '  ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;' \
  '  server_name _;' \
  '  root /var/www/drupal/web;' \
  '  index index.php index.html;' \
  '  client_max_body_size 64m;' \
  '  location / { try_files $uri /index.php?$query_string; }' \
  '  location ~ \.php$ {' \
  '    include snippets/fastcgi-php.conf;' \
  '    fastcgi_pass unix:/run/php/php8.3-fpm.sock;' \
  '    fastcgi_read_timeout 180s;' \
  '  }' \
  '  location ^~ /sites/default/files/ { try_files $uri /index.php?$query_string; }' \
  '  location ~ ^/sites/default/files/.*\.php$ { return 404; }' \
  '  location ~* \.(?:css|js|jpe?g|gif|png|ico|svg|webp|woff2?)$ {' \
  '    expires 7d; add_header Cache-Control "public"; try_files $uri /index.php?$query_string;' \
  '  }' \
  '  location ~ /\. { deny all; }' \
  '  add_header X-Frame-Options SAMEORIGIN always;' \
  '  add_header X-Content-Type-Options nosniff always;' \
  '  add_header Referrer-Policy strict-origin-when-cross-origin always;' \
  '}' > /etc/nginx/sites-enabled/drupal.conf

RUN mkdir -p /etc/ssl/private /etc/ssl/certs && \
  openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
  -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
  -out /etc/ssl/certs/ssl-cert-snakeoil.pem \
  -subj "/C=GB/ST=London/L=London/O=LocalDev/CN=localhost"

EXPOSE 80 8080 443 8443

RUN echo '#!/bin/bash\nset -e\nmkdir -p /run/php\nchown -R www-data:www-data /run/php\nphp-fpm8.3 -D\nsleep 2\nnginx -g "daemon off;"' > /start.sh && chmod +x /start.sh
CMD ["/start.sh"]
