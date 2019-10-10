<?php

$composer = json_decode(file_get_contents('composer.json'));

$composer->extra->build = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

file_put_contents('composer.json', json_encode($composer, JSON_PRETTY_PRINT));
