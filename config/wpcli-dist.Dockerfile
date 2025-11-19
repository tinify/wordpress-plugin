FROM wpcli/wp-cli:2-php8.2-alpine

RUN apk add --no-cache git zip unzip

RUN git config --global url."https://github.com/".insteadOf git@github.com: \
 && git config --global url."https://".insteadOf git://

ENV WP_CLI_PACKAGES_DIR=/var/www/.wp-cli

RUN wp package install wp-cli/dist-archive-command:@stable

WORKDIR /var/www/html
ENTRYPOINT ["wp"]