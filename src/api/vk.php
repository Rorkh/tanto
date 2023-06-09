<?php

namespace Ren\Tanto\API;

/**
 * Vkontakte (vk.com) API answer
*/
class VKApiAnswer
{
    // TODO: Error class with error_code and error_msg
    /**
     * If API request failed with error
    */
    private bool $status;

    /**
     * API response as JSON array
     * https://dev.vk.com/reference/json-schema
    */
    public mixed $response;

    /**
     * Initializes this answer with raw API response
    */
    public function __construct(mixed $response)
    {

        $this->status = !(isset($response["error"]));
        if ($this->status)
        {
            $this->response = $response["response"];
        }
    }

    /**
     * Returns if API request wasn't failed
    */
    public function is_ok() : bool
    {
        return $this->status == true;
    }
}

/**
 * Vkontakte (vk.com) API client
*/
class VkApiClient
{
    /**
     * Access token
     * https://dev.vk.com/api/access-token/getting-started
    */
    private string $token;

    /**
     * Initializes client with the given token
    */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
        * @param array<string, string> $params
    */
    public function request(string $method, array $params = []) : VKApiAnswer
    {
        $params["v"] = "5.131";
        $ch = curl_init("https://api.vk.com/method/$method");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $this->token]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $response = curl_exec($ch);
        curl_close($ch);
        return new VKApiAnswer(json_decode($response, true));
    }
}

/**
 * Vkontakte (vk.com) longpoll client
*/
class VkLongPollClient
{
    /**
     * Longpoll server URL
    */
    private string $server;

    /**
     * Secret session key
    */
    private string $key;
    
    /**
     * Last event number
    */
    private string $ts;

    /**
     * Initializes client with the given server address, secret key and ts
     * https://dev.vk.com/api/bots-long-poll/getting-started
    */
    public function __construct(string $server, string $key, string $ts)
    {
        $this->server = $server;
        $this->key = $key;
        $this->ts = $ts;
    }

    /**
        * Polls events from long poll server
        * https://dev.vk.com/api/community-events/json-schema
        * @return array<object> Array of events 
    */
    public function poll() : array
    {
        $ch = curl_init("{$this->server}?act=a_check&key={$this->key}&ts={$this->ts}&wait=25");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $this->ts = $response['ts'];
        return $response['updates'];
    }
}