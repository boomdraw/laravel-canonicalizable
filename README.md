# Laravel Canonicalizable

Canonicalizable trait for Eloquent model

[![Build Status](https://img.shields.io/scrutinizer/build/g/boomdraw/laravel-canonicalizable.svg?style=flat-square)](https://scrutinizer-ci.com/g/boomdraw/laravel-canonicalizable)
[![StyleCI](https://github.styleci.io/repos/226422261/shield?branch=master)](https://github.styleci.io/repos/226422261)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/boomdraw/laravel-canonicalizable.svg?style=flat-square)](https://scrutinizer-ci.com/g/boomdraw/laravel-canonicalizable)
[![Quality Score](https://img.shields.io/scrutinizer/g/boomdraw/laravel-canonicalizable.svg?style=flat-square)](https://scrutinizer-ci.com/g/boomdraw/laravel-canonicalizable)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/boomdraw/laravel-canonicalizable?style=flat-square)](https://packagist.org/packages/boomdraw/laravel-canonicalizable)
[![Total Downloads](https://img.shields.io/packagist/dt/boomdraw/laravel-canonicalizable.svg?style=flat-square)](https://packagist.org/packages/boomdraw/laravel-canonicalizable)
[![PHP Version](https://img.shields.io/packagist/php-v/boomdraw/laravel-canonicalizable?style=flat-square)](https://packagist.org/packages/boomdraw/laravel-canonicalizable)
[![License](https://img.shields.io/packagist/l/boomdraw/laravel-canonicalizable?style=flat-square?style=flat-square)](https://packagist.org/packages/boomdraw/laravel-canonicalizable)

This package provides a trait that will generate a canonicalized field when saving any Eloquent model.

```php
$model = new EloquentModel();
$model->name = 'HeLlO WoRLd';
$model->save();

$model->name_canonical === 'hello world';
```

## Installation

Via Composer

``` bash
$ composer require boomdraw/laravel-canonicalizable
```

### Lumen

Uncomment the following line in the bootstrap file:

```php
// bootstrap/app.php:
$app->withEloquent();
$app->withFacades();
```

Register `CanonicalizerServiceProvider`

```php
// bootstrap/app.php:
$app->register(Boomdraw\Canonicalizer\CanonicalizerServiceProvider::class);
```

## Usage

Your Eloquent models should use the `Boomdraw\Canonicalizable\HasCanonical` trait,
the `Boomdraw\Canonicalizable\CanonicalFieldsCollection` and the `Boomdraw\Canonicalizable\CanonicalField` classes.

The trait contains an abstract method `getCanonicalFields()` that you must implement yourself. 

Your models' migrations should have a field to save the canonicalized value.

Here's an example of how to implement the trait:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Boomdraw\Canonicalizable\HasCanonical;
use Boomdraw\Canonicalizable\CanonicalField;
use Boomdraw\Canonicalizable\CanonicalFieldsCollection;

class YourEloquentModel extends Model
{
    use HasCanonical;
    
    /**
     * Get the options for generating the canonical.
     */
    public function getCanonicalFields(): CanonicalFieldsCollection
    {
        return CanonicalFieldsCollection::create()
            ->addField(
                CanonicalField::create()
                    ->from('name')
            )->addField(
                CanonicalField::create()
                    ->from('email')
                    ->type('email')
                    ->to('canonicalized_email')
                    ->doNotGenerateOnUpdate()
            );
    }
}
```

With its migration:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateYourEloquentModelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('your_eloquent_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_canonicalized'); // Field name same as your `to`
            $table->string('email');
            $table->string('canonicalized_email'); // Field name same as your `to`
            $table->timestamps();
        });
    }
}
```

By default, the package will generate non-unique canonicalized fields. You can disallow duplicates by calling
`disallowDuplicate()`.

```php
public function getCanonicalFields(): CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->disallowDuplicate()
        );
}
```

The canonicalized uniqueness will be checked even if it has been set manually and `doNotGenerateOnUpdate` or
`doNotGenerateOnCreate` are used.

The canonicalized will be made unique by appending `-` and a number.

To add a custom separator between canonicalized and a number, pass it as an arg to `disallowDuplicate()`.

```php
public function getCanonicalFields(): CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->disallowDuplicate('#')
        );
}
```

You can call a custom canonicalization method by providing the field type.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->type('other')
        );
}
```

The trait will look for method `canonicalizeOther(string $string)` in the model and `other(string $string)` method in the 
[`boomdraw/canonicalizer`](https://github.com/boomdraw/canonicalizer) and it's
[macroses](https://github.com/boomdraw/canonicalizer#canonicalizermacro). 

The canonicalization method also can be provided by using the `callback` function.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->callback(function($string) {
                    return mb_strtoupper($string);
                })
        );
}
```

The type will be ignored if the `callback` function provided.

You can pass additional arguments to the type based method by providing an array of arguments in the type method.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->type('custom', [$arg1, $arg2])
                ->callback(function($string, $arg, $arg2) {
                    return mb_strtoupper($string.$arg.$arg2);
                })
        )->addField(
             CanonicalField::create()
                 ->from('name')
                 ->type('slug', [$separator])
        );
}
```

You can also override the generated canonical just by setting it to another value than the generated canonical.

```php
$model = EloquentModel::create(['name' => 'John']); //The canonical is now "john"; 
$model->name_canonical = 'Ivan';
$model->save(); //The canonical is now "Ivan"; 
```

To force the canonicalization when a canonical is set manually use `forceCanonicalization()` method.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
             CanonicalField::create()
                 ->from('name')
                 ->type('slug')
                 ->forceCanonicalization()
        );
}
...
$model = EloquentModel::create(['name' => 'John']); //The canonical is now "john"; 
$model->name_canonical = 'Test User';
$model->save(); //The canonical is now "test-user"; 
```

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
             CanonicalField::create()
                 ->from('name')
                 ->to('name')
                 ->type('slug')
                 ->forceCanonicalization()
        );
}
...
$model = EloquentModel::create(['name' => 'John']); //The is now "john"; 
$model->name = 'Test User';
$model->save(); //The name is now "test-user"; 
```

If you don't want to create the canonical when the model is initially created you can set use the `doNotGenerateOnCreate()` function.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->doNotGenerateOnCreate()
        );
}
```

If you don't want to create the canonical when the model is initially created you can set use the `doNotGenerateOnUpdate()` function.

```php
public function getCanonicalFields() : CanonicalFieldsCollection
{
    return CanonicalFieldsCollection::create()
        ->addField(
            CanonicalField::create()
                ->from('name')
                ->doNotGenerateOnUpdate()
        );
}
```

This can be helpful for creating fields that don't change until you explicitly want it to.

```php
$model = EloquentModel::create(['name' => 'John']); //The canonical is now "john"; 
$model->save();

$model->name = 'Ivan';
$model->save(); //The canonical stays "john"
```

If you want to explicitly update the canonical on the model you can call `canonicalize(string $fieldName)` on your model
at any time to regenerate the canonical according to your other options.
Don't forget to `save()` the model to persist the update to your database.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

You can run the tests with:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details and a todo list.

## Security

If you discover any security-related issues, please email [pkgsecurity@boomdraw.com](mailto:pkgsecurity@boomdraw.com) instead of using the issue tracker.

## License

[MIT](http://opensource.org/licenses/MIT)
