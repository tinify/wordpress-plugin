FROM wordpress:4-php7.2

ENV WORDPRESS_VERSION 4.9
ENV WORDPRESS_UPSTREAM_VERSION 4.9.7
ENV WORDPRESS_SHA1 7bf349133750618e388e7a447bc9cdc405967b7d

# upstream tarballs include ./wordpress/ so this gives us /usr/src/wordpress
RUN curl -o wordpress.tar.gz -sSL https://wordpress.org/wordpress-${WORDPRESS_UPSTREAM_VERSION}.tar.gz \
  && echo "$WORDPRESS_SHA1 *wordpress.tar.gz" | sha1sum -c - \
  && tar -xzf wordpress.tar.gz -C /usr/src/ \
  && rm wordpress.tar.gz \
  && mkdir /usr/src/wordpress/wp-content/uploads \
  && chown -R www-data:www-data /usr/src/wordpress

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
