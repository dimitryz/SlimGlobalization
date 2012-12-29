SlimGlobalization
=================

I18n and L10n functionality for the PHP Slim framework

The SlimGlobalization plugin prefixes all URLs of the site with the user's
prefered browser language or the default language, as defined by the
programmer.

For example, if English is given as the only language, with the key 'en', the
following URL transformations take place.

```
http://example.com       -> http://example/en/
http://example.com/admin -> http://example/en/admin
http://example.com/en    -> http://example/en/en
```

When giving a list of languages to the SlimGlobalization middleware, the first
language is used as the default language. When switching to a language that is
not in the list of supported languages, the first language is used instead.

Building a link to switch language can be done like so:

```php
$app = new \Slim\Slim();
$app->add(new \SlimGlobalization\Middleware\I18n(array(
    'en' => 'English',  // Default language
    'fr' => 'Français',
)));

$app->get('/', function () use ($app) {
    echo $app->urlFor('lang') . '?lang=fr';
    // /en/language?lang=fr
));
```

The language handler for 'lang' is only initialized after the middleware is
called. In other words, the handler for 'lang' is only available to the 
controller function.

It's also possible to list all languages available to the site. The languages
passed to the middleware constructor are available as Slim config.

```php
$app->config('languages');
// array('en' => 'English', 'fr' => 'Français')

$app->config('language.id');
// 'en'

$app->config('language.name');
// 'English
```

Building a list of language switches is equaly simple:

```php
<?php foreach ( $app->config('languages') as $langKey => $langName ) : ?>
    <a href="<?php echo $app->urlFor('lang') , "?lang=$langKey" ?>">
        <?php echo $langName ?></a>
<?php endforeach ?>
```

Finally, a user can be redirected to a specific page after the language was
changed:

```php
$app->redirect($app->url('lang') . '?lang=fr&next=/page");
// Redirect the user to '/fr/page'
```

## Use

### Requirements

SlimGlobalization requires **PHP >= 5.3** and **Slim >= 2.0**.

### Install

Download the compressed content of this plugin and copy the SlimGlobalization
director into a directory for third-party libraries. This document assumes that
directory is called 'vendor' and is located in the same directory as the index
file.

### Use

Once copied, add the middleware to too the Slim instance in your index file.

Example:

```php
$app = new \Slim\Slim();

include_once 'path_to_vendors/SlimGlobalization/Middleware/I18n.php';
$app->add(new \SlimGlobalization\Middleware\I18n(array(
    'en' => 'English', // Used as the default language
    'fr' => 'French',
)));

// Required for storing the language in the user's session
$app->add(new \Slim\Middleware\SessionCookie());

$app->get('/', function () use ($app) {
    // Renders the index page
});

$app->run();
```

## Author

The SlimGlobalization plugin was created and is maintained by
[Dimitry Zolotaryov](http://webit.ca/). Dimitry is a freelance web developer 
and consultant working under the alias WebIT. 

## License

The SlimGlobalization plugin is release under the MIT public license.
