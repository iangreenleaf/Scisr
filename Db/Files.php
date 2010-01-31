<?php

/**
 * Stores information about files
 *
 * NOTE: We take a very simple approach to caching here. If we ever want to 
 * introduce an operation that uses different first-pass listeners than 
 * rename-method and also wants to cache, we will have to start storing more 
 * information.
 */
class Scisr_Db_Files
{

    /**
     * Set up the DB table we are going to use
     */
    public static function init()
    {
        $db = Scisr_Db::getDb();
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS FileInfo (file text, parsed text);
EOS;
        $db->exec($create);
    }

    /**
     * Register a file as having been parsed
     * @param string $filename the file that was parsed
     */
    public static function registerFile($filename)
    {
        $db = Scisr_Db::getDb();

        $delete = <<<EOS
DELETE FROM FileInfo WHERE file = ?
EOS;
        $delSt = $db->prepare($delete);
        $delSt->execute(array($filename));

        $insert = <<<EOS
INSERT INTO FileInfo (file, parsed) VALUES (?, datetime('now'))
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename));
    }

    /**
     * Get the most recent time this file was parsed
     * @param string $filename the file
     * @return int|null a UNIX timestamp, or null if this file has not been 
     * parsed before
     */
    public static function getTimeParsed($filename)
    {
        $db = Scisr_Db::getDb();

        $select = <<<EOS
SELECT strftime('%s', parsed) FROM FileInfo WHERE file = ?
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename));
        $result = $st->fetch(PDO::FETCH_NUM);
        if ($result === false) {
            return null;
        } else {
            return (int)$result[0];
        }
    }

}
