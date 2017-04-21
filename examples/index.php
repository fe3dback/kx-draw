<?php

require_once '../vendor/autoload.php';

use KX\Template\Builders\EngineFactory;
use KX\Template\Draw;

// ===================================================================
// Code for example demonstration (not needed in real use)
// ===================================================================

// For debug only --dev
if (class_exists('\Whoops\Run'))
{
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

// Make Example directories and templates (only for example)
$tmpDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '../tmp');
$templatesDir = $tmpDir . DIRECTORY_SEPARATOR . 'templates';
$partialsDir = $templatesDir . DIRECTORY_SEPARATOR . 'shared';
$cacheDir = $tmpDir . DIRECTORY_SEPARATOR . 'cache';
$buildExampleStruct = [$tmpDir, $templatesDir, $cacheDir, $partialsDir];

foreach ($buildExampleStruct as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir);
    }
}

// Make test template
$testTemplate = "<b>Hello {{> shared/name}}!</b>";
file_put_contents($templatesDir . DIRECTORY_SEPARATOR . 'hello.hbs', $testTemplate);

$testPartial = "<i>{{name}}</i>";
file_put_contents($partialsDir . DIRECTORY_SEPARATOR . 'name.hbs', $testPartial);


// ===================================================================
// REAL USE
// ===================================================================

// make draw object
$draw = new Draw
(
    (new EngineFactory())
        ->setExt('hbs')
        ->setCacheDirReal($cacheDir)
        ->setTemplatesDirReal($templatesDir)
        ->setUseCache(true)
        ->setUseMemCache(true)
        ->setUseBenchmark(true)
        ->build()
);

// mark 'shared' dir as partials dir (we make it before)
$draw->addPartialsDirectory('shared');

// render template and output
echo $draw->render('hello', 1, [
    'name' => 'world'
]);

// ===================================================================
// Example benchmark (optional)
// ===================================================================

$time = $draw->getDrawTime();
print_r($time);