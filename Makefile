
# Запуск всех сервисов в фоновом режиме
up:
	docker compose up -d

# Остановка всех сервисов
stop:
	docker compose stop

# Остановка всех сервисов с удалением
down:
	docker compose down

# Перезапуск всех сервисов
restart: down up

# Просмотр логов всех сервисов
logs:
	docker compose logs -f

# Пересборка образов и перезапуск сервисов
build:
	docker compose up --build -d

# Остановка и удаление всех контейнеров, томов и сетей, созданных Docker Compose
clean:
	docker compose down -v

exec:
	docker exec -it exec php-dropfiles sh

# Перезапуск конкретного сервиса
restart-%:
	docker compose restart $*

# Просмотр логов конкретного сервиса
logs-%:
	docker compose logs -f $*