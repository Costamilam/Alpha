<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Router;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Debugger\Logger;

class Route
{
    private static $param = array();

    private static $bodyParam = array();

    private static $next = null;

    private static $instance = array();

    public static function addInstance($name, $object) {
        self::$instance[$name] = $object;
    }

    public static function addPathParamValidator($validator)
    {
        foreach ($regexp as $name => $value) {
            self::$param[$name] = $value;
        }

        return __CLASS__;
    }

    public static function addBodyParamValidator($validator)
    {
        foreach ($regexp as $name => $value) {
            self::$bodyParam[$name] = $value;
        }

        return __CLASS__;
    }

    public static function next(...$argument)
    {
        self::$next = $argument;
    }

    protected static function call($config)
    {
        $original = $config;

        self::prepareMethod($config);

        self::preparePath($config);

        if (
            !in_array(Request::method(), $config['method'])
            || $config['match'] === null
            || !self::isValidBody($config)
        ) {
            Logger::logRoute($original, false);
        } else {
            Logger::logRoute($original, true);

            return self::execute($config);
        }
    }

    private static function prepareMethod(&$route)
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

    private static function preparePath(&$route)
    {
        preg_match_all('/\{([^\/]+)\}/', $route['route'], $match);

        $regexp = array_merge(array_fill_keys($match[1], '[^/]+'), self::$param, $route['param']);

        $route['param'] = array_filter($regexp, function ($key) use ($route) {
            return strpos($route['route'], '{'.$key.'}') !== false;
        }, ARRAY_FILTER_USE_KEY);

        $route['key'] = array();

        if (substr($route['route'], 0, 1) !== '/') {
            $route['route'] = '/'.$route['route'];
        }

        $route['route'] = preg_replace('/([^\\\\])\\(([^\\/]*)\\)/', '$1(?:$2)', $route['route']);

        $paramFunction = array();

        foreach ($route['param'] as $name => $value) {
            if (is_callable($value)) {
                $paramFunction[$name] = $value;

                $value = '[^/]+';
            }

            $route['key'][strpos($route['route'], $name)] = $name;

            $route['route'] = str_replace('{'.$name.'}?/', '(?:('.$value.')/)?', $route['route']);
            $route['route'] = str_replace('{'.$name.'}?', '('.$value.')?', $route['route']);
            $route['route'] = str_replace('{'.$name.'}', '('.$value.')', $route['route']);
        }

        $route['route'] = str_replace('/', '\/', $route['route']);
        $route['route'] = '/^'.$route['route'].'$/';

        if (preg_match($route['route'], Request::path(), $match) === 0) {
            $route['match'] = null;

            return $route;
        }

        array_shift($match);

        while (count($match) < count($route['key'])) {
            $match[] = null;
        }

        $match = array_combine($route['key'], $match);

        $route['match'] = $match;

        foreach ($paramFunction as $name => $callback) {
            if (!call_user_func($callback, $route['match'][$name])) {
                $route['match'] = null;
    
                return $route;
            }
        }

        return $route;
    }

    private static function isValidBody($route)
    {
        $body = array_merge(self::$bodyParam, $route['body']);

        if (empty($route['body'])) {
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

            if (
                !isset($body[$key]) &&
                !$opptionally ||
                isset($body[$key]) &&
                (
                    is_callable($value) &&
                    call_user_func($value, $body[$key]) !== true ||
                    !preg_match('/'.$value.'/', $body[$key])
                )
            ) {
                return false;
            }
        }

        return true;
    }

    protected static function execute($route)
    {
        $argument = self::$next ?: array();

        self::$next = null;

        Logger::logRoute($route, true);

        Request::setParam($route['match']);

        if (gettype($route['callback']) === 'string' && strpos($route['callback'], '->') !== false) {
            $parse = explode('->', $route['callback']);

            if (isset(self::$instance[$parse[0]])) {
                $object = new $parse[0];

                self::addInstance($parse[0], $object);
            } else {
                $object = self::$instance[$parse[0]];
            }

            $object->{$parse[1]}(...$argument);
        } else {
            call_user_func($route['callback'], ...$argument);
        }

        if (self::$next === null) {
            return false;
        }
    }
}
