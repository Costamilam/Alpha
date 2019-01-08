<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Token;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;

class Auth
{
    private static $callback;

    private static $executed = array();

    private static $mode = true;

    public static function enableCookieMode()
    {
        self::$mode = 'cookie';
    }

    public static function enableHTTPHeaderMode()
    {
        self::$mode = 'header';
    }

    public static function mode()
    {
        return self::$mode;
    }

    private static function onStatus($status, $callback)
    {
        if (is_array($status)) {
            foreach ($status as $value) {
                self::$callback[$value] = $callback;
            }
        } else {
            self::$callback[$status] = $callback;
        }
    }

    private static function callStatus($status, ...$param)
    {
        // if (in_array($status, self::$executed)) {
        //     return;
        // }

        // self::$executed[] = $status;

        if (isset(self::$callback[$status]) && is_callable(self::$callback[$status])) {
            call_user_func(self::$callback[$status], ...$param);
        } else {
            $response = array(
                'empty' => 401,
                'expired' => 401,
                'invalid' => 401,
                'forbidden' => 403
            );

            if (isset($response[$status])) {
                Response::status($response[$status]);
            }
        }
    }

    // public static function dispatch()
    // {
    //     foreach (self::$route as $route) {
    //         if (
    //             (
    //                 strtoupper($route['method']) === 'ANY' ||
    //                 strtoupper($route['method']) === Request::method()
    //             ) &&
    //             preg_match('/^'.str_replace('/', '\/', $route['route']).'$/', Request::path()) &&
    //             parent::verify(self::getToken(), $route['callback']) === false
    //         ) {
    //             return false;
    //         }
    //     }

    //     return true;
    // }
}
