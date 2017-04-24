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

// mark 'shared' dir as partials dir
// all templates in this dir will be accessible wia partial operator in hbs
// {{> shared/widgets/input}} - &TEMPLATES_DIR&/shared/widgets/input.hbs
$draw->addPartialsDirectory('shared');

// ======================================
// REAL USE:
// ======================================

$html = $draw->render('hello', 1, [
    'name' => 'world'
]);

// ======================================
// Benchmark (only for debug):
// ======================================

const BENCHMARK_COUNT = 1000;

// render template and output
$bench = new Ubench();
$bench->start();

for ($i=0; $i<=BENCHMARK_COUNT; $i++)
{
    // render 10000 times simple template
    $draw->render('hello', $i, [
        'name' => 'world'
    ]);
}

$bench->end();

// display stats


// ======================================
// Example layout (only for debug):
// ======================================

// now prepare all data to example output
// (only for debug)
$index = 1;
$source = $draw->getTemplate('hello'); // get raw template
$source_shared = $draw->getTemplate('shared/name');
$data = ['name' => 'world'];
$result = $draw->render('hello', $index, $data);

// draw rendered template
$exampleHtml = $draw->render('example_1', true, [
    'index' => $index,
    'source' => $source,
    'source_shared' => $source_shared,
    'data' => json_encode($data, JSON_PRETTY_PRINT),
    'result' => $result
]);
?>

<head>
    <title>Example</title>
    <style rel="stylesheet">
        .example .row {
            width: 100%;
            display: inline-flex;
            margin-bottom: 20px;
            clear: both;
        }
        .example .col {
            width: 33%;
            margin-right: 15px;
            padding: 0 10px;
        }
        .example .col .title {
            display: block;
            width: 100%;
            margin-bottom: 5px;
            position: relative;
            left: -10px;
            border-bottom: 1px solid cornflowerblue;
        }

        .example textarea {
            height: 100px;
            min-height: 100px;
            max-height: 200px;
            width: 100%;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?=$exampleHtml?>

    <!-- benchmark -->
    <br>Loop render times: <?=BENCHMARK_COUNT?>
    <br>Loop time: <?=$bench->getTime();?>
    <br>Usage: <?=$bench->getMemoryUsage();?>
    <br>Mem Peak: <?=$bench->getMemoryPeak();?>

    <!-- engine dump -->
    <?var_dump($draw->__getEngine());?>
</body>