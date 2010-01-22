<?php

/**
 * Stores information about variable types
 *
 * Basically a very rudimentary static model
 * @todo share some functionality with Scisr_FileIncludes
 */
class Scisr_VariableTypes
{

    /**
     * Get the DB connection we're using
     */
    public static function getDB()
    {
        return new PDO('sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => true));
    }

    /**
     * Do any necessary setup
     *
     * Sets up the DB table we are going to use
     */
    public static function init()
    {
        $db = self::getDB();
        $create = <<<EOS
CREATE TABLE VariableTypes(filename text, scopeopen integer, variable text, type text, variable_pointer integer);
EOS;
        $db->exec($create);
        $create = <<<EOS
CREATE TABLE GlobalVariables(filename text, scopeopen integer, variable text);
EOS;
        $db->exec($create);
    }

    /**
     * Register a variable as holding a certain class type
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $type the name of the class that this variable holds
     * @param string $filename the file we're in
     * @param array $scopeOpen the stack pointer to the beginning of the current scope
     * @param int $varPtr a pointer to the beginning of the variable
     */
    public static function registerVariableType($variable, $type, $filename, $scopeOpen, $varPtr)
    {
        $db = self::getDB();

        // First delete any previous assignments in this scope
        $delete = <<<EOS
DELETE FROM VariableTypes WHERE filename = ? AND scopeopen = ? AND variable = ?
EOS;
        $delSt = $db->prepare($delete);
        $delSt->execute(array($filename, $scopeOpen, $variable));

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO VariableTypes (filename, scopeopen, variable, type, variable_pointer) VALUES (?, ?, ?, ?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $scopeOpen, $variable, $type, $varPtr));
    }

    /**
     * Register a variable as global
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopeOpen the stack pointer to the beginning of the current scope
     */
    public static function registerGlobalVariable($variable, $filename, $scopeOpen)
    {
        $db = self::getDB();

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO GlobalVariables (filename, scopeopen, variable) VALUES (?, ?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $scopeOpen, $variable));
    }

    /**
     * See if a type has already been registered for this particular place in 
     * the stack.
     * @param string $filename the file we're in
     * @param int $varPtr a pointer to the beginning of the variable
     * @return string|null the type name registered at this location, or null if none
     */
    public static function checkVariableDefinition($filename, $varPtr)
    {
        $db = self::getDB();

        $sql = <<<EOS
SELECT type FROM VariableTypes WHERE filename = ? AND variable_pointer = ?
EOS;
        $st = $db->prepare($sql);
        $st->execute(array($filename, $varPtr));
        $result = $st->fetch();
        if ($result === false) {
            return null;
        } else {
            return $result['type'];
        }
    }

    /**
     * Get the type of a variable
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopeOpen the stack pointer to the beginning of the current scope
     * @return string|null the class name, or null if we don't know
     */
    public static function getVariableType($variable, $filename, $scopeOpen)
    {
        $db = self::getDB();

        $select = <<<EOS
SELECT type FROM VariableTypes WHERE filename = ? AND variable = ? AND scopeopen = ? ORDER BY scopeopen DESC LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename, $variable, $scopeOpen));
        $result = $st->fetch();
        return $result['type'];
    }

    /**
     * See if a variable is in the global scope
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopeOpen the stack pointer to the beginning of the current scope
     * @return boolean true if it is global
     */
    public static function isGlobalVariable($variable, $filename, $scopeOpen)
    {
        $db = self::getDB();

        $select = <<<EOS
SELECT COUNT(*) FROM GlobalVariables WHERE filename = ? AND variable = ? AND scopeopen = ? LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename, $variable, $scopeOpen));
        $result = $st->fetch();
        return $result['COUNT(*)'] > 0;
    }

}
