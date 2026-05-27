FROM nginx:1.25-alpine

RUN rm /etc/nginx/conf.d/default.conf

COPY conf.d/ /etc/nginx/conf.d/

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
