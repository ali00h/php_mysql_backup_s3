FROM composer:2.5 AS builder
COPY ./public/ /var/www/html/
WORKDIR /var/www/html/
RUN composer install

FROM trafex/php-nginx:3.0.0

COPY nginx/default.conf /etc/nginx/conf.d/server.conf
COPY --from=builder /var/www/html/ /var/www/html/
ENV MACHINE_TYPE=docker
USER root
RUN apk update && \
    apk add --update busybox-openrc && \
    apk add --update busybox-suid && \
    apk add --no-cache php81-mysqli && \
    apk add --no-cache php81-simplexml && \
    apk add --no-cache mysql-client

RUN crontab -l | { cat; echo "* * * * * php /var/www/html/runJob.php > /dev/null 2>&1"; } | crontab -    
CMD ["/usr/sbin/crond", "-f", "-d", "0"]
USER nobody

