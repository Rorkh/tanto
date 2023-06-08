<?php

require_once 'vendor/autoload.php';

$vk_token = "vk1.a.slujivAlu61tIwbytdee10sydYldVAjKtBdyx--6RInR9e00ay0N8Xq-crMr2Cggo4BFneaswjzv_d8Dj9RdxQRvBly-49VGw2DQM4yNvoIaXF_-Hhj_nBL7kMn-jFFCmaE_fS7BftMoJLwm6VjLMHA4SNUMJ62NaaR0LaR4MGWp4puw9gN19c82UgLp1PLDCbQsvjqMclszzrduk_kx8Q";

$tanto = new \Ren\Tanto\Tanto();
$tanto->add_backend(new \Ren\Tanto\Vkontakte($vk_token));

$tanto->on_message(function($context) {
    $context->reply("Пошел нахуй!");
});

$tanto->start();