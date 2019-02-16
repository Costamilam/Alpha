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

    private static $startedAt;

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

    public static function startedAt($format = 'Y-m-d H:i:s.u')
    {
        return self::$startedAt->format($format);
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
        self::$startedAt = date_create_from_format('U.u', microtime(true));

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
        Router::dispatch();

        DB::disconnect();

        Response::dispatch();

        exit;
    }
}
