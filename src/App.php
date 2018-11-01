<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;
use Costamilam\Alpha\DB;
use Costamilam\Alpha\Debugger;

class App
{
    private static $instance = null;

    private static $mode = null;

    public static function isDevMode()
    {
        if (self::$mode === null) {
            Debugger::error('applicationNotYetStartedOnVerifyIfIsInDevMode');
        }

        return self::$mode;
    }

    public static function isProdMode()
    {
        if (self::$mode === null) {
            Debugger::error('applicationNotYetStartedOnVerifyIfIsInProdMode');
        }

        return !self::$mode;
    }

    public static function start($mode = "dev", $loggerPath = __DIR__.'/logs/')
    {
        if (self::$instance === null) {
            self::$instance = new App($mode, $loggerPath);
        } else {
            Debugger::error('applicationAlreadyStartedOnStartApplication');
        }
    }

    public static function finish()
    {
        if (self::$instance !== null) {
            self::$instance->__destruct();
        } else {
            Debugger::error('applicationNotYetStartedOnFinishExecution');
        }
    }

    private function __construct($mode, $loggerPath)
    {
        ob_start();

        if (strcasecmp($mode, 'dev') == 0) {
            self::$mode = true;
        } elseif (strcasecmp($mode, 'prod') == 0) {
            self::$mode = false;
        } else {
            Debugger::error('invalidModeValueOnStartApplication');
        }

        Request::load();

        Response::configureCookie(30, '', false, true);

        Debugger::start($loggerPath);
    }

    public function __destruct()
    {
        DB::disconnect();

        exit;
    }
}