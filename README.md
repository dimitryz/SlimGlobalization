SlimGlobalization
=================

I18n and L10n functionality for the PHP Slim framework

## Use

### Requirements

SlimGlobalization requires *PHP >= 5.3*

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
    'en' => 'English',
    'fr' => 'French',
)));

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
