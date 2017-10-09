<?php

use KX\Template\Builders\EngineFactory;
use KX\Template\Draw;

include_once "__common_part.php";

// ======================================
// CONFIGURE:
// ======================================

/**
 * Get draw object
 * global scope wrapper
 * this is optional, only for global project use
 *
 * @return Draw
 */
function KXDraw(): Draw
{
    // cache, templates dir we make early in __common_part.php
    global $__kxDrawEntity, $cacheDir, $templatesDir;

    if (is_null($__kxDrawEntity))
    {
        // make draw object, we can use only this class directly
        $__kxDrawEntity = new Draw
        (
            (new EngineFactory())
                ->setExt('hbs')                         // templates file extension (*.hbs)
                ->setCacheDirReal($cacheDir)            // cache directory (we can safely delete dir, and cache will be rebuild)
                ->setTemplatesDirReal($templatesDir)    // where our templates stored
                ->setUseCache(true)                     // recommend to turn ON this feature (compile only first time)
                ->setUseMemCache(true)                  // recommend to turn ON this feature (helpful for loops)
                ->setUseBenchmark(false)                 // not needed in real use
                ->build()
        );
    }

    return $__kxDrawEntity;
}



// mark 'shared' dir as partials dir
// all templates in this dir will be accessible wia partial operator in hbs
// {{> shared/widgets/input}} - &TEMPLATES_DIR&/shared/widgets/input.hbs
KXDraw()->addPartialsDirectory('shared');

// ======================================
// REAL USE:
// ======================================

$html = KXDraw()->render('hello', 1, [
    'name' => 'world'
]);

// ======================================
// Benchmark (only for debug):
// ======================================

const BENCHMARK_COUNT = 1000;

if (class_exists('Ubench'))
{
    // render template and output
    $bench = new Ubench();
    $bench->start();

    for ($i=0; $i<=BENCHMARK_COUNT; $i++)
    {
        // render 10000 times simple template
        KXDraw()->render('hello', 1000+$i, [
            'name' => 'world - '.$i
        ]);
    }

    $bench->end();
}

// display stats


// ======================================
// Example layout (only for debug):
// ======================================

// now prepare all data to example output
// (only for debug)
$index = 1;
$source = KXDraw()->getTemplate('hello'); // get raw template
$source_shared = KXDraw()->getTemplate('shared/name');
$data = ['name' => 'world'];
$result = KXDraw()->render('hello', $index, $data);

// draw rendered template
$exampleHtml = KXDraw()->render('example_1', true, [
    'index' => $index,
    'source' => $source,
    'source_shared' => $source_shared,
    'data' => json_encode($data, JSON_PRETTY_PRINT),
    'result' => $result
]);

fire(KXDraw()->getDrawTime());
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

    <?if (class_exists('Ubench')):?>
        <!-- benchmark -->
        <br>Loop render times: <?=BENCHMARK_COUNT?>
        <br>Loop time: <?=$bench->getTime();?>
        <br>Usage: <?=$bench->getMemoryUsage();?>
        <br>Mem Peak: <?=$bench->getMemoryPeak();?>
    <?endif;?>

    <!-- engine dump -->
    <?var_dump(KXDraw()->getEngine());?>


    <!-- Send all data to JS Side (optional, for js render only) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.7/handlebars.min.js"></script>
    <script type="text/javascript" src="./../draw.js"></script>
    <?=KXDraw()->exportToJS()?>

    <!-- Example render action, triggered when button pressed -->
    <script type="text/javascript">
        function render_again(index) {
            var jsonData = JSON.parse($("#JSExampleDataJSON").val());
            KXDraw.update('hello', index, jsonData, false);
        }
    </script>
</body>