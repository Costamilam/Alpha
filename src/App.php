<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;

class App
{
    private static $instance;

    public static function start()
    {
        if (self::$instance === null) {
            self::$instance = new App();
        }
    }

    public static function finish()
    {
        if (self::$instance !== null) {
            self::$instance->__destruct();
        }
    }

    private function __construct()
    {
        ob_start();

        Request::load();

        Response::configureCookie(time() + 60 * 30, "HTTP_HOST", false, true);
    }

    public function __destruct()
    {
        exit;
    }
}