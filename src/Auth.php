<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Token;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Response;

class Auth extends Token
{
    private static $httpHeader = false;
    private static $cookie = false;

    public static function configureToken($algorithm, $key, $issuer, $audience, $expireInMinutes)
    {
        parent::configure($algorithm, $key, $issuer, $audience, $expireInMinutes);
    }

    public static function onStatus($status, $callback)
    {
        parent::onStatus($status, $callback);
    }

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
            parent::verify(self::getToken(), $callback);
        }
    }

    public static function createToken($subject, $data = null)
    {
        return parent::create($subject, $data)->__toString();
    }

    public static function setToken($token)
    {
        if (self::$cookie) {
            Response::cookie("Token", $token, parent::$expire);
        } elseif (self::$httpHeader) {        
            Response::header("Token", $token);
        }

        return $token;
    }

    public static function getToken()
    {
        if (self::$cookie) {
            return Request::cookie("Token");
        } elseif (self::$httpHeader) {
            $auth = Request::header("Authorization");

            if ($auth !== null) {
                preg_match("/(.*) (.*)/", $auth, $auth);

                $auth = isset($auth[2]) ? $auth[2] : null;
            }

            return $auth;
        }
    }

    public static function removeToken()
    {
        if (self::$cookie) {
            Response::cookie("Token", "", 0);
        } elseif (self::$httpHeader) {
            Response::header("Token");
        }
    }
}
