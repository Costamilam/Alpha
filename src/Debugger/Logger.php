<?php

namespace Costamilam\Alpha\Debugger;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Debugger\Exception;

class Logger
{
    private static $started = false;

    private static $loggerPath = null;

    public static $loggerFile = null;

    public static function start($path, $overwrite = true) {
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        if (!file_exists($path) || !is_dir($path)) {
            mkdir($path);
        }

        self::$loggerPath = $path;

        if ($overwrite === true) {
            self::$loggerFile = 'logger.log.json';
        } else {
            self::$loggerFile = App::startedAt('Y-m-d_H:i:s_u').'.log.json';
        }

        if (App::isDevMode()) {
            self::writeFile(self::$loggerFile, array(
                'startedAt' => App::startedAt('Y-m-d H:i:s'),
                'domain' => Request::header('Host'),
                'ip' => Request::header('Host'),
                'method' => Request::method(),
                'path' => Request::path(),
                'body' => Request::body(),
                'routes' => array()
            ));
        }

        self::$started = true;
    }

    public static function error($type)
    {
        if (!self::$started) {
            return;
        }

        if (App::isProdMode()) {
            self::writeFile('production-error.log.json', array(
                'date' => App::startedAt(),
                'origin' => Request::header('Host'),
                'ip' => Request::header('Host'),
                'method' => Request::method(),
                'to' => Request::path(),
                'body' => Request::body(),
                'error' => (new Exception($type))->toJSON()
            ));
        } else {
            throw new Exception($type);
        } 
    }

    public static function log($data, $overwrite = true)
    {
        if (!self::$started) {
            return;
        }

        $content = $data;

        if (!$overwrite) {
            $content = self::readFile('custom.log.json');

            if (!is_array($content)) {
                $content = array(
                    App::startedAt() => array($data)
                );
            } elseif (!isset($content[App::startedAt()])) {
                $content[App::startedAt()] = array($data);
            } else {
                $content[App::startedAt()][] = $data;
            }
        }

        self::writeFile('custom.log.json', $content);
    }

    public static function logRoute($route, $executed)
    {
        if (App::isProdMode() || !self::$started) {
            return;
        }

        $content = self::readFile(self::$loggerFile);

        $content['routes'][] = array(
            'executed' => $executed,
            'route' => $route,
            'parameter' => Request::param()
        );

        self::writeFile(self::$loggerFile, $content);
    }

    private static function readFile($fileName) {
        return json_decode(
            file_get_contents(self::$loggerPath.$fileName),
            true
        );
    }

    private static function writeFile($fileName, $content) {
        return file_put_contents(
            self::$loggerPath.$fileName,
            json_encode($content, JSON_PRETTY_PRINT)
        );
    }
}