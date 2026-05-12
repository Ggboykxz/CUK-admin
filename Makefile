.PHONY: help install prod dev test migrate backup restore logs

help:
	@echo "CUK-Admin - Commandes"
	@echo ""
	@echo "  make install     Installer les dépendances Composer"
	@echo "  make dev         Lancer en développement (php -S)"
	@echo "  make prod        Lancer en production (Docker complet)"
	@echo "  make test        Exécuter les tests"
	@echo "  make migrate     Exécuter les migrations BDD"
	@echo "  make backup      Créer une sauvegarde"
	@echo "  make logs        Voir les logs"

install:
	composer install --no-interaction --prefer-dist

dev:
	php -S localhost:8000

prod:
	docker compose -f docker-compose.prod.yml up -d --build

prod-down:
	docker compose -f docker-compose.prod.yml down

prod-logs:
	docker compose -f docker-compose.prod.yml logs -f

prod-ssl:
	mkdir -p docker/ssl && openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
		-keyout docker/ssl/cuk-admin.key -out docker/ssl/cuk-admin.crt \
		-subj "/C=GA/ST=Ogooue-Lolo/L=Koulamoutou/O=CUK/CN=cuk-admin.local"

test:
	./vendor/bin/phpunit

migrate:
	php database/migrate.php migrate

rollback:
	php database/migrate.php rollback $(n)

backup:
	php api/backup.php?action=create

logs:
	tail -f runtime/logs/*.log

maintenance-on:
	export MAINTENANCE_MODE=true

maintenance-off:
	export MAINTENANCE_MODE=false

clean:
	rm -rf runtime/logs/*.log runtime/cache/*
	docker compose -f docker-compose.prod.yml down -v
