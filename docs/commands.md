# Локальные команды

Все команды запускаются из корня репозитория. Перед первым запуском создайте `.env` из `.env.example`.

## Инициализация

| Команда | Назначение |
| --- | --- |
| `task init` | Полностью подготовить проект после чистого клонирования, включая импорт автомобилей |
| `task up` | Собрать и запустить основные Docker-сервисы |
| `task stop` | Остановить сервисы проекта |
| `task ps` | Показать состояние контейнеров |
| `task logs -- app` | Просматривать логи указанного сервиса |

## Laravel и база данных

| Команда | Назначение |
| --- | --- |
| `task artisan -- route:list` | Выполнить Artisan-команду в PHP-контейнере |
| `task composer -- require vendor/package` | Выполнить Composer в PHP-контейнере |
| `task bash` | Открыть shell PHP-контейнера |
| `task migrate` | Применить миграции к основной dev БД |
| `task seed` | Запустить сидеры основной dev БД |
| `task fresh` | Пересоздать основную dev БД и применить сиды |
| `task storage-link` | Создать публичную ссылку на storage |
| `task import` | Клонировать/обновить исходные JSON, скопировать JPG в public и импортировать автомобили |

`task fresh` удаляет данные только из основной dev БД.

`task init` требует доступ к `CARS_IMPORT_REPOSITORY`: после миграций он запускает `task import`, поэтому сайт сразу получает автомобили и изображения для голосования.

`task import` использует `CARS_IMPORT_REPOSITORY`, `CARS_IMPORT_SOURCE_PATH`, `CARS_IMPORT_BATCH_SIZE`, `CARS_IMPORT_WORK_PATH`, `CARS_IMPORT_IMAGES_PATH` и `CARS_PUBLIC_IMAGES_PATH`. Команда обрабатывает JSON чанками, пропускает некорректные записи и публикует проверенные JPEG-файлы в `public/images/cars/{auction_item_id}/{image_filename}`.

## Frontend

| Команда | Назначение |
| --- | --- |
| `task npm-install` | Установить frontend-зависимости в Node 22-контейнере |
| `task build` | Собрать production-asset'ы Vite в `public/build` |
| `task dev` | Запустить Vite dev-сервер на `VITE_PORT` (по умолчанию `5174`) |

## Проверки

| Команда | Назначение |
| --- | --- |
| `task test` | Запустить PHPUnit против изолированной БД `mysql-test` |
| `task pint` | Проверить форматирование PHP |
| `task pint-fix` | Исправить форматирование PHP |
| `task ide-helper` | Сгенерировать IDE helper, PHPDoc Eloquent-моделей и PhpStorm meta |

Полный перечень команд и их описание: `task --list-all`.
