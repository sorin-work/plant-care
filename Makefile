.PHONY: help install lint fix stan test check db demo up down

help: ## Show this help
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "}; {printf "  %-10s %s\n", $$1, $$2}'

install: ## Install PHP and JavaScript dependencies
	composer install
	php bin/console importmap:install

lint: ## Check coding style and templates
	vendor/bin/php-cs-fixer fix --dry-run --diff
	php bin/console lint:twig templates
	php bin/console lint:yaml config

fix: ## Fix coding style
	vendor/bin/php-cs-fixer fix

stan: ## Static analysis
	php bin/console cache:warmup --env=dev
	vendor/bin/phpstan analyse --no-progress

test: ## Run the test suite (SQLite, no database server needed)
	vendor/bin/phpunit

check: lint stan test ## Everything CI runs

db: ## Create the database, run migrations and import the species catalog
	php bin/console doctrine:database:create --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console app:species:import

demo: db ## Load the demo account
	php bin/console app:demo:seed

up: ## Start the Docker stack
	docker compose up -d --build

down: ## Stop the Docker stack
	docker compose down
