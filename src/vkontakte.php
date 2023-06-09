<?php

namespace Ren\Tanto;

require_once 'backend.php';
require_once 'vk_api.php';

class VkMessageContext
{
    private VkApiClient $api;

    /**
     * @var array<mixed>
    */
    public array $object;

    // TODO: Object type (Message or smth like this)
    /**
     * @param array<mixed> $object
    */
    function __construct(VkApiClient $api, array $object)
    {
        $this->api = $api;
        $this->object = $object;
    }

    function reply(string $text) : void
    {
        $this->api->request("messages.send", [
            "user_id"=>$this->object["message"]["from_id"], 
            "random_id"=>rand(0, 1000000),
            "message"=>$text]);
    }
}

class Vkontakte implements Backend
{
    private string $token;
    private VkApiClient $api;
    private VkLongPollClient $lp_client;

    /**
     * @var array<string, Callable>
    */
    private array $handlers;

    private string $name;
    private int $next_request = 0;

    function __construct(string $token)
    {
        $this->token = $token;
    }

    function __destruct()
    {
        $this->on_shutdown();
    }

    function get_name() : string
    {
        return 'Vkontakte';
    }

    function log(string $message) : void
    {
        echo "[{$this->get_name()}] $message" . PHP_EOL;
    }

    function on_start($handlers) : void
    {
        $this->handlers = $handlers;
        $this->api = new \Ren\Tanto\VkApiClient($this->token);

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

    function on_shutdown() : void
    {
        $this->log("Stopped bot for {$this->name}");
    }

    function loop() : void
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