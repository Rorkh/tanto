<?php

namespace Ren\Tanto;

class Tanto
{
    private array $backends = [];
    private array $handlers = [];

    public function add_backend($backend)
    {
        array_push($this->backends, $backend);
    }

    public function on_message($callback)
    {
        $this->handlers["message"] = $callback;
    }

    public function start()
    {
        foreach ($this->backends as $backend)
        {
            # TODO: Think if I should pass it here
            $backend->on_start($this->handlers);
        }
        
        while (true)
        {
            foreach ($this->backends as $backend)
            {
                $backend->loop();
            }
        }
    }

    public function stop()
    {
        foreach ($this->backends as $backend)
        {
            $backend->on_shutdown();
        }
    }
}