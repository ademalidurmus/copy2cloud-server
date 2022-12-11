env:
	@cp .env.dist .env
build:
	@docker compose up -d --build
up:
	@docker compose up -d
down:
	@docker compose down
restart:
	@docker compose restart
ps:
	@docker compose ps
php:
	@docker exec -it "copy2cloud-php" /bin/bash
webserver:
	@docker exec -it "copy2cloud-webserver" /bin/sh
redis:
	@docker exec -it "copy2cloud-redis" /bin/sh  -c "redis-cli"
test:
	@docker exec -it "copy2cloud-php" /bin/bash  -c "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html ./tests/.coverage"
# 	@docker exec -it "copy2cloud-php" /bin/bash  -c "./vendor/bin/phpunit"
serve-test:
# 	@docker exec -it "copy2cloud-php" /bin/bash  -c "XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html ./tests/.coverage"
	php -S 10.0.0.13:8000 -t ./tests/c
update:
	@docker exec -it "copy2cloud-php" /bin/bash  -c "composer update -vvv"
clean:
	@rm .env
