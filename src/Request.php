<?php

namespace Costamilam\Alpha;

class Request
{
    private static $method;
    private static $header;
    private static $path;
    private static $param;
    private static $body;

    public static function load()
    {
        self::$method = strtoupper($_SERVER['REQUEST_METHOD']);

        //$route = preg_quote($route);
        //$route = rtrim($route, "/");
        self::$path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);

        if (isset($_SERVER['QUERY_STRING'])) {
            self::$path = str_replace('?'.$_SERVER['QUERY_STRING'], '', self::$path);
        }

        self::loadHeader();

        self::$param = array();

        $contentType = self::header('Content-Type');
        $contentType = str_replace('/', '\/', $contentType);
        if (preg_match('/'.$contentType.'/i', 'application/json')) {
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
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach ($_SERVER as $key => $val) {
                if (preg_match($rx_http, $key)) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    $rx_matches = explode('_', $arh_key);
                    if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            self::$header = $arh;
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
            return self::getIndex(self::$param, $index);
        }
    }

    public static function body(...$index)
    {
        if (count($index) === 0) {
            return self::$body ?: array();
        } else {
            return self::getIndex(self::$body, $index);
        }
    }

    private static function getIndex($array, $index)
    {
        $data = array();

        foreach ($index as $name) {
            $data[$name] = isset($array[$name]) ? $array[$name] : null;
        }

        return $data;
    }
}
