<?php

namespace Costamilam\SF;

use Costamilam\SF\Token;
use Costamilam\SF\Request;
use Costamilam\SF\Response;

class Auth extends Token
{
    private static $httpHeader = false;
    private static $cookie = false;

    public static function enableCookieMode()
    {
        self::$httpHeader = false;
        self::$cookie = true;
    }

    public static function enableHTTPHeaderMode()
    {
        self::$httpHeader = true;
        self::$cookie = false;
    }

    public static function route($method, $route, $callback = null)
    {
        if ((strtoupper($method) === "ANY" || strtoupper($method) === Request::method()) && preg_match("/^".str_replace("/", "\/", $route)."$/", Request::path())) {
            if (self::$cookie) {
                $auth = Request::cookie("Token");

                parent::verify($auth, $callback);
            }

            if (self::$httpHeader) {
                $auth = Request::header("Authorization");

                if ($auth !== null) {
                    preg_match("/(.*): (.*)/", $auth, $auth);

                    if (isset($auth[1]) && strtolower($auth[1]) === "bearer" && isset($auth[2])) {
                        parent::verify($auth[2], $callback);
                    }
                }
            }
        }
    }

    public static function sendToken($subject, $data = null)
    {
        $token = parent::create($subject, $data);

        if ($token === false) {
            return false;
        }

        if (self::$cookie) {
            Response::cookie("Token", $token->__toString(), parent::$expire);
        }

        if (self::$httpHeader) {        
            Response::header("Token", $token);
        }

        return $token;
    }

    private static function removeToken()
    {
        if (self::$cookie) {
            Response::cookie("Token", "", 0);
        }

        if (self::$httpHeader) {
            Response::header("Token");
        }
    }
}
