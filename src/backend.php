<?php

namespace Ren\Tanto;

interface Backend
{
    function get_name();
    function on_start($handlers);
    function on_shutdown();
    function loop();
}