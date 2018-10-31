<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\Request;

class Router
{
    private static $param = array();

    private static $baseRoute = '';

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

        self::$baseRoute = '';
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
        return self::route('ANY', $route, $callback, $option);
    }

    public static function get($route, $callback, $option = array()) 
    {
        return self::route('GET', $route, $callback, $option);
    }

    public static function post($route, $callback, $option = array())
    {
        return self::route('POST', $route, $callback, $option);
    }

    public static function put($route, $callback, $option = array())
    {
        return self::route('PUT', $route, $callback, $option);
    }

    public static function patch($route, $callback, $option = array())
    {
        return self::route('PATCH', $route, $callback, $option);
    }

    public static function delete($route, $callback, $option = array())
    {
        return self::route('DELETE', $route, $callback, $option);
    }

    public static function options($route, $callback, $option = array())
    {
        return self::route('OPTIONS', $route, $callback, $option);
    }

    public static function connect($route, $callback, $option = array())
    {
        return self::route('CONNECT', $route, $callback, $option);
    }

    public static function trace($route, $callback, $option = array())
    {
        return self::route('TRACE', $route, $callback, $option);
    }

    private static function route($method, $route, $callback, $option = array())
    {
        $route = array(
            'method' => $method,
            'route' => $route,
            'callback' => $callback,
            'param' => isset($option['param']) ? $option['param'] : array(),
            'body' => isset($option['body']) ? $option['body'] : array()
        );

        $route = self::prepareMethod($route);

        $route = self::prepareRoute($route);

        if(
            !in_array(Request::method(), $route['method'])
            || !preg_match($route['route'], Request::path(), $match)
            || !self::isValidBody($route)
        ) {
            echo "\n";
            return;
        }

        array_shift($match);

        while (count($match) < count($route['key'])) {
            $match[] = null;
        }

        $match = array_combine($route['key'], $match);

        Request::setParam($match);

        self::executeCallback($route);
    }

    private static function prepareMethod($route)
    {
        if ($route['method'] === 'ANY') {
            $route['method'] = array(Request::method());
        } else {
            if (!is_array($route['method'])) {
                $route['method'] = array($route['method']);
            }

            $route['method'] = array_map(function ($method) {
                return strtoupper($method);
            }, $route['method']);
        }

        return $route;
    }

    private static function prepareRoute($route)
    {
        preg_match_all('/\{([^\/]+)\}/', $route['route'], $match);

        $regexp = array_merge(array_fill_keys($match[1], '[^/]+'), self::$param, $route['param']);

        $route['param'] = array_filter($regexp, function ($key) use ($route) {
            return strpos($route['route'], '{'.$key.'}') !== false;
        }, ARRAY_FILTER_USE_KEY);

        $route['key'] = [];

        if (substr($route['route'], 0, 1) !== '/') {
            $route['route'] = '/'.$route['route'];
        }

        $route['route'] = preg_replace('/([^\\\\])\\(([^\\/]*)\\)/', '$1(?:$2)', $route['route']);

        foreach ($route['param'] as $name => $value) {
            $route['key'][strpos($route['route'], $name)] = $name;

            $route['route'] = str_replace('{'.$name.'}?/', '(?:('.$value.')/)?', $route['route']);
            $route['route'] = str_replace('{'.$name.'}?', '('.$value.')?', $route['route']);
            $route['route'] = str_replace('{'.$name.'}', '('.$value.')', $route['route']);
        }

        $route['route'] = str_replace('/', '\/', $route['route']);
        $route['route'] = '/^'.$route['route'].'$/';

        return $route;
    }

    private static function isValidBody($route)
    {
        if ($route['body'] === array()) {
            return true;
        }

        $body = Request::body();

        foreach ($route['body'] as $key => $value) {
            $opptionally = preg_match('/^[^\\\\]\\?$/', substr($key, -2));

            if (substr($key, -3) === '\\\\?') {
                $key = substr_replace($key, '\\', -3);
            } elseif (substr($key, -2) === '\\?') {
                $key = substr_replace($key, '?', -2);
            } elseif (substr($key, -1) === '?') {
                $key = substr_replace($key, '', -1);
            }

            if (!isset($body[$key]) && !$opptionally || isset($body[$key]) && !preg_match('/'.$value.'/', $body[$key])) {
                return false;
            }
        }

        return true;
    }

    private static function executeCallback($route)
    {
        if (gettype($route['callback']) === 'string' && strpos($route['callback'], '->') !== false) {
            $parse = explode('->', $route['callback']);

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
            $result = call_user_func($route['callback'], ...self::$next);
        }

        if ($result === true) {
            self::$next = array();
        } elseif (is_array($result)) {
            self::$next = $result;
        } else {
            App::finish();
        }
    }
}