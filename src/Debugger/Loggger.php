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

    public static $debuged = false;

    public static function start($path, $overwrite = true) {
        self::$started = true;

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
            for ($i = 0; file_exists(self::$loggerPath.App::$startedAt.'_'.$i.'.log.json'); $i++) {  }

            self::$loggerFile = App::$startedAt.'_'.$i.'.log.json';
        }

        if (App::isDevMode()) {
            self::writeInFile(self::$loggerFile, array(
                'startedAt' => App::$startedAt,
                'domain' => Request::header('Host'),
                'ip' => Request::header('Host'),
                'method' => Request::method(),
                'route' => Request::path(),
                'body' => Request::body()
            ));
        }
    }

    public static function error($type)
    {
        if (!self::$started) {
            return;
        }

        if (App::isProdMode()) {
            self::writeInFile('production-error.log.json', array(
                'date' => App::$startedAt,
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

        if (!$overwrite) {
            $content = json_decode(file_get_contents(self::$loggerPath.'custom.log.json'));

            if (isset($content[App::startedAt()])) {
                $content[App::startedAt()][] = $data;
            } else {
                $content[App::startedAt()] = array($data);
            }

            self::writeInFile($content, 'custom.log.json');
        }
    }

    public static function logRoute($route, $executed)
    {
        if (App::isProdMode() || !self::$started) {
            return;
        }

        self::writeInFile(self::$loggerFile, array(
            'executed' => $executed,
            'route' => $route,
            'parameter' => Request::param(),
            'body' => Request::body()
        ));
    }

    private static function writeInFile($fileName, $content) {
        $file = fopen(self::$loggerPath.$fileName, 'a');

        fwrite($file, json_encode($content));

        fclose($file);
    }
}