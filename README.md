Laravel Gravatar
==========

Gravatar Package for Laravel 5.0

installation
------------
For install this package Edit your project's ```composer.json``` file to require parsidev/gravatar

```php
"require": {
    "parsidev/gravatar": "dev-master"
},
```
Now, update Composer:
```
composer update
```
Once composer is finished, you need to add the service provider. Open ```config/app.php```, and add a new item to the providers array.
```
'Parsidev\Gravatar\GravatarServiceProvider',
```
Next, add a Facade for more convenient usage. In ```config/app.php``` add the following line to the aliases array:
```
'Gravatar'	=> 'Parsidev\Gravatar\Facades\Gravatar',
```
Publish config files:
```
php artisan vendor:publish
```

Usage
-----

```php
Gravatar::exists($email)
```
Returns a boolean telling if the given $email has got a Gravatar.

```php
Gravatar::saveImage($email, $destination, $size=null, $rating=null)
```
Download gravatar 

```php
Gravatar::src($email, $size = null, $rating = null)

Returns the https URL for the Gravatar of the email address specified. Can optionally pass in the size required as an integer. The size will be contained within a range between 1 - 512 as gravatar will no return sizes greater than 512 of less than 1

<!-- Show image with default dimensions -->
<img src="{{ Gravatar::src('info@parsidev.ir') }}">

<!-- Show image at 200px -->
<img src="{{ Gravatar::src('info@parsidev.ir', 200) }}">

<!-- Show image at 512px scaled in HTML to 1024px -->
<img src="{{ Gravatar::src('info@parsidev.ir', 1024) }}" width=1024>
```


```php
Gravatar::image($email, $alt = null, $attributes = array(), $rating = null)

Returns the HTML for an <img> tag

echo Gravatar::image('info@parsidev.ir');

// Show image at 200px
echo Gravatar::image('info@parsidev.ir', 'Some picture', array('width' => 200, 'height' => 200));

// Show image at 512px scaled in HTML to 1024px
echo Gravatar::image('info@parsidev.ir', 'Some picture', array('width' => 1024, 'height' => 1024));
```