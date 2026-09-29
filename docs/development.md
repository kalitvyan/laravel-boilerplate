# Разработка

## Требования

- Docker с Compose v2
- GNU Make
- macOS или Linux (Windows через WSL2)

Локальные PHP, Composer и Node.js не нужны: всё работает в контейнерах.

## Первый запуск

```bash
git clone git@github.com:kalitvyan/laravel-boilerplate.git
cd laravel-boilerplate
make init
```

`make init` создаст файлы окружения, соберёт образы, установит зависимости,
сгенерирует ключ приложения, поднимет стек и применит миграции.

## Адреса сервисов

| Сервис | Адрес | Доступ |
|---|---|---|
| Frontend | http://127.0.0.1:3000 | — |
| API | http://127.0.0.1:8000 | — |
| Horizon | http://127.0.0.1:8000/horizon | в local без пароля |
| Mailpit | http://127.0.0.1:8025 | — |
| RustFS | http://127.0.0.1:9011 | `rustfsadmin` / `rustfsadmin` |
| Grafana | http://127.0.0.1:3030 | — |
| PostgreSQL | `127.0.0.1:5432` | `app` / `app` |

На macOS используйте `127.0.0.1`, а не `localhost`: имя резолвится сначала в
IPv6, где сервисы контейнеров могут быть недоступны.

Порты переопределяются в `infra/docker/compose/.env` — скопируйте туда
`.env.example` и поменяйте нужные. Порт RustFS смещён на 9010, потому что
9000 обычно занят локальным php-fpm.

## Команды

```bash
make help                          # весь список
make up / make down / make restart # управление стеком
make logs s=api                    # логи сервиса
make artisan c="route:list"        # artisan
make composer c="require foo/bar"  # composer
make psql                          # psql в базу разработки
```

`make restart` пересоздаёт контейнеры — он нужен после изменений в `.env` и
в compose-файлах, потому что переменные читаются при старте.

### Проверки

```bash
make fix       # Rector и Pint: автоматические исправления
make qa        # все проверки API: PSR-4, стиль, PHPStan, deptrac, тесты
make web-check # типы и линтер фронтенда
make web-test  # тесты фронтенда
make check     # всё сразу
```

Перед коммитом достаточно `make fix && make qa`. Перед завершением крупного
изменения полезно прогнать `make fresh` — база разработки накапливает
состояние, которого не будет на чистом развёртывании.

### Фронтенд

```bash
make web-logs                       # логи Next.js
make web-shell                      # оболочка в контейнере
make shadcn c="button input"        # добавить компоненты shadcn/ui
make pnpm c="install"               # pnpm вне стека, например на свежем клоне
```

### Контракт API

```bash
make spec        # линт и сборка бандла
make client      # регенерация типизированного клиента
make spec-docs   # HTML-документация в docs/api/dist/index.html
```

Сгенерированный клиент коммитится: так типы доступны сразу после клонирования,
а расхождение со спецификацией ловит CI.

## Отладка

### Xdebug

```bash
XDEBUG_MODE=debug make restart
```

В IDE понадобится сопоставление путей `apps/api` → `/app`. Точки останова в
коде бутстрапа срабатывают только при старте воркера, в коде запроса — на
каждый запрос.

### Наблюдаемость

Локальный стек включает `otel-lgtm` — Grafana, Tempo, Prometheus и Loki в
одном контейнере. В проде приложение только отправляет данные по OTLP; как
подключить приёмник, описано в [эксплуатации](operations.md).

Готовый дашборд лежит в `infra/observability/grafana/dashboards/api.json` и
загружается автоматически: Grafana → Dashboards → папка «Laravel Boilerplate».
Правки через интерфейс не сохраняются — дашборд редактируется в репозитории.

Каждый ответ содержит заголовок `X-Request-Id` — это идентификатор трассы.
Откройте Grafana → Explore → Tempo → TraceQL и вставьте его в поиск. В трассе
видны спан HTTP-запроса, вложенный спан команды и, если событие уехало в
очередь, спан обработки в консьюмере.

Метрики смотрите через Explore → Prometheus. Гейджи (лаг outbox, глубина
очередей) обновляет команда `telemetry:collect` — планировщик запускает её
раз в минуту, вручную можно вызвать через `make artisan c="telemetry:collect"`.

Методика нагрузочных замеров и ориентиры по производительности — в
[эксплуатации](operations.md#производительность).

### Логи

Логи структурированные, в stdout, с `trace_id` и `span_id`:

```bash
make logs s=api | grep '"level_name":"ERROR"'
```

### Очереди

```bash
make artisan c="queue:failed"      # упавшие джобы
make horizon-restart               # перезапуск после изменения кода
make relay-restart                 # перезапуск relay
```

Horizon и relay не следят за файлами, в отличие от API, поэтому после правок
их нужно перезапускать вручную.

## Добавление контекста

1. Создать `src/<Context>/{Domain,Application,Infrastructure,Presentation,Contract}`.
2. Добавить схему в миграцию `create_context_schemas`.
3. Создать провайдер в `Infrastructure/Provider` и зарегистрировать его в
   `bootstrap/providers.php`.
4. Добавить контекст в `deptrac.contexts.yaml` — слой с исключением
   собственного `Contract` и правило зависимости от `Shared` и `Contracts`.
5. Описать операции в `docs/api/paths/<context>/`.

Регистрация обработчиков, листенеров, трансляторов и записей `ProblemMap`
выполняется в методе `register()` провайдера. Подробности и причина — в
[соглашениях](conventions.md).

## Типичные проблемы

**Порт занят.** Переопределите его в `infra/docker/compose/.env`.

**Изменения в коде не применяются.** API следит за файлами и перезагружает
воркеры сам; Horizon, scheduler и relay — нет.

**Ошибки прав в контейнере Node.** UID хоста отсутствует в образе,
для этого генерируется `infra/docker/node/passwd`. Файл создаётся целью
Makefile автоматически; если каталог с таким именем создал Docker,
удалите его и повторите `make up`.

**Тесты падают на несуществующих таблицах.** `RefreshDatabase` применяет
миграции к тестовой базе автоматически, но если миграции сломаны — проверьте
`make fresh`.
