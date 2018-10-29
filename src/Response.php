<?php

namespace Costamilam\Alpha;

class Response
{
    private static $cookie;

    public static function status($status)
    {
        http_response_code($status);

        return __CLASS__;
    }

    public static function json($response)
    {
        self::header('Content-Type', 'application/json; charset=utf-8');

        ob_end_clean();

        echo json_encode($response);

        return __CLASS__;
    }

    public static function text($response)
    {
        self::header('Content-Type', 'text/*; charset=utf-8');

        ob_end_clean();

        echo $response;

        return __CLASS__;
    }

    public static function header($name, $value = null, $replace = true)
    {
        if ($value !== null) {
            header($name.': '.$value, $replace);
        } else {
            header_remove($name);
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

    public static function cache($seconds)
    {
        if ($seconds === false || $seconds === 0) {
            header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Vary: *');
        } else {
            header('Cache-Control: public, max-age='.($seconds*60));
        }

        return __CLASS__;
    }

    public static function configureCookie($expireInMinutes, $domain = 'HTTP_HOST', $secure = true, $httponly = true)
    {
        $domain = $domain === 'HTTP_HOST' ? Request::header('Host') ?: '' : $domain;

        $expireInMinutes = time() + 60 * $expireInMinutes;

        self::$cookie['expire'] = $expireInMinutes;
        self::$cookie['domain'] = $domain;
        self::$cookie['secure'] = $secure;
        self::$cookie['httponly'] = $httponly;
    }

    public static function cookie($name, $value, $expireInMinutes = null)
    {
        //$value = json_encode($value);

        $expireInMinutes = $expireInMinutes === null ? self::$cookie['expire'] : time() + 60 * $expireInMinutes;

        setcookie($name, urlencode($value), $expireInMinutes, '/', self::$cookie['domain'], self::$cookie['secure'], self::$cookie['httponly']);
    }
}
