# Laravel Routes Publisher

A command to replace deprecated `Route::controller()` and `Route::controllers()` with explicit routes.

![what it does](http://s17.postimg.org/x5q8vfrfz/Screen_Shot_2016_03_23_at_12_36_34_PM.png)

In laravel 5.3 implicit controller routes will be removed from the framework, the functionality will likely be extracted into a separate
package, however if you'd like to make the move and start using explicit routes this package will help you.

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

After the command is done, two new files will be generated in your `app/Http` directory:

```
routes.php.generated
routes.php.backup
```

Replace the content of your `routes.php` file with that of `routes.php.generated`, knowing that if anything went wrong a backup
of your original `routes.php`'s content will be available in `routes.php.backup`.

#### This package assumes the following:

- Your `routes.php` doesn't contain any PHP syntax errors.
- Your `routes.php` file is located in `app\Http\routes.php` with the exact name.
- Your `routes.php` files doesn't include any other file using `include` or `require`.
- Your Application namespace is correctly registered in the psr-4 section of `composer.json`.

# Problems?
I tried hard to cover different syntax and file formatting in this package, however if you found any problems while using the
package please [open a new issue](https://github.com/themsaid/laravel-routes-publisher/issues/new).