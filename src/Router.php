<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Route;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Debugger;

class Router extends Route
{
    private static $baseRoute = '';

    private static $instances = array();

    private static $next = array();

    private static $route = array();

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

    public static function path($baseRoute, $group) 
    {
        self::$baseRoute = $baseRoute;

        $group();

        self::$baseRoute = '';
    }

    public static function dispatch()
    {
        foreach (self::$route as $config) {
            parent::execute($config);
        }
    }

    public static function set($method, $route, $callback, $option = array())
    {
        $route = parent::create($method, self::$baseRoute.$route, $callback, $option);

        if ($route !== false) {
            self::$route[] = $route;
        }
    }

    public static function any($route, $callback, $option = array())
    {
        return self::set('ANY', $route, $callback, $option);
    }

    public static function get($route, $callback, $option = array()) 
    {
        return self::set('GET', $route, $callback, $option);
    }

    public static function post($route, $callback, $option = array())
    {
        return self::set('POST', $route, $callback, $option);
    }

    public static function put($route, $callback, $option = array())
    {
        return self::set('PUT', $route, $callback, $option);
    }

    public static function patch($route, $callback, $option = array())
    {
        return self::set('PATCH', $route, $callback, $option);
    }

    public static function delete($route, $callback, $option = array())
    {
        return self::set('DELETE', $route, $callback, $option);
    }

    public static function options($route, $callback, $option = array())
    {
        return self::set('OPTIONS', $route, $callback, $option);
    }

    public static function connect($route, $callback, $option = array())
    {
        return self::set('CONNECT', $route, $callback, $option);
    }

    public static function trace($route, $callback, $option = array())
    {
        return self::set('TRACE', $route, $callback, $option);
    }
}
