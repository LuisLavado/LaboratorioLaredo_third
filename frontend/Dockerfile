FROM node:20-alpine AS builder

WORKDIR /app
COPY package.json ./
RUN npm install
COPY . .

# Crear .env.production con las variables de build
ARG VITE_API_URL
ARG VITE_BACKEND_URL
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT
ARG VITE_REVERB_SCHEME
ARG VITE_REVERB_APP_KEY

# Si existe .env.production, usarlo; sino, usar las variables ARG
RUN if [ ! -f .env.production ]; then \
    echo "VITE_API_URL=${VITE_API_URL}" > .env.production && \
    echo "VITE_BACKEND_URL=${VITE_BACKEND_URL}" >> .env.production && \
    echo "VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}" >> .env.production && \
    echo "VITE_REVERB_HOST=${VITE_REVERB_HOST}" >> .env.production && \
    echo "VITE_REVERB_PORT=${VITE_REVERB_PORT}" >> .env.production && \
    echo "VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}" >> .env.production; \
fi

RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY <<'EOF' /etc/nginx/conf.d/default.conf
server {
    listen 80;
    server_name _;
    root /usr/share/nginx/html;
    index index.html;
    
    # Habilitar gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # Cache para assets estáticos
    location /assets/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
    
    # SPA fallback para todas las demás rutas
    location / {
        try_files $uri $uri/ /index.html;
    }
}
EOF
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
