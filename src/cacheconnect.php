<?php

$m = new Memcached();
$m->addServer('localhost', 11211);

return $m;
