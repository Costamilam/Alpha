<?php

namespace Costamilam\Alpha\Debugger;

use Costamilam\Alpha\Response;

class Exception extends \Exception
{
    private $error = null;

    private $trace = null;

    public function __construct($type)
    {
        $this->loadErrorData($type);

        parent::__construct($this->error['messsage']);

        $this->trace = $this->getTrace();
        array_shift($this->trace);
    }

    private function loadErrorData($type)
    {
        $data = json_decode(file_get_contents(__DIR__.'/exception.json'), true);

        if (isset($data[$type])) {
            $this->error = $data[$type];
        } else {
            $this->error = $data['unkownException'];

            foreach (array_keys($data) as $key) {
                $this->error['option'][] = '\''.$key.'\'';
            }
        }
    }

    private static function getArgument($argument)
    {
        foreach ($argument as &$arg) {
            switch (strtolower(gettype($arg))) {
                case 'boolean':
                    $arg = $arg ? 'true' : 'false';
                    break;

                case 'null':
                    $arg = 'null';
                    break;

                case 'string':
                    $arg = '\''.$arg.'\'';
                    break;

                case 'array':
                    $arg = 'Array';
                    break;

                case 'object':
                case 'resource':
                    $arg = 'Object';
                    break;

                default:
                    break;
            }
        }

        return implode(', ', $argument);
    }

    public function __toString()
    {
        $string = 
            'Error:'.
            PHP_EOL.$this->getMessage().'.'.PHP_EOL.
            PHP_EOL.'Excecution finished with exit code '.$this->getCode().'.'.PHP_EOL//.
            //PHP_EOL.'File '.$this->getFile().' on line '.$this->getLine().'.'.PHP_EOL
        ;

        if ($this->error['option'] !== null) {
            $string .= PHP_EOL.'The valid values are '.implode(', ', $this->error['option']).'.'.PHP_EOL;
        }

        if ($this->error['observation'] !== null) {
            $string .= PHP_EOL.$this->error['observation'].'.';
        }

        $string .= PHP_EOL.'Backtrace'.PHP_EOL;

        foreach ($this->trace as $index => $data) {
            $string .= '#'.$index.' '.$data['file'].'('.$data['line'].'): '.(isset($data['class'])?$data['class']:'').(isset($data['type'])?$data['type']:'').$data['function'].'('.self::getArgument($data['args']).')';
        }

        //$string .= PHP_EOL.'Backtrace'.PHP_EOL.$this->getTraceAsString();

        if ($this->getPrevious()) {
            $string .= PHP_EOL.'Exception stack'.PHP_EOL.$this->getPrevious().'.';
        }

        return $string;
    }

    public function toHTML()
    {
        $string = 
            '<pre>'.
                '<strong>Error</strong>:'.
                '<br>'.$this->getMessage().'.<br>'.
                '<br>Excecution finished with exit code '.$this->getCode().'.<br>'//.
                //'<br>File '.$this->getFile().' on line '.$this->getLine().'.<br>'
        ;

        if ($this->error['option'] !== null) {
            $string .= '<br>The valid values are '.implode(', ', $this->error['option']).'.<br>';
        }

        if ($this->error['observation'] !== null) {
            $string .= '<br>'.$this->error['observation'].'.';
        }

        $string .= '<hr>Backtrace<br>';

        foreach ($this->trace as $index => $data) {
            $string .= '#'.$index.$data['file'].'('.$data['line'].'): '.(isset($data['class'])?$data['class']:'').(isset($data['type'])?$data['type']:'').$data['function'].'('.self::getArgument($data['args']).')';
        }

        if ($this->getPrevious()) {
            $string .= '<hr>Exception stack<br>'.$this->getPrevious().'.';
        }

        $string .= '</pre>';

        return $string;
    }

    public function toArray()
    {
        return array(
            'message' => $this->getMessage(),
            'exitCode' => $this->getCode(),
            //'file' => $this->getFile(),
            //'line' => $this->getLine(),
            'backtrace' => $this->trace,
            'previousException' => $this->getPrevious(),
            'option' => $this->error['option'],
            'observation' => $this->error['observation']
        );
    }

    public function toJSON()
    {
        return json_encode(
            $this->toArray()
        );
    }
}