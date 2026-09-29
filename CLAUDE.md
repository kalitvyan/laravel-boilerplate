# CLAUDE.md

Инструкции для Claude Code в этом репозитории. Подробности — в `docs/`:
`architecture.md` — устройство, `conventions.md` — правила и ловушки,
`development.md` — окружение, `adr/` — принятые решения. Перед нетривиальной
правкой прочитай соответствующий раздел. Правила для отдельных частей кода
подгружаются автоматически из `.claude/rules/`.

## Проект

- `apps/api` — Laravel 13, PHP 8.5, Octane + FrankenPHP в worker mode
- `apps/web` — Next.js в роли BFF, React 19, Tailwind 4, shadcn/ui
- `packages/api-client` — типизированный клиент, генерируется из спеки
- `docs/api` — OpenAPI-спецификация, источник истины для контракта
- `infra` — Docker, compose, наблюдаемость, нагрузочные тесты

## Жёсткие правила

- Всё запускается через `make` в контейнерах. Никогда не вызывай на хосте
  `php`, `composer`, `node`, `pnpm`, `npx`.
- Не редактируй сгенерированное: `docs/api/dist/`,
  `packages/api-client/src/schema.d.ts` (только через `make client`),
  `vendor/`, `node_modules/`.
- Не меняй закоммиченные миграции — добавляй новые.
- Не ослабляй правила deptrac и PHPStan, чтобы пройти проверку. Если deptrac
  запрещает связь, сначала проверь, туда ли положен класс.
- В прикладном коде нет `Auth::user()`, `Gate`, `$this->authorize()`.
  Актор — `Principal`, передаётся в команду явным полем.
- Не публикуй: никаких `git push --force`, `make prod-push`, `docker push`.
- Основная ветка — `master`.

## Готовность

Задача не завершена, пока не прошли проверки:

- API — `make fix && make qa`
- Frontend — `make web-check && make web-test`
- Миграции — дополнительно `make fresh` дважды подряд: второй прогон
  проверяет идемпотентность
- Спека — `make client`, сгенерированный клиент коммитится вместе с ней

Показывай вывод проверки, а не пересказ. Если проверка падает — устраняй
причину, а не подавляй сообщение.

## Порядок работы

- Изменение, затрагивающее больше одного слоя или контекста, начинается с
  плана.
- HTTP API — spec-first: сначала `docs/api/paths/<context>/`, затем код,
  затем проверка контрактным тестом.
- Архитектурное решение фиксируется в `docs/adr/` по шаблону
  `0000-template.md`.
- Коммиты — `type(scope): описание`, например `feat(identity): ...`.
- Перед созданием файла прочитай существующий файл того же вида рядом:
  это подгружает правила для этой части кода и показывает принятый образец.

## Команды

```bash
make up / down / restart          # restart нужен после правки .env и compose
make artisan c="route:list"
make composer c="require vendor/pkg"
make test f="--filter=Health"     # фильтр или путь относительно apps/api
make fix                          # Rector + Pint
make qa                           # PSR-4, стиль, Rector, PHPStan, deptrac, тесты
make fresh                        # migrate:fresh --seed
make spec / make client           # бандл спеки / регенерация клиента
make web-check / make web-test
make horizon-restart / relay-restart  # эти процессы не перезагружаются сами
```

`make help` — полный список.

## Архитектура API

Модульный монолит: контексты в `apps/api/src/<Context>/`, каждый из слоёв
`Domain`, `Application`, `Infrastructure`, `Presentation`, `Contract`.

- Зависимости направлены внутрь. Domain и Application не видят
  `Illuminate\*` и Carbon; потребности во фреймворке — порты в Application с
  реализацией в Infrastructure. Провайдеры (`Infrastructure/Provider`) —
  composition root, им можно всё.
- Контекст видит только `Shared` и `Contract` других контекстов. Никаких
  внешних ключей и джойнов между контекстами — только события.
- Запись: контроллер → команда (UUIDv7 создаётся до отправки, команда ничего
  не возвращает) → `CommandBus` → хендлер → репозиторий.
- Чтение: `QueryBus` → read-модель на Query Builder → DTO, мимо агрегатов.
- Карты обработчиков, листенеров, трансляторов, подписчиков и `ProblemMap`
  расширяются только в `register()` провайдера. В `boot()` расширение
  молча не применяется.
- Octane: никакого состояния запроса в singleton; для сервисов на запрос —
  `scoped()`.
