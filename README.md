# Laravel Boilerplate

> 🚧 Work in progress

Production-oriented API boilerplate built around **DDD**, **Clean Architecture**
and **event-driven design**.

**Repository:** https://github.com/kalitvyan/laravel-boilerplate

## Stack

| Layer | Technology |
|---|---|
| API | PHP 8.5, Laravel 13, Octane + FrankenPHP (worker mode) |
| Architecture | Modular monolith, bounded contexts, CQRS-lite, transactional outbox |
| Auth | Laravel Sanctum (opaque tokens) behind ports, Next.js as BFF |
| Database | PostgreSQL 18 (schema per bounded context) |
| Redis | `redis-state` (queues, Horizon, locks, rate limits) and `redis-cache` |
| Queues | Laravel Horizon |
| Storage | S3-compatible (RustFS locally, any S3 provider in production) |
| API contract | OpenAPI (spec-first), RFC 9457 Problem Details |
| Observability | OpenTelemetry, Grafana LGTM locally |
| Frontend | Next.js, React 19, Tailwind CSS 4 *(planned)* |
| Quality | Pest, PHPStan (level max), deptrac, Rector, Pint |

## Requirements

- Docker with Compose v2
- GNU Make
- macOS or Linux (Windows via WSL2)

No local PHP, Composer or Node.js is required.

## Quick start

```bash
git clone git@github.com:kalitvyan/laravel-boilerplate.git
cd laravel-boilerplate
make init
```

## Local services

| Service | URL | Credentials |
|---|---|---|
| API | http://localhost:8000 | — |
| Health (liveness / readiness) | http://localhost:8000/health/live, `/health/ready` | — |
| Horizon | http://localhost:8000/horizon | — |
| Mailpit | http://localhost:8025 | — |
| RustFS console | http://localhost:9001 | `rustfsadmin` / `rustfsadmin` |
| Grafana | http://localhost:3030 | — |
| PostgreSQL | `localhost:5432` | `app` / `app` |

Host ports can be overridden: `API_PORT=8080 make up`
(also `POSTGRES_PORT`, `MAILPIT_UI_PORT`, `RUSTFS_PORT`, `RUSTFS_CONSOLE_PORT`, `GRAFANA_PORT`).

## Common commands

```bash
make help                          # list all commands
make up / make down                # start / stop the stack
make logs s=api                    # follow service logs
make artisan c="route:list"        # run artisan
make composer c="require foo/bar"  # run composer
make test                          # run tests
make qa                            # lint, rector, phpstan, deptrac, tests
XDEBUG_MODE=debug make up          # start with Xdebug enabled
```

## Repository layout

```
apps/api        Laravel API (bounded contexts in src/)
apps/web        Next.js frontend (planned)
packages/       Shared JS packages, generated API client (planned)
infra/docker    Dockerfiles and Compose stack
docs/           Architecture notes and ADRs
```

## Documentation

- [Architecture](docs/architecture.md)
- [Architecture Decision Records](docs/adr)

## Security

See [SECURITY.md](SECURITY.md).

## License

[MIT](https://opensource.org/licenses/MIT)
