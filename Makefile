#!make

up:
	docker run -dp 4444:4444 selenium/standalone-chrome
	php -S localhost:8080 -t tests/fixtures/www &> /dev/null
	composer install

test: ## Run filtered tests
	php bin/behat $(subst +,-, $(filter-out $@,$(MAKECMDGOALS)))
	php bin/atoum
	php bin/yaml-lint .
	php bin/rector --dry-run
