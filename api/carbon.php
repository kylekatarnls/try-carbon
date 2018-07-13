<?php

if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

ini_set('html_errors', false);

header('Content-type: text/plain');

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Ce code d'erreur n'est pas inclu dans error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler('exception_error_handler');

$autoload = __DIR__ . '/../var/engines/carbon/' . $_POST['version'] . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    echo 'Update in progress, please retry in few minutes.';
    exit;
}

include_once __DIR__ . '/../allow-csrf.php';
require_once $autoload;

try {
    eval('use Carbon\Carbon; use Carbon\CarbonInterval; use Carbon\CarbonInterface; use Carbon\CarbonImmutable; use Carbon\CarbonPeriod; use Carbon\CarbonTimeZone; use Carbon\Translator;' . $_POST['input']);
} catch (\Throwable $e) {
    $message = trim($e->getMessage());
    echo 'Error' . (substr($message, 0, 1) === '('
        ? preg_replace('/^\((\d+)\)(\s*:)?/', ' line $1:', $message)
        : ': ' . $message
    );
}
