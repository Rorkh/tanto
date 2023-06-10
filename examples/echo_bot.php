<?php

require '../vendor/autoload.php';

$env = parse_ini_file('.env');

$tanto = new \Ren\Tanto\Tanto();
$tanto->add_backend(new \Ren\Tanto\Backend\Vkontakte($env["token"]));

$tanto->on_message(function($msg, $context) {
    $context->reply($msg);
});

$tanto->start();