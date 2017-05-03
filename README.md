[![Build Status](https://travis-ci.org/fe3dback/kx-draw.svg?branch=master)](https://travis-ci.org/fe3dback/kx-draw)
[![Coverage Status](https://coveralls.io/repos/github/fe3dback/kx-draw/badge.svg?branch=master)](https://coveralls.io/github/fe3dback/kx-draw?branch=master)

# Handlebars Render (PHP/JS)

This is reactive two side template library for handlebars.
One template you can render on both sides:

- backend [PHP]
- frontend [JS] (optional)

Lib is wrapper of fastest handlebars implementation, based on:
[zordius/lightncandy](https://github.com/zordius/lightncandy)

[![Unit testing](https://travis-ci.org/zordius/lightncandy.svg?branch=master)](https://travis-ci.org/zordius/lightncandy) 
[![Regression testing](https://travis-ci.org/zordius/HandlebarsTest.svg?branch=master)](https://travis-ci.org/zordius/HandlebarsTest)
[![License](https://poser.pugx.org/zordius/lightncandy/license.svg)](https://github.com/zordius/lightncandy/blob/master/LICENSE.md) 
[![Total Downloads](https://poser.pugx.org/zordius/lightncandy/downloads)](https://packagist.org/packages/zordius/lightncandy) 

## Install

```bash
$ composer require fe3dback/kx-draw
```

##### Requirements [PHP side]:
- php >= 7.0
- [composer](https://getcomposer.org/)

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

Before use partials in our templates, we need to mark some templates (or entire folders as partials).

```php
// mark /templates/common/header.hbs
KXDraw()->addPartial('/common/header');

// mark /templates/shared/*/*
KXDraw()->addPartialsDirectory('shared');
```

Now any marked templates can be used as partials:

_code from public/templates/hello.hbs_
```handlebars
<b>Hello {{> shared/name}}!</b>
```

_code from public/templates/shared/name.hbs_
```handlebars
<i>{{name}}</i>
```

_code from example.php_
```php
$html = KXDraw()->render('hello', 1, [
    'name' => 'world'
]);
```

Result will be in _$html_:
```html
<b>Hello <i>world</i>!</b>
```

### Templates and Reactivity

- All templates should have only one parent. (like in react). For example:

_This is OK_:
```handlebars
<div>..some_content..</div>
```

_This will be broken_:
```handlebars
<div>..some_content1..</div>
<div>..some_content2..</div>
```

In case when template have many nodes, we can wrap with some another div/span/etc..

_This is OK_:
```handlebars
<div>
    <div>..some_content1..</div>
    <div>..some_content2..</div>
</div>
```

#### Export to JS

##### Requirements [JS side]:
- [jquery](https://developers.google.com/speed/libraries/) >= 2.*
- handlebars.js

Handlebars can be installed with many variants:
- [list handlebars.min-latest.js](http://builds.handlebarsjs.com.s3.amazonaws.com/bucket-listing.html?sort=lastmod&sortdir=desc)
- http://handlebarsjs.com/installation.html

##### Include

- place JQ and handlebars.js anywhere in markup (for example before < / body> tag closing)
- **AFTER** place js draw implementation and exported data:

```html
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.7/handlebars.min.js"></script>
<script type="text/javascript" src="/vendor/fe3dback/kx-draw/draw.js"></script>

<!-- after export no more rendering allowed, this should be last call to KXDraw -->
<?=KXDraw()->exportToJS()?>
```

## JS Usage

Go to page with templates and lib, and try in console:

```js
KXDraw
// KXDrawRender {templates: Object, data: Object, partials: Object}
```

If lib included correctly, 
this object should contain all data, templates and partials 
from backend

### Methods:

#### KXDraw.getRawData()
Return all data used in backend

```js
KXDraw.getRawData()
// Object {hello: Object}
```

#### KXDraw.getRawPartials()
Return all partials

```js
KXDraw.getRawPartials()
// Object {shared/name: "<i>{{name}}</i>"}
```

#### KXDraw.getRawTemplates()
Return all raw templates from backend files

```js
KXDraw.getRawTemplates()
// Object {hello: "<b>Hello {{> shared/name}}!</b>"}
```

#### KXDraw.update(templateName, uniqueId, data = {}, assign = true)

Update html template on page, and template data.

- string **templateName** - path to templateFile 
(relative without extension)
- string **uniqueId** - id from backend
- object **data** - object with fields
- bool **assign** _DEF: TRUE_ - if true, update will combine old 
data with new data

```js
KXDraw.update('hello', 1, {name:"KXRender"});
// true
```

**Assign example**
```js
// old data:
{
    a: 1, 
    b: 2
}

// new data:
{
    b: 4
}

// RESULT WITH ASSIGN:
{
    a: 1, 
    b: 4
}

// RESULT WITHOUT ASSIGN:
{
    b: 4
}
```

#### KXDraw.getDataByUniqId(templateName, uniqueId)

Get stored data from used template

- string **templateName** - path to templateFile 
(relative without extension)
- string **uniqueId** - id from backend

```js
KXDraw.getDataByUniqId('hello', 1);
// Object {
// name: "world", 
// _kx_draw_template_name: "hello", 
// _kx_draw_unique_id: "1"
// }
```

#### KXDraw.getNodeByUniqId(templateName, uniqueId)

Get Jquery node object

- string **templateName** - path to templateFile 
(relative without extension)
- string **uniqueId** - id from backend

```js
KXDraw.getNodeByUniqId('hello', 1);
// [b, prevObject: r.fn.init(1)]
// > 0: b
// > length: 1
```


## Examples
see examples folder.
