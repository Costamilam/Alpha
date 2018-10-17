<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Request;

class Router
{
    private static $param = array();

    private static $baseRoute = "";

    private static $instances = array();

    private static $next = array();

    public static function addInstance($name, $object) {
        self::$instances[$name] = $object;
    }

    public static function getInstance($name) {
        foreach (self::$instances as $key => $value) {
            if ($name === $key) {
                return $value;
            }
        }
    }

    public static function addParamRegExp($regexp)
    {
        foreach ($regexp as $name => $value) {
            self::$param[$name] = $value;
        }

        return __CLASS__;
    }

    public static function getParamRegExp()
    {
        return self::$param;
    }

    public static function path($baseRoute, $group) 
    {
        self::$baseRoute = $baseRoute;

        $group();

        self::$baseRoute = "";
    }

    public static function set($method, $route, $callback, $option = array())
    {
        /*if (is_array($method)) {
            foreach ($method as $value) {
                self::set($value, $route, $callback);
            }
        } else {
            return new Route($method, self::$baseRoute.$route, $callback);
        }*/
        self::route($method, $route, $callback, $option);
    }

    public static function any($route, $callback, $option = array())
    {
        return self::route("ANY", $route, $callback, $option);
    }

    public static function get($route, $callback, $option = array()) 
    {
        return self::route("GET", $route, $callback, $option);
    }

    public static function post($route, $callback, $option = array())
    {
        return self::route("POST", $route, $callback, $option);
    }

    public static function put($route, $callback, $option = array())
    {
        return self::route("PUT", $route, $callback, $option);
    }

    public static function patch($route, $callback, $option = array())
    {
        return self::route("PATCH", $route, $callback, $option);
    }

    public static function delete($route, $callback, $option = array())
    {
        return self::route("DELETE", $route, $callback, $option);
    }

    private static function route($method, $route, $callback, $option = array())
    {
        $route = array(
            "method" => $method,
            "route" => $route,
            "callback" => $callback,
            "pathMatchFull" => isset($option["pathMatchFull"]) ? $option["pathMatchFull"] : true,
            "param" => isset($option["param"]) ? $option["param"] : array()
        );

        if ($method === "ANY") {
            $route["method"] = array(Request::method());
        } else {
            if (!is_array($method)) {
                $method = array($method);
            }

            $route["method"] = array_map(function ($method) {
                return strtoupper($method);
            }, $method);
        }
        //$this->route = rtrim($route, "/");

        preg_match_all("/\{([^\/]+)\}/", $route["route"], $match);

        $regexp = array_merge(array_fill_keys($match[1], "[^/]+"), self::$param, $route["param"]);

        $route["param"] = array_filter($regexp, function ($key) use ($route) {
            return strpos($route["route"], $key) !== false;
        }, ARRAY_FILTER_USE_KEY);

        $key = [];
        $routeAux = $route["route"];

        foreach ($regexp as $name => $value) {
            $key[strpos($route["route"], $name)] = $name;

            $routeAux = str_replace('{'.$name.'}', "($value)", $routeAux);
        }

        $routeAux = str_replace("/", "\/", $routeAux);
        $routeAux = "/^{$routeAux}".($route["pathMatchFull"] ? "" : ".*")."$/";

        if(
            !in_array(Request::method(), $route["method"])
            || !preg_match($routeAux, Request::path(), $match)
        ) {
            return;
        }

        array_shift($match);

        $match = array_combine($key, $match);        

        Request::setParam($match);

        if (gettype($route["callback"]) === "string" && strpos($route["callback"], "->") !== false) {
            $parse = explode("->", $route["callback"]);

            $object = array_filter(self::$instances, function ($name) use ($parse) {
                return $name === $parse[0];
            }, ARRAY_FILTER_USE_KEY);
            $object = array_shift($object);

            if ($object === null) {
                $object = new $parse[0];
                self::addInstance($parse[0], $object);
            }

            $result = $object->{$parse[1]}(...self::$next);
        } else {
            $result = call_user_func($route["callback"], ...self::$next);
        }

        if ($result === false) {
            App::finish();
        } elseif ($result !== null) {
            self::$next = is_array($result) ? $result : array($result);
        } else {
            self::$next = [];
        }
    }
}