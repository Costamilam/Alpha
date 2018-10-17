<?php

namespace Costamilam\SF;

class Request
{
    private static $method;
    private static $header;
    private static $path;
    protected static $param;
    private static $body;

    public static function load()
    {
        self::$method = strtoupper($_SERVER["REQUEST_METHOD"]);

        //$route = preg_quote($route);
        //$route = rtrim($route, "/");
        self::$path = str_replace($_SERVER["SCRIPT_NAME"], "", $_SERVER["REQUEST_URI"]);

        if (isset($_SERVER["QUERY_STRING"])) {
            self::$path = str_replace("?".$_SERVER["QUERY_STRING"], "", self::$path);
        }

        self::$header = apache_request_headers();

        $contentType = self::header("Content-Type");
        $contentType = str_replace("/", "\/", $contentType);
        if (preg_match("/$contentType/i", "application/json")) {
            self::$body = json_decode(file_get_contents("php://input"), true);
        } else {
            parse_str(file_get_contents("php://input"), self::$body);
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
        foreach ($_COOKIE as $key => $value) {
            if ($key === $name) {
                return $value;
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

    public static function param()
    {
        return self::$param;
    }

    public static function body(...$index)
    {
        if (count($index) === 0) {
            return self::$body;
        } else {
            $array = array();

            foreach ($index as $name) {
                $array[$name] = isset(self::$body[$name]) ? self::$body[$name] : null;
            }

            return $array;
        }
    }
}