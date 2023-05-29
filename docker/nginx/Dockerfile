FROM nginx:1.25

COPY sites/laravel.conf /etc/nginx/conf.d/laravel.conf

RUN ln -sf /dev/stdout /var/log/nginx/access.log
RUN ln -sf /dev/stderr /var/log/nginx/error.log
