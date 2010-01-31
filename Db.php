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
            $db_path = dirname(__FILE__) . '/cache.db';
            $db = new PDO("sqlite:$db_path", null, null, array(PDO::ATTR_PERSISTENT => true));
        }
        return $db;
    }

    public static function clearDB()
    {
        $db = self::getDB();

        // Get all tables in the db
        $select = "SELECT tbl_name FROM sqlite_master WHERE type='table'";
        $st = $db->prepare($select);
        $st->execute();
        $result = $st->fetchAll();

        // And drop them
        foreach ($result as $row) {
            $tbl = $row['tbl_name'];
            $drop = "DROP TABLE $tbl";
            $st = $db->prepare($drop);
            $st->execute();
        }
    }

}
