shell:
	docker-compose run --service-ports web bash

build: clean
	docker-compose build

up:
	docker-compose up

up-d:
	docker-compose up -d

down:
	docker-compose down --remove-orphans

clean: down
	find . -name 'logs/apache2/*' -exec rm -fr {} +
	find . -name 'logs/supervisord/*' -exec rm -fr {} +
