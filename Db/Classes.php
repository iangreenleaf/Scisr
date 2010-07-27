<?php

/**
 * Stores information about classes
 */
class Scisr_Db_Classes extends Scisr_Db_Dao
{
    /**
     * Do any necessary setup
     *
     * Sets up the DB table we are going to use
     */
    public function init()
    {
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS Classes(filename text, class text);
CREATE INDEX IF NOT EXISTS Classes_index_filename ON Classes (class);
EOS;
        $this->_db->exec($create);

        $create = <<<EOS
CREATE TABLE IF NOT EXISTS ClassRelationships(class text, is_a text);
CREATE INDEX IF NOT EXISTS ClassRelationships_index_is_a ON ClassRelationships (is_a);
EOS;
        $this->_db->exec($create);

        // That's right, my DB is denormalized
        $create = <<<EOS
CREATE TABLE IF NOT EXISTS Parents(class text, parent text);
CREATE UNIQUE INDEX IF NOT EXISTS Parents_index_class ON Parents (class);
EOS;
        $this->_db->exec($create);
    }

    /**
     * Register a class in a certain file
     * @param string $className the name of the class
     * @param string $filename the file we're in
     */
    public function registerClass($className, $filename)
    {
        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO Classes (filename, class) VALUES (?, ?)
EOS;
        $insSt = $this->_db->prepare($insert);
        $insSt->execute(array($filename, $className));
    }

    /**
     * Register one class as extending another
     * @param string $className the name of the class
     * @param string $extendsClass the name of the class being extended
     */
    public function registerClassExtends($className, $extendsClass)
    {
        // First register the generic relationship
        $this->registerClassRelationship($className, $extendsClass);

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO Parents (class, parent) VALUES (?, ?)
EOS;
        $insSt = $this->_db->prepare($insert);
        $insSt->execute(array($className, $extendsClass));
    }

    /**
     * Register a class as implementing one or more interfaces
     * @param string $className the name of the class
     * @param array(string) $implements an array of names of
     * interfaces being implemented
     */
    public function registerClassImplements($className, $implements)
    {
        foreach ($implements as $interface) {
            $this->registerClassRelationship($className, $interface);
        }
    }

    /**
     * Register a class relationship
     * @param string $className the name of the class
     * @param string $isA the name of the class or interface it extends or implements
     */
    private function registerClassRelationship($className, $isA)
    {
        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO ClassRelationships (class, is_a) VALUES (?, ?)
EOS;
        $insSt = $this->_db->prepare($insert);
        $insSt->execute(array($className, $isA));
    }

    /**
     * Find the file that contains a given clas
     * @param string $className the name of the class
     * @return string|null the filename, or null if we can't find this class
     * @todo what if there is more than one entry?
     */
    public function getClassFile($className)
    {
        $select = <<<EOS
SELECT filename FROM Classes WHERE class = ? LIMIT 1
EOS;
        $st = $this->_db->prepare($select);
        $st->execute(array($className));
        $result = $st->fetch();

        return $result['filename'];
    }

    /**
     * Get all classes that extend or implement this class or interface
     * @param string the class or interface name
     * @return array(string) the names of all child classes
     */
    public function getChildClasses($className)
    {
        $select = <<<EOS
SELECT class FROM ClassRelationships WHERE is_a = ?
EOS;
        $st = $this->_db->prepare($select);
        $st->execute(array($className));
        $result = $st->fetchAll();

        if ($result === false) {
            return array();
        }

        $children = array();
        foreach ($result as $row) {
            $child = $row['class'];
            if (!in_array($child, $children)) {
                $children[] = $child;
                $children = array_merge($children, $this->getChildClasses($child));
            }
        }
        return $children;
    }

    /**
     * Get the parent of this class
     * @param string the class name
     * @return string|null the name of this class' parent, or null if none exists
     */
    public function getParent($className)
    {
        $select = <<<EOS
SELECT parent FROM Parents WHERE class = ?
EOS;
        $st = $this->_db->prepare($select);
        $st->execute(array($className));
        $result = $st->fetchColumn();

        if ($result === false) {
            return null;
        }

        return $result;
    }

}
