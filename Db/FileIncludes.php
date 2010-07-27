<?php

/**
 * Stores information about file includes
 */
class Scisr_Db_FileIncludes extends Scisr_Db_Dao
{

    /**
     * Set up the DB table we are going to use
     */
    public function init()
    {
        // Yes, I know this is not the most efficient or normalized. But I'm lazy.
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS FileIncludes(file text, included_file text);
CREATE UNIQUE INDEX IF NOT EXISTS FileIncludes_index_file ON FileIncludes (file, included_file);
EOS;
        $this->_db->exec($create);
    }

    /**
     * Register a included file
     * @param string $filename the file we're in
     * @param string $includedFilename the file being included
     */
    public function registerFileInclude($filename, $includedFilename)
    {
        $insert = <<<EOS
INSERT OR IGNORE INTO FileIncludes (file, included_file) VALUES (?, ?)
EOS;
        $insSt = $this->_db->prepare($insert);
        $insSt->execute(array($filename, $includedFilename));
    }

    /**
     * Get a list of files this file includes
     * @param string $filename the file we're in
     * @return array(string) an array of filenames that this file includes
     */
    public function getIncludedFiles($filename)
    {
        $select = <<<EOS
SELECT included_file FROM FileIncludes WHERE file = ?
EOS;
        $st = $this->_db->prepare($select);
        $st->execute(array($filename));
        $result = $st->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    }

}
