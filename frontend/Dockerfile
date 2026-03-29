FROM node:22-alpine AS builder
WORKDIR /app

COPY frontend/package.json /app/package.json
RUN npm install --no-fund --no-audit

COPY frontend /app
RUN npm run build

FROM nginx:1.27-alpine
WORKDIR /usr/share/nginx/html
COPY --from=builder /app/dist /usr/share/nginx/html
COPY frontend/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
