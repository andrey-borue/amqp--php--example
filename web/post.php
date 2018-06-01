<?php

const EVENTS = ['view', 'play', 'click'];

$input = file_get_contents('php://input');

// This check can be simplified and moved to the consumer to increase productivity
$json = json_decode($input, true) ?? [];
$country = $json['country'] ?? null;
$event = $json['event'] ?? null;
$date = $json['date'] ?? date('Y-m-d');
if (!$country || !in_array($event, EVENTS, true)) {
    http_response_code(400);
    exit;
}
$message = serialize(['country' => $country, 'event' => $event, 'date' => $date]);


$connection = new AMQPConnection();
$connection->connect();

$channel = new AMQPChannel($connection);

$exchange = new AMQPExchange($channel);
$exchange->setName('statistic_exchange');
$exchange->setType(AMQP_EX_TYPE_DIRECT);
$exchange->declareExchange();

$queue = new AMQPQueue($channel);
$queue->setName('statistic_queue');
$queue->setFlags(AMQP_DURABLE);
$queue->declareQueue();
$queue->bind('statistic_exchange');


$exchange->publish($message);
