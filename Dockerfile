FROM docker.arvancloud.ir/trafex/php-nginx:3.0.0
COPY nginx/default.conf /etc/nginx/conf.d/server.conf
COPY ./public/ /var/www/html/

USER root
RUN apk update && \
    apk add --no-cache php81-mysqli && \
    apk add --no-cache php81-simplexml && \
    apk add --no-cache mysql-client
USER nobody
