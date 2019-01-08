<?php

namespace Costamilam\Alpha;

class Request
{
    private static $method;

    private static $path;

    private static $param;

    private static $header;

    private static $body;

    public static function load()
    {
        self::$method = strtoupper($_SERVER['REQUEST_METHOD']);

        //$route = preg_quote($route);
        //$route = rtrim($route, "/");
        self::$path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']) ?: '/';

        if (isset($_SERVER['QUERY_STRING'])) {
            self::$path = str_replace('?'.$_SERVER['QUERY_STRING'], '', self::$path);
        }

        self::loadHeader();

        self::$param = array();

        if (strcasecmp(self::header('Content-Type'), 'application/json')) {
            self::$body = json_decode(file_get_contents('php://input'), true);
        } else {
            parse_str(file_get_contents('php://input'), self::$body);
        }
    }

    private static function loadHeader()
    {
        if (function_exists('apache_request_headers')) {
            self::$header = apache_request_headers();
        } else {
            self::$header = array();

            foreach ($_SERVER as $key => $val) {
                if (preg_match('/\AHTTP_/', $key)) {
                    $key = preg_replace('/\AHTTP_/', '', $key);

                    $matches = explode('_', $key);

                    if (count($matches) > 0 && strlen($key) > 2) {
                        foreach($matches as $index => $value) {
                            $matches[$index] = ucfirst($value);
                        }

                        $key = implode('-', $matches);
                    }

                    self::$header[$key] = $val;
                }
            }
        }
    }

    public static function method()
    {
        return self::$method;
    }

    public static function header($name)
    {
        foreach (self::$header as $key => $value) {
            if (strtolower($key) === strtolower($name)) {
                return $value;
            }
        }
    }

    public static function cookie($name)
    {
        $cookieHeader = self::header('Cookie');

        if ($cookieHeader === null) {
            return null;
        }

        foreach (explode('; ', $cookieHeader) as $cookie) {
            list($key, $value) = explode('=', $cookie);

            if ($key === $name) {
                return urldecode($value);
            }
        }
    }

    public static function token()
    {
        if (Auth::mode() === 'cookie') {
            return self::cookie('Token');
        } elseif (Auth::mode() === 'header') {
            $auth = self::header('Token');

            if ($auth !== null) {
                $auth = explode(' ', $auth, 2);

                $auth = isset($auth[0]) && strtolower($auth[0]) === 'bearer' && isset($auth[1]) ? $auth[1] : null;
            }

            return $auth;
        }
    }

    public static function path()
    {
        return self::$path;
    }

    public static function setParam($value)
    {
        self::$param = $value;
    }

    public static function param(...$index)
    {
        if (count($index) === 0) {
            return self::$param;
        } else {
            return self::getByIndex(self::$param, $index);
        }
    }

    public static function body(...$index)
    {
        if (count($index) === 0) {
            return self::$body ?: array();
        } else {
            return self::getByIndex(self::$body, $index);
        }
    }

    private static function getByIndex($array, $index)
    {
        $data = array();

        foreach ($index as $name) {
            $data[$name] = isset($array[$name]) ? $array[$name] : null;
        }

        return $data;
    }
}
