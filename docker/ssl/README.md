# SSL Certificates for Production

Generate self-signed certificates for development:

```bash
mkdir -p docker/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout docker/ssl/cuk-admin.key \
  -out docker/ssl/cuk-admin.crt \
  -subj "/C=GA/ST=Ogooue-Lolo/L=Koulamoutou/O=CUK/CN=cuk-admin.local"
```

For production, replace with Let's Encrypt certificates:

```bash
certbot certonly --webroot -w /var/www/html -d votre-domaine.ga
# Then copy to docker/ssl/
```
