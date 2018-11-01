<?php

namespace Costamilam\Alpha;

use Costamilam\Alpha\App;
use Costamilam\Alpha\Request;
use Costamilam\Alpha\Exception;

class Debugger
{
    private static $loggerPath = __DIR__.'/log/';

    public static $loggerFile = '';

    public static $debuged = false;

    public static function start($path)
    {
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        if (!file_exists($path) || !is_dir($path)) {
            mkdir($path);
        }

        self::$loggerPath = $path;

        $date = date('Y-m-d-H:i:s-');

        for ($i = 0; file_exists(self::$loggerPath.$date.$i.'.log'); $i++) {  }

        self::$loggerFile = $date.$i.'.log';

        if (App::isDevMode()) {
            $file = fopen(self::$loggerPath.self::$loggerFile, 'a');

            fwrite($file,
                'Date: '.date('Y-m-d-H:i:s')
                .PHP_EOL
                .'Origin: '.Request::header('Host')
                .PHP_EOL
                .'IP: '.Request::header('Host')
                .PHP_EOL
                .'Method: '.Request::method()
                .PHP_EOL
                .'To: '.Request::path()
                .PHP_EOL
                .'Body: '
                .PHP_EOL
                .print_r(Request::body(), true)
            );

            fclose($file);
        }
    }

    public static function error($type)
    {
        if (App::isProdMode()) {
            $file = fopen(self::$loggerPath.'production-error.log', 'a');

            fwrite($file,
                'Date: '.date('Y-m-d-H:i:s')
                .PHP_EOL
                .'Origin: '.Request::header('Host')
                .PHP_EOL
                .'IP: '.Request::header('Host')
                .PHP_EOL
                .'Method: '.Request::method()
                .PHP_EOL
                .'To: '.Request::path()
                .PHP_EOL
                .'Body: '
                .PHP_EOL
                .print_r(Request::body(), true)
                .(new Exception($type))->__toString()
                .PHP_EOL
            );

            fclose($file);
        } else {
            throw new Exception($type);
        } 
    }

    public static function debug($data, $export = true)
    {
        $file = fopen(self::$loggerPath.'custom-log.log', 'a');

        if (!self::$debuged) {
            fwrite($file,
                PHP_EOL
                .' - '.date('Y-m-d-H:i:s').' - '
                .PHP_EOL
            );

            self::$debuged = true;
        }

        fwrite($file,
            ($export ? print_r($data, true) : $data)
            .PHP_EOL
        );

        fclose($file);
    }

    public static function debugRoute($route, $executed)
    {
        if (App::isProdMode()) {
            return;
        }

        $file = fopen(self::$loggerPath.self::$loggerFile, 'a');

        fwrite($file, 
            PHP_EOL
            .PHP_EOL
            .PHP_EOL
            .'ROUTE '.($executed ? '' : 'NOT').' EXECUTED'
            .PHP_EOL.
            print_r($route, true).
            'Parameters: '
            .PHP_EOL
            .print_r(Request::param(), true)
        );

        fclose($file);
    }
}