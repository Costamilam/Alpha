<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Auth;
use Costamilam\Alpha\Token;
use Costamilam\Alpha\Request;

class Response
{
    private static $responseCode;

    private static $header = array();

    private static $body = '';

    private static $cookieConfig;

    public static function status($status)
    {
        self::$responseCode = $status;

        return __CLASS__;
    }

    public static function json($response)
    {
        self::header('Content-Type', 'application/json; charset=utf-8');

        self::$body = json_encode($response);

        return __CLASS__;
    }

    public static function header($name, $value = null, $replace = true)
    {
        if ($value === null) {
            if (isset(self::$header[$name])) {
                unset(self::$header[$name]);
            }
        } else {
            self::$header[$name] = isset(self::$header[$name]) && !$replace ? array(self::$header[$name], $value) : $value;
        }

        return __CLASS__;
    }

    public static function multiHeader($header)
    {
        foreach ($header as $name => $value) {
            self::header($name, $value);
        }

        return __CLASS__;
    }

    public static function cache($minutes, $lastModified = 'now')
    {
        if ($minutes === false || $minutes === 0) {
            self::multiHeader(array(
                'Pragma'=> 'no-cache',
                'Expires' => gmdate('D, d M Y H:i:s').' GMT',
                'Vary' => '*',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0'
            ))
            ::header('Cache-Control', 'post-check=0, pre-check=0', false);
        } else {
            self::multiHeader(array(
                'Expires' => gmdate('D, d M Y H:i:s', App::startedAt() + $minutes * 60) . ' GMT',
                'Cache-Control' => 'public, max-age='.($minutes * 60),
                'Pragma' => 'max-age='.($minutes * 60)
            ));
        }

        self::header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified !== 'now' ? $lastModified : App::startedAt()).' GMT');

        return __CLASS__;
    }

    public static function configureCookie($expireInMinutes, $domain = 'HTTP_HOST', $secure = true, $httponly = true)
    {
        $domain = $domain === 'HTTP_HOST' ? Request::header('Host') ?: '' : $domain;

        $expireInMinutes = time() + 60 * $expireInMinutes;

        self::$cookieConfig['expire'] = $expireInMinutes;
        self::$cookieConfig['domain'] = $domain;
        self::$cookieConfig['secure'] = $secure;
        self::$cookieConfig['httponly'] = $httponly;
    }

    public static function cookie($name, $value, $expireInMinutes = null)
    {
        //$value = json_encode($value);

        $expireInMinutes = $expireInMinutes === null ? self::$cookieConfig['expire'] : time() + 60 * $expireInMinutes;

        setcookie($name, urlencode($value), $expireInMinutes, '/', self::$cookieConfig['domain'], self::$cookieConfig['secure'], self::$cookieConfig['httponly']);
    }

    public static function token($token)
    {
        if ($token === null) {
            self::header('Token');
        } else {
            self::header('Token', $token);
        }
    }

    public static function dispatch()
    {
        if (self::$responseCode !== null) {
            http_response_code(self::$responseCode);
        }

        foreach (self::$header as $name => $value) {
            if (!is_array($value)) {
                $value = array($value);
            }

            foreach ($value as $header) {
                header($name.': '.$header, true);
            }
        }

        echo self::$body;
    }
}
