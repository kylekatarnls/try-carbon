<?php

header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] === 'https://carbon.nesbot.com' ? 'https://carbon.nesbot.com' : '*'));
header('X-XSS-Protection: 0');
