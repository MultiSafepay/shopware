FROM dockware/dev:5.7.11

USER root

# Update package lists, install locales, generate en_US.UTF-8 locale
RUN apt-get update && \
    apt-get install -y locales && \
    locale-gen en_US.UTF-8 && \
    rm -rf /var/lib/apt/lists/*

# Set locale as default
ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US:en \
    LC_ALL=en_US.UTF-8

COPY docker/entrypoint.sh /entrypoint-multisafepay.sh

RUN git clone https://github.com/vishnubob/wait-for-it.git /wait-for-it/
RUN chmod +x /entrypoint-multisafepay.sh
RUN { \
		echo '\tSetEnvIf X-Forwarded-Proto https HTTPS=on'; \
		echo '\tSetEnvIf X-Forwarded-Host ^(.+) HTTP_X_FORWARDED_HOST=$1'; \
		echo '\tRequestHeader set Host %{HTTP_X_FORWARDED_HOST}e env=HTTP_X_FORWARDED_HOST'; \
        } | tee "/etc/apache2/conf-available/docker-php.conf" \
	&& a2enconf docker-php && a2enmod headers

WORKDIR /var/www/html/custom/plugins
RUN git clone https://github.com/shopwareLabs/SwagBackendOrder.git