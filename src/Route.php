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

    private static $next = array();

    public static function addParamValidator($regexp)
    {
        foreach ($regexp as $name => $value) {
            self::$param[$name] = $value;
        }

        return __CLASS__;
    }

    public static function addBodyParamValidator($regexp)
    {
        foreach ($regexp as $name => $value) {
            self::$bodyParam[$name] = $value;
        }

        return __CLASS__;
    }

    protected static function create($method, $route, $callback, $option = array())
    {
        $config = array(
            'method' => $method,
            'route' => $route,
            'callback' => $callback,
            'param' => isset($option['param']) ? $option['param'] : array(),
            'body' => isset($option['body']) ? $option['body'] : array()
        );

        $original = $config;

        self::prepareMethod($config);

        self::prepareRoute($config);

        if (
            !in_array(Request::method(), $config['method'])
            || !preg_match($config['route'], Request::path(), $match)
            || !self::isValidBody($config)
        ) {
            Logger::logRoute($original, false);
            return false;
        }

        Logger::logRoute($original, true);

        array_shift($match);

        while (count($match) < count($config['key'])) {
            $match[] = null;
        }

        $match = array_combine($config['key'], $match);

        $config['match'] = $match;

        return $config;
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

    private static function prepareRoute(&$route)
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
        Logger::logRoute($route, true);

        Request::setParam($route['match']);

        if (gettype($route['callback']) === 'string' && strpos($route['callback'], '->') !== false) {
            $parse = explode('->', $route['callback']);

            $object = Router::getInstance($parse[0]);

            if ($object === null) {
                $object = new $parse[0];

                Router::addInstance($parse[0], $object);
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
