<?php

/**
 * Stores information about file includes
 */
class Scisr_FileIncludes
{

    /**
     * Set up the DB table we are going to use
     */
    public static function init()
    {
        $db = Scisr_Db::getDB();
        // Yes, I know this is not the most efficient or normalized. But I'm lazy.
        $create = <<<EOS
CREATE TABLE FileIncludes(file text, included_file text);
EOS;
        $db->exec($create);
    }

    /**
     * Register a included file
     * @param string $filename the file we're in
     * @param string $includedFilename the file being included
     */
    public static function registerFileInclude($filename, $includedFilename)
    {
        $db = Scisr_Db::getDB();

        $insert = <<<EOS
INSERT INTO FileIncludes (file, included_file) VALUES (?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $includedFilename));
    }

    /**
     * Get a list of files this file includes
     * @param string $filename the file we're in
     * @return array(string) an array of filenames that this file includes
     */
    public static function getIncludedFiles($filename)
    {
        $db = Scisr_Db::getDB();

        $select = <<<EOS
SELECT included_file FROM FileIncludes WHERE file = ?
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename));
        $result = $st->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }

}
