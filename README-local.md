# common

## ddev

```bash
ddev start

ddev composer create-project laravel/laravel

ddev launch

ddev describe
```

> Steps : Install and Trust the Local CA (mkcert)
mkcert -install
ddev poweroff
ddev start

## install packages

```bash
ddev composer require pestphp/pest --dev
ddev composer require pestphp/pest-plugin-browser --dev
ddev composer require pestphp/pest-plugin-laravel --dev

ddev composer require larastan/larastan --dev

ddev composer require rector/rector --dev
composer require rector/rector-laravel --dev

ddev composer require laravel/boost --dev
```

## laravel boost

```bash
php artisan boost:install
php artisan boost:update
```

## formatters

```bash
ddev composer format
```

## artisan

### refresh db

```bash
ddev artisan migrate
ddev artisan migrate:refresh
```

## xdebug + code coverage

```bash
ddev xdebug on
ddev pest --coverage-html public/coverage
ddev composer coverage
```
