[![Build Status](https://travis-ci.org/fe3dback/kx-draw.svg?branch=master)](https://travis-ci.org/fe3dback/kx-draw)
[![Coverage Status](https://coveralls.io/repos/github/fe3dback/kx-draw/badge.svg?branch=master)](https://coveralls.io/github/fe3dback/kx-draw?branch=master)

# Handlebars Render (PHP/JS)

This is reactive two side template library for handlebars.
One template you can render on both sides:

- backend [PHP]
- frontend [JS] (optional)

## Install

```bash
$ composer require fe3dback/kx-draw
```

##### Requirements [PHP side]:
- php >= 7.0
- [composer](https://getcomposer.org/)

##### Requirements [JS side] - optional (only for two-side render):
- [jquery](https://developers.google.com/speed/libraries/) >= 2.*
- handlebars.js

Handlebars can be installed with many variants:
- [list handlebars.min-latest.js](http://builds.handlebarsjs.com.s3.amazonaws.com/bucket-listing.html?sort=lastmod&sortdir=desc)
- http://handlebarsjs.com/installation.html

## Use

### Prepare directories

- First of all you need to make tmp folder inside your project, kxdraw will
be store all cache data to optimize render speed. Check RWO access to this directory.

- Also you need some folder for templates.

Something like this:
```
- my_project_root
|- example.php              // some php script
|- tmp/draw                 // any cache will be here
|- public/templates/*.hbs   // templates folder
```

### Require lib

_code from example.php_
```php
use KX\Template\Builders\EngineFactory;
use KX\Template\Draw;

require_once 'vendor/autoload.php';

$draw = new Draw
(
    (new EngineFactory())
        ->setExt('hbs')                         // templates file extension (*.hbs)
        ->setCacheDirReal($cacheDir)            // cache directory (we can safely delete dir, and cache will be rebuild)
        ->setTemplatesDirReal($templatesDir)    // where our templates live
        ->setUseCache(true)                     // recommend to turn ON this feature (compile only first time)
        ->setUseMemCache(true)                  // recommend to turn ON this feature (helpful for loops)
        ->build()
);
```

Ok, we got $draw class, so we can render some template:

_code from public/templates/hello.hbs_
```handlebars
<b>Hello {{name}}!</b>
```

_code from example.php_
```php
$html = $draw->render('hello', 1, [
    'name' => 'world'
]);
```

What we use:
- **'hello'** - this is template file name (without extension)
- **1** - unique render id. Good idea to use EntityID (productId, userId, articleId, etc..) for that.
- **['name'=>'world']** - data used in template

Result will be in _$html_:
```html
<b>Hello world!</b>
```


### Global scope use

In some case we want to use DrawLib from any application place:

Put this code in any shared file, executed from every other script (common.php, etc..)
```php
/**
 * Get draw object
 * global scope wrapper
 *
 * @return Draw
 */
function KXDraw(): Draw
{
    global $__kxDrawEntity;

    if (is_null($__kxDrawEntity))
    {
        $cacheDir = '/'; // todo replace to your dir
        $templatesDir = '/'; // todo replace to your dir
        
        // make draw object, we can use only this class directly
        $__kxDrawEntity = new Draw
        (
            (new EngineFactory())
                ->setExt('hbs')                         // templates file extension (*.hbs)
                ->setCacheDirReal($cacheDir)            // cache directory (we can safely delete dir, and cache will be rebuild)
                ->setTemplatesDirReal($templatesDir)    // where our templates stored
                ->setUseCache(true)                     // recommend to turn ON this feature (compile only first time)
                ->setUseMemCache(true)                  // recommend to turn ON this feature (helpful for loops)
                ->build()
        );
    }

    return $__kxDrawEntity;
}
```

Use:

```php
$html = KXDraw()->render('hello', 1, [
    'name' => 'world'
]);
```

### Partials

todo..

## Examples
see examples folder.
