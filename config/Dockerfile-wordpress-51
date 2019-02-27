FROM wordpress:5-php7.3

ENV WORDPRESS_VERSION 5.1
ENV WORDPRESS_UPSTREAM_VERSION 5.1
ENV WORDPRESS_SHA1 830eadf0afa15928d7f6856b1b85bf57b8e1f585

# upstream tarballs include ./wordpress/ so this gives us /usr/src/wordpress
RUN curl -o wordpress.tar.gz -sSL https://wordpress.org/wordpress-${WORDPRESS_UPSTREAM_VERSION}.tar.gz \
  && echo "$WORDPRESS_SHA1 *wordpress.tar.gz" | sha1sum -c - \
  && tar -xzf wordpress.tar.gz -C /usr/src/ \
  && rm wordpress.tar.gz \
  && mkdir /usr/src/wordpress/wp-content/uploads \
  && chown -R www-data:www-data /usr/src/wordpress

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
