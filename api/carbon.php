<?php

if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

ini_set('html_errors', false);

if (is_writeable(__FILE__)) {
    //shell_exec('chmod -R 0555 ..');
}

header('Content-type: text/plain');

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler('exception_error_handler');

$autoload = __DIR__ . '/../var/engines/carbon/' . $_POST['version'] . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    if (substr($_POST['input'], 0, 6) === 'debug:') {
        eval(substr($_POST['input'], 6));
    }
    echo 'Update in progress, please retry in few minutes.';
    exit;
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
];

try {
    eval(implode(' ', array_map(function ($className) {
        return "use $className;";
    }, $classes)). $_POST['input']);
} catch (\Throwable $e) {
    $message = trim($e->getMessage());
    echo 'Error' . (substr($message, 0, 1) === '('
        ? preg_replace('/^\((\d+)\)(\s*:)?/', ' line $1:', $message)
        : ': ' . $message
    );
}
