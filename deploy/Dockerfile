FROM richarvey/nginx-php-fpm:1.9.1

MAINTAINER Battery Chain Team <saad.haroon@coeus-solutions.de>

# EXPOSE 443 80

# Environment variable defaults
ENV WEBROOT="/var/www/html/public"
ENV APP_ENV=prod
ENV ERRORS=1
ENV SKIP_COMPOSER=1
ENV SKIP_CHOWN=1
ENV RUN_SCRIPTS=1

RUN apk update && \
      apk add imagemagick python ghostscript-fonts ghostscript py-pip groff && \
  pip install awscli

RUN set -ex \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS imagemagick-dev libtool \
    && export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS" \
    && pecl install imagick-3.4.3 \
    && docker-php-ext-enable imagick \
    && apk add --no-cache --virtual .imagick-runtime-deps imagemagick \
    && apk del .phpize-deps

WORKDIR "/var/www/html"
#Copy code
COPY . /var/www/html/
RUN chown -R nginx:nginx /var/www/html

# Copy all the workers.
COPY deploy/workers /sbin/workers
RUN chmod -R +x /sbin/workers/


CMD ["/start.sh"]
