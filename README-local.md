# common

## ddev

ddev start

ddev composer create-project laravel/laravel

ddev launch

ddev describe

> Steps : Install and Trust the Local CA (mkcert)
mkcert -install
ddev poweroff
ddev start

## install packages

ddev composer require pestphp/pest --dev
ddev composer require pestphp/pest-plugin-browser --dev
ddev composer require pestphp/pest-plugin-laravel --dev

ddev composer require larastan/larastan --dev

ddev composer require rector/rector --dev
composer require rector/rector-laravel --dev

ddev composer require laravel/boost --dev

## laravel boost

php artisan boost:install
php artisan boost:update

## formatters

ddev composer format

## artisan

### refresh db

ddev artisan migrate
ddev artisan migrate:refresh
