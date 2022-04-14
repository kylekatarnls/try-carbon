<?php

if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

ini_set('html_errors', false);

header('Content-type: text/plain');

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler('exception_error_handler');

$version = $_POST['version'] === 'master' ? '2.x-dev' : $_POST['version'];
$autoload = __DIR__ . '/../var/engines/carbon/' . $version . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    echo 'Update in progress, please retry in few minutes.';
    exit;
}

if (is_writeable(__FILE__)) {
    shell_exec('chmod -R 0555 ..');
}

include_once __DIR__ . '/../allow-csrf.php';
require_once $autoload;

$classes = [
    'Carbon\Carbon',
    'Carbon\CarbonInterval',
    'Carbon\CarbonInterface',
    'Carbon\CarbonImmutable',
    'Carbon\CarbonPeriod',
    'Carbon\CarbonTimeZone',
    'Carbon\Language',
    'Carbon\Translator',
    'Carbon\Factory',
    'Carbon\FactoryImmutable',
    'Cmixin\BusinessDay',
    'Cmixin\BusinessTime',
    'Carbon\Carbonite',
];

try {
    eval(implode(' ', array_map(function ($className) {
        return "use $className;";
    }, $classes)) . $_POST['input']);
} catch (\Throwable $e) {
    $message = trim((string) $e->getMessage());
    echo 'Error' . (substr($message, 0, 1) === '('
        ? preg_replace('/^\((\d+)\)(\s*:)?/', ' line $1:', $message)
        : ': ' . $message
    ) . "\n" . $e->getFile() . ':' . $e->getLine()
    . "\n" . $e->getTraceAsString();
}
