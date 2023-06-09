<?php

namespace Ren\Tanto;

class VKApiAnswer
{
    private bool $status;
    public mixed $response;

    public function __construct(mixed $response)
    {
        $this->status = !(isset($response["error"]));
        if ($this->status)
        {
            $this->response = $response["response"];
        }
    }

    public function is_ok() : bool
    {
        return $this->status == true;
    }
}

class VkApiClient
{
    private string $token;

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

class VkLongPollClient
{
    private string $server;
    private string $key;
    private string $ts;

    public function __construct(string $server, string $key, string $ts)
    {
        $this->server = $server;
        $this->key = $key;
        $this->ts = $ts;
    }

    /**
        * @return array<object>
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