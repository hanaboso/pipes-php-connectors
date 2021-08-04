.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/connectors

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env.dist | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \


docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
docker-up-force: .env .lo0-up
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env .lo0-down
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install

composer-update:
	$(DE) composer update
	$(DE) composer normalize

clear-cache:
	$(DE) rm -rf var/log
	$(DE) tests/bin/console cache:clear --env=test
	$(DE) tests/bin/console cache:warmup --env=test

# App
init-dev: docker-up-force composer-install

phpcodesniffer:
	$(DE) vendor/bin/phpcs --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpunit:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors tests/Unit

phpintegration:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors tests/Integration

phpcontroller:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors tests/Controller

phpcoverage:
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --coverage-html var/coverage --whitelist src --exclude-group live tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -e live

test: docker-up-force composer-install fasttest

fasttest: phpcodesniffer clear-cache phpstan phpunit phpintegration phpcontroller phpcoverage-ci
