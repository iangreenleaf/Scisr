<?php

/**
 * Handles interactions with a Scisr DB
 */
class Scisr_Db
{
    /**
     * Get the DB connection we're using
     */
    public static function getDB()
    {
        static $db = null;
        if ($db === null) {
            $db = new PDO('sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => true));
        }
        return $db;
    }

}
