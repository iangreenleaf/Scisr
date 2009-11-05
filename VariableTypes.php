<?php

/**
 * Stores information about variable types
 *
 * Basically a very rudimentary static model
 */
class Scisr_VariableTypes {

    /**
     * Get the DB connection we're using
     * @todo seems like this should go somewhere else
     */
    public static function getDB() {
        return new PDO('sqlite::memory:', null, null, array(PDO::ATTR_PERSISTENT => true));
    }

    /**
     * Do any necessary setup
     *
     * Sets up the DB table we are going to use
     */
    public static function init() {
        $db = self::getDB();
        $create = <<<EOS
CREATE TABLE VariableTypes(filename text, scopeopen integer, variable text, type text);
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
     * @param int $scopeOpen the stack pointer for the start of our current scope
     */
    public static function registerVariableType($variable, $type, $filename, $scopes) {
        $db = self::getDB();

        if (self::isGlobalVariable($variable, $filename, $scopes)) {
            // If it's global, put it in the global scope
            $scopeOpen = 0;
        } else {
            // Otherwise get the lowermost scope
            $scopeOpen = $scopes[count($scopes) - 1];
        }

        // First delete any previous assignments in this scope
        $delete = <<<EOS
DELETE FROM VariableTypes WHERE filename = ? AND scopeopen = ? AND variable = ?
EOS;
        $delSt = $db->prepare($delete);
        $delSt->execute(array($filename, $scopeOpen, $variable));

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO VariableTypes (filename, scopeopen, variable, type) VALUES (?, ?, ?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $scopeOpen, $variable, $type));
    }

    /**
     * Register a variable as global
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopes an array of all the currently active scopes
     */
    public static function registerGlobalVariable($variable, $filename, $scopes) {
        $db = self::getDB();

        $scopeOpen = $scopes[count($scopes) - 1];

        // Now insert this assignment
        $insert = <<<EOS
INSERT INTO GlobalVariables (filename, scopeopen, variable) VALUES (?, ?, ?)
EOS;
        $insSt = $db->prepare($insert);
        $insSt->execute(array($filename, $scopeOpen, $variable));
    }

    /**
     * Get the type of a variable
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopes an array of all the currently active scopes
     * @return string|null the class name, or null if we don't know
     */
    public static function getVariableType($variable, $filename, $scopes) {
        $db = self::getDB();

        // If it's global, look in the global scope
        if (self::isGlobalVariable($variable, $filename, $scopes)) {
            $scopes = array(0);
        }

        // PDO can't do IN clauses. Sigh.
        // It's really okay because this isn't really untrusted input. Nothing here is.
        $scopeInStmt = '(' . implode(',', $scopes) . ')';
        // We look through the scopes in order for our variable name (just like a real interpreter)
        $select = <<<EOS
SELECT type FROM VariableTypes WHERE filename = ? AND variable = ? AND scopeopen IN $scopeInStmt ORDER BY scopeopen DESC LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename, $variable));
        $result = $st->fetch();
        return $result['type'];
    }

    /**
     * See if a variable is in the global scope
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopes an array of all the currently active scopes
     * @return boolean true if it is global
     */
    public static function isGlobalVariable($variable, $filename, $scopes) {

        // If we have no scope, we're global without trying
        if (count($scopes) == 0) {
            return true;
        }

        $db = self::getDB();

        $scopeInStmt = '(' . implode(',', $scopes) . ')';
        // We look through the scopes in order for our variable name (just like a real interpreter)
        $select = <<<EOS
SELECT COUNT(*) FROM GlobalVariables WHERE filename = ? AND variable = ? AND scopeopen IN $scopeInStmt LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename, $variable));
        $result = $st->fetch();
        return $result['COUNT(*)'] > 0;
    }
}
