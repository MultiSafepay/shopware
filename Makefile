-include .env
export

# ------------------------------------------------------------------------------------------------------------
## Docker installation commands

.PHONY: update-host
update-host:
	docker-compose exec app mysql -uroot -proot shopware -e "update s_core_shops set host='${APP_SUBDOMAIN}.${EXPOSE_HOST}' where hosts LIKE '%localhost%'"

.PHONY: install
install:
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:refresh
	docker-compose exec --user=www-data app php /var/www/html/bin/console sw:plugin:install --clear-cache --activate MltisafeMultiSafepayPayment

.PHONY: phpcs
phpcs:
	docker-compose exec --user=www-data --workdir /var/www/html/custom/plugins/MltisafeMultiSafepayPayment app composer run-script phpcs

.PHONY: phpcbf
phpcbf:
	docker-compose exec --user=www-data --workdir /var/www/html/custom/plugins/MltisafeMultiSafepayPayment app composer run-script phpcbf
