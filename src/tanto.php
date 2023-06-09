<?php

namespace Ren\Tanto;

class Tanto
{
    /**
     * @var array<Backend>
    */
    private array $backends = [];

    /**
     * @var array<string, Callable>
    */
    private array $handlers = [];

    public function add_backend(Backend $backend) : void
    {
        array_push($this->backends, $backend);
    }
    
    public function on_message(Callable $callback) : void
    {
        $this->handlers["message"] = $callback;
    }

    public function start() : void
    {
        foreach ($this->backends as $backend)
        {
            # TODO: Think if I should pass it here
            $backend->on_start($this->handlers);
        }
        
        while (true) /** @phpstan-ignore-line */
        {
            foreach ($this->backends as $backend)
            {
                $backend->loop();
            }
        }
    }

    public function stop() : void
    {
        foreach ($this->backends as $backend)
        {
            $backend->on_shutdown();
        }
    }
}