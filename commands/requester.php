<?php
// Makes http requests for testing

$ch = curl_init('http://kudos.local/post.php');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


$countries = ['US', 'RU', 'PH', 'TH', 'AF', 'AH', 'AL', 'AM', 'SE'];
$events = ['view', 'play', 'click'];
$dates = [];
for ($i = 0; $i<20; $i++ ) {
    $dates[] = (new \DateTime("-$i days"))->format('Y-m-d');
}

$j = 0;
while (true) {
    $data = json_encode(['country' => arrayRand($countries), 'event' => arrayRand($events), 'date' => arrayRand($dates)]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );

    $result = curl_exec($ch);
    echo $j++, ' ';
}


function arrayRand(array $a): string
{
    return $a[random_int(0, count($a) - 1)];
}
