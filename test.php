<?php

include 'ProxyChecker.php';

$inst = new ProxyChecker();
$inst->set_proxylists();
$inst->send_request();
