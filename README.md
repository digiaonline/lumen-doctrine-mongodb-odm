# Lumen Doctrine MongoDB ODM
[Doctrine MongoDB ODM](http://www.doctrine-project.org/projects/mongodb-odm.html) module for the [Lumen PHP framework](http://lumen.laravel.com/).

## Requirements
- PHP >= 5.5

## Usage
### Install through Composer
Run the following command to install the package:

```sh
composer require nordsoftware/lumen-doctrine-mongodb-odm
```

### Register the Service Provider
Add the following line to `bootstrap/app.php`:

```php
$app->register('Nord\Lumen\Doctrine\ODM\MongoDB\DoctrineServiceProvider');
```

You can now use the `DocumentManager` facade where needed.

### Configure
Create `config/odm.php` into `config` and modify according to your needs. Check base class under `src/Config/Config.php`

Example of `config/mongodb.php`

```php

<?php

return [
    'mapping'         => 'xml',
    'paths'           => [
        base_path('some/Domain/Path/To/Your/Infrastructure/Resources/ODM'),
        base_path('some/App'),
    ],
    'proxy'           => [
        'directory' => storage_path('doctrine/proxies'),
    ],
    'hydrator'        => [
        'directory' => storage_path('doctrine/proxies'),
    ],
];
```

Create `config/mongodb.php` into `config` and modify according to your needs.

Example of `config/mongodb.php`

```php

<?php

return [
    'default' => env('MONGODB_DB_CONNECTION', 'mongodb'),
    'connections' => [
        env('MONGODB_DB_CONNECTION', 'mongodb') => [
            'host' => env('MONGODB_DB_HOST', 'localhost'),
            'database' => env('MONGODB_DB_DATABASE', 'forge'),
            'username' => env('MONGODB_DB_USERNAME', 'forge'),
            'password' => env('MONGODB_DB_PASSWORD', ''),
            'timezone' => env('MONGODB_DB_TIMEZONE', '+00:00'),
        ]
    ],
];
```

### Run Artisan
Run `php artisan` and you should see the new commands in the odm:* namespace section.

## Contributing
Please note the following guidelines before submitting pull requests:
- Use the [PSR-2 coding style](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)

## License
See [LICENSE](LICENSE).
