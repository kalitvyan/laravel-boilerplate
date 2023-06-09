## Main stack
- PHP `8.2.6`
- XDebug `latest`
- Composer `latest`
- Laravel `10.12.0`
- Nginx `1.25.0`
- PostgreSQL `15.3`
- Redis `7.0.11`

## Requirements
- Ubuntu-based distribution, recommends latest LTS version
- Docker < `24`
- Docker compose < `2.3`

## Run Development environment
```bash
# Creates config for dev environment.
make env-dev

# Build dev environment.
make build

# Start dev environment.
make start

# Installs composer dependencies.
make composer-install

# Runs migrations.
make migrate

# Runs seeds.
make seed

# Available commands.
make help
```

### Frontend
```bash
# Open Laravel container.
make shell

# Install js packages and dependencies.
yarn install

# Build assets (it's works for prod too).
yarn build

# Runs dev mode.
yarn dev
```
Welcome: http://localhost.

### Backend
Go to: http://localhost/admin/login
```
Login: admin@localhost
Password: password
```

### Api
Boilerplate uses GraphQL for API implementation.

GraphQL endpoint: http://localhost/graphql

GraphQL Playground: http://localhost/graphiql

Schema: `graphql/schema.graphql`

## XDebug
In order to enable Xdebug
#### PHPStorm:
- Start listening for PHP Debug connection.
- Go to `Setting` -> `PHP` -> `Debug`. Click `Validate` add select `Local Web Server or Shared Folder`. Add `public` in `Path to create validation script` and run `Validation`. Everything must be success.
- Also disable `Break at first line in PHP scripts` in Debug section.
- Go to `Settings` -> `PHP` -> `Server`. Use path mappings for default server `0.0.0.0`. In Project File section specified the Absolute path on the server - `/var/www/html` which corresponds to project root.
- Apply remote configuration that stored in `.run/remote.run.xml`.
- Set Breakpoints.
- Start debugging.

#### VSCode:
- Install and enable [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug), [Docker](https://marketplace.visualstudio.com/items?itemName=ms-azuretools.vscode-docker) and [Remote Explorer](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-containers) extensions.
- Just set Breakpoints and run Debugging (F5).
- VSCode configuration in `.vscode/launch.json`.
  
That's all.

In both cases [XDebug helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc) need to be installed and enabled.

Customize the configuration you may in `docker/php/dev/xdebug.ini`. Available only in `dev` and `test` environments.

## Nginx

## PostgreSQL
```bash
# Run shell in container.
make shell-pgsql

# Show postgresql logs.
make logs-pgsql
```

## Redis
- Customize the configuration you may in `docker/redis/redis.conf`

## Packages
- [Livewire](https://github.com/livewire/livewire)
- [Filament](https://github.com/filamentphp/filament)
- [Lighthouse](https://github.com/nuwave/lighthouse)
- [Laravel GraphiQL](https://github.com/mll-lab/laravel-graphiql)
- [Debugbar for Laravel](https://github.com/barryvdh/laravel-debugbar)
- [IDE Helper for Laravel](https://github.com/barryvdh/laravel-ide-helper)

## License
[The MIT License (MIT)](LICENSE)
