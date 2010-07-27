<?php

/**
 * Stores information about files
 *
 * NOTE: We take a very simple approach to caching here. If we ever want to 
 * introduce an operation that uses different first-pass listeners than 
 * rename-method and also wants to cache, we will have to start storing more 
 * information.
 */
class Scisr_Db_Files extends Scisr_Db_Dao
{

    /**
     * Set up the DB table we are going to use
     */
    public function init()
    {
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS FileInfo (file text PRIMARY KEY, parsed text);
EOS;
        $this->_db->exec($create);
    }

    /**
     * Register a file as having been parsed
     * @param string $filename the file that was parsed
     */
    public function registerFile($filename)
    {
        $delete = <<<EOS
DELETE FROM FileInfo WHERE file = ?
EOS;
        $delSt = $this->_db->prepare($delete);
        $delSt->execute(array($filename));

        $insert = <<<EOS
INSERT INTO FileInfo (file, parsed) VALUES (?, datetime('now'))
EOS;
        $insSt = $this->_db->prepare($insert);
        $insSt->execute(array($filename));
    }

    /**
     * Get the most recent time this file was parsed
     * @param string $filename the file
     * @return int|null a UNIX timestamp, or null if this file has not been 
     * parsed before
     */
    public function getTimeParsed($filename)
    {
        $select = <<<EOS
SELECT strftime('%s', parsed) FROM FileInfo WHERE file = ?
EOS;
        $st = $this->_db->prepare($select);
        $st->execute(array($filename));
        $result = $st->fetch(PDO::FETCH_NUM);
        if ($result === false) {
            return null;
        } else {
            return (int)$result[0];
        }
    }

}
