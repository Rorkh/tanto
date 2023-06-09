<?php

namespace Ren\Tanto;

interface Backend
{
    function get_name() : string;
    /**
        * @param array<string, Callable> $handlers
    */
    function on_start(array $handlers) : void;
    function on_shutdown() : void;
    function loop() : void;
}