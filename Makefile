build:
	@docker-compose up -d --build
up:
	@docker-compose up -d
down:
	@docker-compose down
restart:
	@docker-compose restart
ps:
	@docker-compose ps
php:
	@docker exec -it "copy2cloud-php" /bin/bash
webserver:
	@docker exec -it "copy2cloud-webserver" /bin/sh
redis:
	@docker exec -it "copy2cloud-redis" /bin/sh  -c "redis-cli"
test:
	@docker exec -it "copy2cloud-php" /bin/bash  -c "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html ./tests/.coverage"
# 	@docker exec -it "copy2cloud-php" /bin/bash  -c "./vendor/bin/phpunit"
update:
	@docker exec -it "copy2cloud-php" /bin/bash  -c "composer update -vvv"

