<?php

$format = $_GET['format'] ?? 'json';

$m = require __DIR__ . '/../src/cacheconnect.php';


if ($format === 'json') {
    header('Content-Type: application/json');
    echo $m->get('statistic_json') ?? '[]';
    exit;
}

header('Content-Type: text/plain');
echo $m->get('statistic_csv') ?? '';
