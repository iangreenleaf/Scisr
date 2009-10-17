<?php
/**
 * A registry to track potential changes to files we have parsed
 *
 * This could extend Zend_Registry or something similar if we desired.
 */
class Scisr_ChangeRegistry
{

    /**
     * Stores the raw data we've been given
     * @var array
     */
    private static $_data;

    /**
     * Set a value
     * @param string $name the name of the value
     * @param mixed $value the value
     */
    public static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    /**
     * Get a value
     * @param string $name the name of the value
     * @return mixed the value stored for this name
     */
    public static function get($name)
    {
        return self::$data[$name];
    }

    /**
     * Set a potential change to a file
     * @param string $filename the filename
     * @param int $line the line number that our change begins on
     * @param int $column the column number that our change begins at
     * @param int $length the length of the original text to be replaced
     * @param string $replacement the text to insert in its place
     */
    public static function setChange($filename, $line, $column, $length, $replacement)
    {
        //TODO
    }

}
