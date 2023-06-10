<?php

namespace Ren\Tanto\Backend;
use Ren\Tanto\API as API;

require_once __DIR__.'/../backend.php';;
require_once __DIR__.'/../api/vk.php';;

// TODO: Move somewhere probably
/**
 * Vkontakte (vk.com) message context
*/
class VkMessageContext
{
    /**
     * API client handle
    */
    private API\VkApiClient $api;

    /**
     * @var array<mixed>
    */
    private array $object;

    // TODO: Message class
    /**
     * @var array<mixed>
    */
    public array $message;

    /**
     * Initializes context with API client handle and raw API response
     * @param array<mixed> $object
    */
    function __construct(API\VkApiClient $api, array $object)
    {
        $this->api = $api;
        $this->object = $object;

        $this->message = $this->object["message"];
    }

    /**
     * Sends message in chat what message belong
    */
    function reply(string $text) : void
    {
        $this->api->request("messages.send", [
            "user_id"=>$this->message["from_id"], 
            "random_id"=>rand(0, 1000000),
            "message"=>$text]);
    }
}

class Vkontakte implements \Ren\Tanto\Backend
{
    /**
     * Access token
     * https://dev.vk.com/api/access-token/getting-started
    */
    private string $token;

    /**
     * API client handle
    */
    private API\VkApiClient $api;

    /**
     * Longpoll client handle
    */
    private API\VkLongPollClient $lp_client;

    /**
     * Array of handlers
     * @var array<string, Callable>
    */
    private array $handlers;

    /**
     * Name of backend that used in logging
    */
    private string $name;

    /**
     * Timestamp when next poll request should be done
    */
    private int $next_request = 0;

    /**
     * Initializes context with access token
     * https://dev.vk.com/api/access-token/getting-started
    */
    function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Backend destructor
    */
    function __destruct()
    {
        $this->on_shutdown();
    }

    /**
     * Backend name getter
    */
    function get_name() : string
    {
        return 'Vkontakte';
    }

    /**
     * Prints message in console with backend name
    */
    function log(string $message) : void
    {
        echo "[{$this->get_name()}] $message" . PHP_EOL;
    }

    /**
     * Function called on backend start
     * Tests provided token and retrieves longpoll server
    */
    function on_start($handlers) : void
    {
        $this->handlers = $handlers;
        $this->api = new API\VkApiClient($this->token);

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

        $this->lp_client = new API\VkLongPollClient($answer->response['server'], $answer->response['key'],
            $answer->response['ts']);
    }

    /**
     * Function called on backend shutdown (error)
    */
    function on_shutdown() : void
    {
        $this->log("Stopped bot for {$this->name}");
    }

    /**
     * Backend loop
     * Polls events from longpoll server and handles it
    */
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
                $context = new VkMessageContext($this->api, $event["object"]);
                $this->handlers['message']($context->message["text"], $context);
            }
        }

        //
        $this->next_request = time() + 1;
    }
}