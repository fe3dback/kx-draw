<?php

use KX\Template\Builders\EngineFactory;
use KX\Template\Draw;

include_once "__common_part.php";

// ======================================
// CONFIGURE:
// ======================================

// make draw object
$draw = new Draw
(
    (new EngineFactory())
        ->setExt('hbs')                         // templates file extension (*.hbs)
        ->setCacheDirReal($cacheDir)            // cache directory (we can safely delete dir, and cache will be rebuild)
        ->setTemplatesDirReal($templatesDir)    // where our templates stored
        ->setUseCache(true)                     // recommend to turn ON this feature (compile only first time)
        ->setUseMemCache(true)                  // recommend to turn ON this feature (helpful for loops)
        ->build()
);

// see engine status (not needed)
var_dump($draw->__getEngine());

// ======================================
// REAL USE:
// ======================================

// mark 'shared' dir as partials dir (we make it before, see __common_part.php)
// all templates in this dir will be accessible wia partial operator in hbs
// {{> shared/widgets/input}} - &TEMPLATES_DIR&/shared/widgets/input.hbs
$draw->addPartialsDirectory('shared');

// example render (demo template we make automatic in __common_part.php)
$html = $draw->render('hello', 1, [
    'name' => 'world'
]);

// draw rendered template
echo $html;

// ======================================
// Benchmark (only for debug):
// ======================================

// render template and output
$bench = new Ubench();
$bench->start();

for ($i=0; $i<=10000; $i++)
{
    // render 10000 times simple template
    $draw->render('hello', 1, [
        'name' => 'world'
    ]);
}

$bench->end();

// display stats
echo "<br>Loop time: " . $bench->getTime();
echo "<br> Usage: " . $bench->getMemoryUsage();
echo "<br>Mem Peak: " . $bench->getMemoryPeak();
