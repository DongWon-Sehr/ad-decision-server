shell:
	docker-compose run --service-ports web bash

shell-redis:
	docker-compose exec redis redis-cli

shell-mysql:
	docker-compose exec mysql mysql -uroot -proot

build: clean
	docker-compose build

up:
	docker-compose up

up-d:
	docker-compose up -d

down:
	docker-compose down --remove-orphans

clean: down
	find . -name 'access_log' -exec rm -f {} +
	find . -name 'error_log' -exec rm -f {} +
	find . -name '*~' -exec rm -f {} +
	find . -name 'logs/apache2/*' -exec rm -fr {} +
	find . -name 'logs/supervisord/*' -exec rm -fr {} +
