FROM php:8.2-alpine

ENV APP_USER=user
ENV APP_GROUP=user

WORKDIR /app
COPY . /app

RUN apk add --no-cache linux-headers make
RUN docker-php-ext-install pcntl bcmath opcache sockets

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN addgroup -S $APP_GROUP && adduser -S $APP_USER -G $APP_GROUP

RUN chown -R $APP_USER:$APP_GROUP .

USER $APP_USER

RUN composer install

CMD ["make", "test-jit"]
