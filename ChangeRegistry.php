<?php
class Scisr_ChangeRegistry
{

    private static $_data;

    public static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    public static function get($name)
    {
        return self::$data[$name];
    }

    public static function setChange($filename, $line, $column, $length, $replacement)
    {
        //TODO
    }

}
