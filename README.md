# Laravel Routes Publisher

A command to replace deprecated `Route::controller()` and `Route::controllers()` with explicit routes.

# Installation

#### Step 1:

```
composer require themsaid/laravel-routes-publisher
```

#### Step 2:

Include the following command in your `$commands` attribute of `app/Console/Kernel.php`:

 ```
 \Themsaid\RoutesPublisher\RoutesPublisherCommand::class
 ```

# Usage

Run the following command:

```
php artisan themsaid:publishRoutes
```

After the command is done, 2 new files will be generated in your `app/Http` directory

```
routes.php.generated
routes.php.backup
```

Replace the content of your `routes.php` file with this of `routes.php.generated`, knowing that if anything went wrong a backup
of your original `routes.php`'s content will be available in `routes.php.backup`.

### This package assumes the following:

- Your `routes.php` doesn't contain any PHP syntax errors.
- Your `routes.php` file is located in `app\Http\routes.php` with the exact name.
- Your `routes.php` files doesn't include any other file using `include` or `require`.
- Your Application namespace is correctly registered in the psr-4 section of `composer.json`.
