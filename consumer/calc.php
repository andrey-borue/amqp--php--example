<?php
// Just for test, in real project these numbers must be greater
const PREFETCH_COUNT = 10;
const UPDATE_COUNT = 5;

$connection = new AMQPConnection();
$connection->connect();

$channel = new AMQPChannel($connection);
$channel->setPrefetchCount(PREFETCH_COUNT);

$queue = new AMQPQueue($channel);
$queue->setName('statistic_queue');

$tags = [];
$update = [];


$dbh = require __DIR__ . '/../src/dbconnect.php';
$stmt = $dbh->prepare('
            INSERT INTO log
              (date, country, event, count)
            VALUES
              (:date, :country, :event, :count)
            ON DUPLICATE KEY UPDATE
              count = count + :count
');

// Probably I should add here also the type of event
// Or in a real system, this table can be pre-populated with data and use only UPDATE
$stmtStateGlobal = $dbh->prepare('
            INSERT INTO statistic_global
                (country, count)
            VALUES 
                (:country, :count)
            ON DUPLICATE KEY UPDATE
              count = count + :count
');

$queue->consume(function(AMQPEnvelope $envelope, AMQPQueue $queue) use (&$tags, &$update, $stmt, $stmtStateGlobal, $dbh) {
    $body = unserialize($envelope->getBody(), ['allowed_classes' => false]);

    $date = $body['date'];
    $country = $body['country'];
    $event = $body['event'];

    if (!isset($update[$date])) {
        $update[$date] = [];
    }

    if (!isset($update[$date][$country])) {
        $update[$date][$country] = [];
    }

    if (!isset($update[$date][$country][$event])) {
        $update[$date][$country][$event] = 1;
    } else {
        $update[$date][$country][$event] ++;
    }

    $tags[] = $envelope->getDeliveryTag();

    if (count($tags) > UPDATE_COUNT) {
        $result = processUpdates($update, $stmt, $stmtStateGlobal, $dbh);
        foreach ($tags as $tag) {
            if ($result) {
                $queue->ack($tag);
            } else {
                $queue->nack($tag);
                // Log ...
            }
        }
        $tags = [];
        $update = [];
    }
});


function processUpdates(array $update, PDOStatement $stmt, PDOStatement $stmtStateGlobal, PDO $dbh): bool
{
    $dbh->beginTransaction();

    $c = [];

    foreach ($update as $date => $countries) {
        $stmt->bindParam('date', $date);
        foreach ($countries as $country => $events) {
            $stmt->bindParam('country', $country);

            if (!isset($c[$country])) {
                $c[$country] = 0;
            }

            foreach ($events as $event => $count) {
                $c[$country] += $count;

                $stmt->bindParam('event', $event);
                $stmt->bindParam('count', $count);
                $stmt->execute();

                if ($stmt->errorCode() !== '00000') {
                    $dbh->rollBack();
                    var_dump($stmt->errorInfo());
                    return false;
                }
            }
        }
    }
    foreach ($c as $country => $count) {
        $stmtStateGlobal->bindParam('country', $country);
        $stmtStateGlobal->bindParam('count', $count);
        $stmtStateGlobal->execute();
        if ($stmtStateGlobal->errorCode() !== '00000') {
            $dbh->rollBack();
            var_dump($stmtStateGlobal->errorInfo());
            return false;
        }

    }

    $dbh->commit();
    return true;
}
