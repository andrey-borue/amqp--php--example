<?php

$dbh = require __DIR__ . '/../src/dbconnect.php';

$sql = 'SELECT country FROM statistic_global ORDER BY `count` DESC LIMIT 5';
$topCountries = [];
foreach ($dbh->query($sql) as $row) {
    $topCountries[] = "'" . $row['country'] . "'";
}

$date = (new \DateTime('-7 days'))->format('Y-m-d');


$countries = count($topCountries) ? implode(',', $topCountries) : '""';

$sql = "
    SELECT `date`, country,  `event`, SUM(`count`) as `count`
    FROM log
    WHERE country IN ($countries)
      AND date > '$date'
    GROUP BY country, `event`, `date`
    ORDER BY `date` DESC, `country`
";

$result = [];
foreach ($dbh->query($sql, PDO::FETCH_ASSOC) as $row) {
    $result[] = [
        'date' => $row['date'],
        'country' => $row['country'],
        'event' => $row['event'],
        'count' => (int)$row['count']
    ];
}

$m = require __DIR__ . '/../src/cacheconnect.php';
$m->set('statistic_json', json_encode($result));

$handle = fopen('php://memory', 'wb');
foreach ($result as $row) {
    fputcsv($handle, $row, ' ');
}
fseek($handle, 0);
$m->set('statistic_csv', stream_get_contents($handle));
