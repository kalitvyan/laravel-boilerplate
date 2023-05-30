FROM nginx:1.25.0-alpine

# Set main params.
ARG BUILD_ENV=dev
ENV ENV=$BUILD_ENV

# Put nginx config.
COPY ./$BUILD_ENV/nginx.conf /etc/nginx/conf.d/default.conf

# Links to logs and cleanup.
RUN ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    rm -rf /etc/nginx/conf.d/*

# Install openssl and etc tools, cleanup.
RUN apk update && \
    apk upgrade && \
    apk add --no-cache bash && \
    apk add --no-cache curl && \
    apk add --no-cache openssl && \
    rm -rf /var/cache/apk/*

# Create dir for certificates.
RUN mkdir -p /etc/nginx/certificates

# Generate certificates.
# TODO: create a script thats generate ssl certificates.
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/nginx/certificates/key.pem -out /etc/nginx/certificates/cert.pem -subj "/C=AT/ST=Tomsk/L=Tomsk/O=Security/OU=Development/CN=localhost"
