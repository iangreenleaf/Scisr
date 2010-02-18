<?php

/**
 * Stores information about classes
 */
class Scisr_Db_Classes
{
    /**
     * Do any necessary setup
     *
     * Sets up the DB table we are going to use
     */
    public static function init()
    {
        $db = Scisr_Db::getDb();
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS Classes(filename text, class text);
EOS;
        $db->exec($create);
    }

    /**
     * Register a class in a certain file
     * @param string $className the name of the class
     * @param string $filename the file we're in
     */
    public static function registerClass($className, $filename)
    {
        $db = Scisr_Db::getDb();

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO Classes (filename, class) VALUES (?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $className));
    }

    /**
     * Find the file that contains a given clas
     * @param string $className the name of the class
     * @return string|null the filename, or null if we can't find this class
     * @todo what if there is more than one entry?
     */
    public static function getClassFile($className)
    {
        $db = Scisr_Db::getDb();

        $select = <<<EOS
SELECT filename FROM Classes WHERE class = ? LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($className));
        $result = $st->fetch();

        return $result['filename'];
    }

}
