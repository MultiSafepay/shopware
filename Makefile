-include .env
export

# ------------------------------------------------------------------------------------------------------------
## Docker installation commands

.PHONY: update-host
update-host:
	docker-compose exec app mysql -uroot -proot shopware -e "update s_core_shops set host='${APP_SUBDOMAIN}.${EXPOSE_HOST}' where hosts LIKE '%localhost%'"

.PHONY: install
install:
	docker-compose exec --user=www-data --workdir /var/www/html/custom/plugins/MltisafeMultiSafepayPayment app composer install
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:refresh
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:install MltisafeMultiSafepayPayment
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:config:set --shopId 1 MltisafeMultiSafepayPayment msp_api_key ${MULTISAFEPAY_API_KEY}
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:config:set --shopId 1 MltisafeMultiSafepayPayment msp_environment 0
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:activate --clear-cache MltisafeMultiSafepayPayment
	# Located at the end of the install process, to avoid that restarting of PHP-FPM service be in conflict with the plugin installation
	make update-xdebug

.PHONY: update-xdebug
# Making PHP version variable local
PHP_VERSION :=
# Getting the PHP version from the container
php-version:
 $(eval PHP_VERSION := $(shell docker-compose exec -T app php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;'))
# Defining the path to the xdebug ini file
XDEBUG_INI_PATH := /etc/php/$(PHP_VERSION)/fpm/conf.d/20-xdebug.ini
# Disable local xdebug logging to avoid over-populating the logs
update-xdebug: php-version
	docker-compose exec --user=root app bash -c 'if ! grep -q "xdebug.log_level = 0" $(XDEBUG_INI_PATH); then echo -e "\nxdebug.log_level = 0" >> $(XDEBUG_INI_PATH); fi'
	# Restarting PHP-FPM service to apply the changes
	docker-compose exec --user=root app service php${PHP_VERSION}-fpm restart

.PHONY: phpcs
phpcs:
	docker-compose exec --user=www-data --workdir /var/www/html/custom/plugins/MltisafeMultiSafepayPayment app composer run-script phpcs

.PHONY: phpcbf
phpcbf:
	docker-compose exec --user=www-data --workdir /var/www/html/custom/plugins/MltisafeMultiSafepayPayment app composer run-script phpcbf
