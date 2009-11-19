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
        self::$_data[$name] = $value;
    }

    /**
     * Get a value
     * @param string $name the name of the value
     * @return mixed the value stored for this name
     */
    public static function get($name)
    {
        return (isset(self::$_data[$name]) ? self::$_data[$name] : null);
    }

    /**
     * Set a potential change to a file
     * @param string $filename the filename
     * @param int $line the line number that our change begins on
     * @param int $column the column number that our change begins at
     * @param int $length the length of the original text to be replaced
     * @param string $replacement the text to insert in its place
     * @param boolean $tentative true if this change is something we "aren't
     * sure about" - for example, a word match found in a string. Changes marked 
     * tentative will only be acted upon if we are in "aggressive" mode.
     */
    public static function addChange($filename, $line, $column, $length, $replacement, $tentative=false)
    {
        if ($tentative && self::get('aggressiveMode') !== true) {
            return self::addNotification($filename, $line);
        }

        // Get stored changes
        $changes = self::get('storedChanges');
        if (!is_array($changes)) {
            $changes = array();
        }
        // We just store our pending changes as file objects themselves. If one
        // doesn't exist yet for this file, create it
        if (!isset($changes[$filename])) {
            $changes[$filename] = new Scisr_File($filename);
        }
        // Add this edit, and save our changes
        $changes[$filename]->addEdit($line, $column, $length, $replacement);
        self::set('storedChanges', $changes);
    }

    /**
     * Add a notification about a possible change
     *
     * This method and the 'aggressiveMode' setting do leak a bit of business
     * logic out of the Scisr class into this class. However, I think the code 
     * is cleanest this way, at least for now.
     *
     * @param string $filename the filename
     * @param int $line the line number that our change begins on
     */
    protected static function addNotification($filename, $line)
    {
        $changes = self::get('storedNotifications');
        if (!is_array($changes)) {
            $changes = array();
        }
        $changes[$filename][] = $line;
        self::set('storedNotifications', $changes);
    }

}
