<?php
require_once '../vendor/autoload.php';

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
$tmpDir = __DIR__ . DIRECTORY_SEPARATOR . '../tmp';
$templatesDir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
$partialsDir = $templatesDir . DIRECTORY_SEPARATOR . 'shared';
$cacheDir = $tmpDir . DIRECTORY_SEPARATOR . 'cache';
$buildExampleStruct = [$tmpDir, $cacheDir, $partialsDir];

foreach ($buildExampleStruct as $dir)
{
    if (!is_dir($dir))
    {
        mkdir($dir);
    }
}

// ===================================================================
// Set up logger
// ===================================================================

if (class_exists('Monolog\Logger'))
{
    // create a log channel
    global $__libLogger;
    $__libLogger = new Monolog\Logger('name');
    $__libLogger->pushHandler(new Monolog\Handler\RotatingFileHandler($tmpDir . DIRECTORY_SEPARATOR . 'lib.log', Monolog\Logger::WARNING));
}

function fire($msg)
{
    if (class_exists('Monolog\Logger'))
    {
        global $__libLogger;
        /** @var $__libLogger Monolog\Logger */
        $__libLogger->info(print_r($msg, true));
    }
}
