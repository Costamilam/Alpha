<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Route;
use Costamilam\Alpha\Request;

class Router extends Route
{
    private static $prefix = '';

    private static $instances = array();

    private static $next = array();

    private static $route = array();

    public static function addInstance($name, $object) {
        self::$instances[$name] = $object;
    }

    public static function getInstance($name) {
        return self::$instances[$name];
    }

    public static function fromFile($path, $file) {
        if (substr($path, 0, 1) !== '/') {
            $path = '/'.$path;
        }

        if (strpos(Request::path(), $path) === 0) {
            self::$prefix = $path;

            require $file;

            self::$prefix = '';
        }
    }

    public static function dispatch()
    {
        foreach (self::$route as $config) {
            if (parent::call($config) === false) {
                break;
            }
        }
    }

    public static function set($method, $route, $callback, $option = array())
    {
        self::$route[] = array(
            'method' => $method,
            'route' => self::$prefix.$route,
            'callback' => $callback,
            'param' => isset($option['param']) ? $option['param'] : array(),
            'body' => isset($option['body']) ? $option['body'] : array()
        );
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
