<?php

namespace Costamilam\Alpha;

class DB
{
    private static $access;
    private static $charset;

    private static $connection;

    private static $statement;

    public static $insertedId;

    public static function access($host, $username, $password, $database)
    {
        self::$access = array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'database' => $database
        );
    }

    public static function charset($charset)
    {
        self::$charset = $charset;
    }

    private static function format($query)
    {
        $formattedQuery = preg_replace('/[\t\n\r]/', '', $query);

        $formattedQuery = explode(' ', $formattedQuery);
                
        $formattedQuery = array_filter($formattedQuery);
              
        $formattedQuery = implode($formattedQuery, ' ');

        $formattedQuery = trim($formattedQuery, ';');

        $formattedQuery = explode(';', $formattedQuery);

        return $formattedQuery[0];
    }

    private static function connect()
    {
        if (!self::$connection) {
            self::$connection = new \mysqli(self::$access['host'], self::$access['username'], self::$access['password'], self::$access['database']);

            if (self::$charset) {
                self::$connection->set_charset(self::$charset);
            }
        }

        return self::$connection;
    }

    public static function disconnect()
    {
        if (self::$connection) {
            return self::$connection->close();
        }
    }

    private static function execute($return, $query, ...$param)
    {
        $type = array();
        $blob = array();

        foreach ($param as &$parameter) {
            switch(strtolower(gettype($parameter))) {
                case 'string':
                    $type[] = 's';
                    break;

                case 'integer':
                    $type[] = 'i';
                    break;

                case 'float':
                    $type[] = 'd';
                    break;

                case 'boolean':
                    $type[] = 'i';
                    break;

                case 'resource':
                    $blob[count($type)] = $parameter;
                    $parameter = null;
                    $type[] = 'b';
                    break;

                default:
                    $type[] = null;
                    break;
            }
        }

        $statement = self::connect()->prepare($query);

        if (!empty($type)) {
            $statement->bind_param(implode('', $type), ...$param);
        }

        foreach ($blob as $index => $data) {
            self::$connection->send_long_data($index, $data);
        }

        $execute = $statement->execute();

        return $return ? $statement : $execute;
    }

    public static function insert($query, ...$param)
    {
        $query = self::format($query);

        if (stripos($query, 'insert') !== 0) {
            return false;
        }

        $execute = self::execute(false, $query, ...$param);

        self::$insertedId = self::$connection->insert_id;

        return $execute;
    }

    public static function update($query, ...$param)
    {
        $query = self::format($query);

        if (stripos($query, 'update') !== 0) {
            return false;
        }

        return self::execute(false, $query, ...$param);
    }

    public static function delete($query, ...$param)
    {
        $query = self::format($query);

        if (stripos($query, 'delete') !== 0) {
            return false;
        }

        return self::execute(false, $query, ...$param);
    }

    public static function select($query, ...$param)
    {
        $query = self::format($query);

        if (stripos($query, 'select') !== 0) {
            return false;
        }

        $statement = self::execute(true, $query, ...$param);

        return self::fetch($statement);
    }

    private static function fetch($statement)
    {
        $metadata = $statement->result_metadata();

        $columnName = array();

        foreach ($metadata->fetch_fields() as $field) {
            $columnName[] = json_decode(json_encode($field), true)['name'];
        }

        $metadata->free_result();

        $columnValue = $columnName;

        if (!$statement->bind_result(...$columnValue)) {
            return null;
        }

        $data = array();

        while($statement->fetch()) {
            $row = array();

            for ($i = 0; $i < count($columnName); $i++) { 
                $row[$columnName[$i]] = $columnValue[$i];
            }

            $data[] = $row;
        }

        return $data;
    }
}
