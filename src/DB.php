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

        return __CLASS__;
    }

    public static function charset($charset)
    {
        self::$charset = $charset;

        return __CLASS__;
    }

    private static function connect()
    {
        if (!self::$connection) {
            self::$connection = new \mysqli(...array_values(self::$access));

            if (self::$charset) {
                self::$connection->set_charset(self::$charset);
            }
        }

        return self::$connection;
    }

    public static function disconnect()
    {
        if (self::$connection) {
            self::$connection->close();
        }
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

    private static function multiQuery($type, $query, $param) {
        $results = array();

        foreach ($query as $sql) {
            $results[] = self::{$type}($sql, ...$param);
        }

        return $results;
    }

    private static function execute($query, $param)
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

                case 'object':
                case 'array':
                    $type[] = 's';
                    $parameter = json_encode($parameter);
                    break;

                case 'null':
                    $type[] = 's';
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

        return $statement;
    }

    public static function insert($query, ...$param)
    {
        if (is_array($query)) {
            return self::multiQuery('insert', $query, $param);
        }

        $query = self::format($query);

        if (stripos($query, 'insert') !== 0) {
            return false;
        }

        $execute = self::execute($query, $param)->execute();

        self::$insertedId = self::$connection->insert_id;

        return $execute;
    }

    public static function update($query, ...$param)
    {
        if (is_array($query)) {
            return self::multiQuery('update', $query, $param);
        }

        $query = self::format($query);

        if (stripos($query, 'update') !== 0) {
            return false;
        }

        return self::execute($query, $param)->execute();
    }

    public static function delete($query, ...$param)
    {
        if (is_array($query)) {
            return self::multiQuery('delete', $query, $param);
        }

        $query = self::format($query);

        if (stripos($query, 'delete') !== 0) {
            return false;
        }

        return self::execute($query, $param)->execute();
    }

    public static function select($query, ...$param)
    {
        if (is_array($query)) {
            return self::multiQuery('select', $query, $param);
        }

        $query = self::format($query);

        if (stripos($query, 'select') !== 0) {
            return false;
        }

        $statement = self::execute($query, $param);

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
