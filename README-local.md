ddev start

ddev composer create-project laravel/laravel

ddev launch


# install packages

ddev composer require pestphp/pest --dev
ddev composer require pestphp/pest-plugin-browser --dev
ddev composer require pestphp/pest-plugin-laravel --dev

ddev composer require larastan/larastan --dev

ddev composer require rector/rector --dev
composer require rector/rector-laravel --dev

ddev composer require laravel/boost --dev
