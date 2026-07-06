# common

## > install packages

```bash
ddev composer require pestphp/pest --dev
ddev composer require pestphp/pest-plugin-browser --dev
ddev composer require pestphp/pest-plugin-laravel --dev

ddev composer require larastan/larastan --dev

ddev composer require rector/rector --dev
ddev composer require rector/rector-laravel --dev

ddev composer require laravel/boost --dev

ddev composer require spatie/laravel-data
ddev composer require spatie/laravel-typescript-transformer
```

## > ddev

```bash
ddev start

ddev composer create-project laravel/laravel

ddev launch

ddev describe
```

> Steps : Install and Trust the Local CA (mkcert)

```
mkcert -install
ddev poweroff
ddev start
```

> DDEV conflicts

```
ddev stop -a
ddev start
ddev stop xxx

# router_http_port: "8080"
# router_https_port: "8443"
host_db_port: "54330"
```

## xdebug + code coverage

```bash
ddev xdebug on
ddev pest --coverage-html public/coverage
ddev composer coverage
```

## formatters

```bash
ddev composer format
```

## > artisan

### refresh db + seed

```bash
ddev artisan migrate
ddev artisan migrate:refresh

ddev artisan db:seed
```

### Spatie Data :: Generate Typescript

```bash
# first time
php artisan typescript:install

php artisan typescript:transform
```

### laravel boost

```bash
php artisan boost:install
php artisan boost:update
```

## > testing

specific test

```bash
ddev pest tests/Feature/Api/Auth/UserTest.php
ddev pest tests/Feature/Api/Auth/UserTest.php --filter="it_registers_a_user_successfully"
```
