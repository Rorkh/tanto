<?php

namespace Ren\Tanto;

require_once 'backend.php';
require_once 'vk_api.php';

class VkMessageContext
{
    private $api;
    public $object;

    function __construct($api, $object)
    {
        $this->api = $api;
        $this->object = $object;
    }

    function reply($message)
    {
        $this->api->request("messages.send", [
            "user_id"=>$this->object["message"]["from_id"], 
            "random_id"=>rand(0, 1000000),
            "message"=>$message]);
    }
}

class Vkontakte implements Backend
{
    private string $token;
    private $api;
    private $lp_client;

    private $handlers;

    private string $name;
    private $next_request = 0;

    function __construct(string $token)
    {
        $this->token = $token;
    }

    function __destruct()
    {
        $this->on_shutdown();
    }

    function get_name()
    {
        return 'Vkontakte';
    }

    function log(string $message)
    {
        echo "[{$this->get_name()}] $message" . PHP_EOL;
    }

    function on_start($handlers)
    {
        $this->handlers = $handlers;
        $this->api = new \Ren\Tanto\VKApiClient($this->token);

        $answer = $this->api->request("groups.getById", ["fields"=>"screen_name"]);
        if (!$answer->is_ok())
        {
            $this->log("Can't access API. Probably bad token provided.");
            exit;
        }

        $this->name = $answer->response[0]['name'];
        $this->log("Started bot for {$this->name} (vk.com/{$answer->response[0]['screen_name']})");

        $answer = $this->api->request("groups.getLongPollServer", ["group_id"=>$answer->response[0]['id']]);
        if (!$answer->is_ok())
        {
            $this->log("Oops... Can't access Long Poll Server. Try again later.");
            exit;
        }

        $this->lp_client = new \Ren\Tanto\VkLongPollClient($answer->response['server'], $answer->response['key'],
            $answer->response['ts']);
    }

    function on_shutdown()
    {
        $this->log("Stopped bot for {$this->name}");
    }

    function loop()
    {
        if ($this->next_request > time())
        {
            return;
        }

        $events = $this->lp_client->poll();
        foreach ($events as $event)
        {
            if ($event["type"] == "message_new")
            {
                $this->handlers['message'](new \Ren\Tanto\VkMessageContext($this->api, $event["object"]));
            }
        }

        //
        $this->next_request = time() + 1;
    }
}