FROM php:8.3-cli-alpine

RUN apk add --no-cache postgresql-dev \
  && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app

COPY backend /app/backend

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "backend/public/index.php"]
