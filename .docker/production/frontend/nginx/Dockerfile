# docker/frontend-nginx/Dockerfile
FROM nginx:alpine

# Copy frontend-only Nginx template directory.
# The official nginx entrypoint renders all .template files at container start.
COPY ./.docker/common/nginx/frontend-templates/ /etc/nginx/templates/

# No ${VAR}-style placeholders are used in the frontend templates today, but
# this filter is set (matching backend-nginx's pattern) so that if any are
# added later, envsubst only touches explicitly-listed vars and never
# mangles nginx's own runtime variables ($host, $remote_addr, etc).
ENV NGINX_ENVSUBST_TEMPLATE_DIR=/etc/nginx/templates \
    NGINX_ENVSUBST_TEMPLATE_SUFFIX=.template \
    NGINX_ENVSUBST_OUTPUT_DIR=/etc/nginx \
    NGINX_ENVSUBST_FILTER=^$

EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
