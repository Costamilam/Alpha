<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\DB;
use Costamilam\Alpha\Auth;
use Costamilam\Alpha\Router;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;
use Costamilam\Alpha\Debugger\Logger;

class App
{
    private static $instance = null;

    private static $mode = null;

    public static $startedAt;

    public static function isDevMode()
    {
        if (self::$mode === null) {
            Logger::error('applicationNotYetStartedOnVerifyIfIsInDevMode');
        }

        return self::$mode;
    }

    public static function isProdMode()
    {
        if (self::$mode === null) {
            Logger::error('applicationNotYetStartedOnVerifyIfIsInProdMode');
        }

        return !self::$mode;
    }

    public static function startedAt()
    {
        return self::$startedAt;
    }

    public static function start($mode)
    {
        if (self::$instance === null) {
            self::$instance = new App($mode);
        } else {
            Logger::error('applicationAlreadyStartedOnStartApplication');
        }
    }

    private function __construct($mode)
    {
        self::$startedAt = date('Y-m-d-H:i:s');

        ob_start();

        if (strcasecmp($mode, 'dev') == 0) {
            self::$mode = true;
        } elseif (strcasecmp($mode, 'prod') == 0) {
            self::$mode = false;
        } else {
            Logger::error('invalidModeValueOnStartApplication');
        }

        Request::load();

        Response::configureCookie(30, '', false, true);
    }

    public function __destruct()
    {
        if (Auth::dispatch() === true) {
            Router::dispatch();
        }

        DB::disconnect();

        Response::dispatch();

        exit;
    }
}
