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
     * @param array $scopes an array of all the currently active scopes as given by CodeSniffer
     */
    public static function registerVariableType($variable, $type, $filename, $scopes) {
        $db = self::getDB();

        $scopeOpen = self::getScopeOpener($variable, $filename, $scopes);

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
     * @param array $scopes an array of all the currently active scopes as given by CodeSniffer
     */
    public static function registerGlobalVariable($variable, $filename, $scopes) {
        $db = self::getDB();

        $scopeOpen = self::getScopeOpener($variable, $filename, $scopes);

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
     * @param array $scopes an array of all the currently active scopes as given by CodeSniffer
     * @return string|null the class name, or null if we don't know
     */
    public static function getVariableType($variable, $filename, $scopes) {
        $db = self::getDB();

        $scopeOpen = self::getScopeOpener($variable, $filename, $scopes);

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
     * @param array $scopes an array of scope opener pointers
     * @return boolean true if it is global
     */
    public static function isGlobalVariable($variable, $filename, $scopes) {

        // If we have no scope, we're global without trying
        if (count($scopes) == 0) {
            return true;
        }
        // Get the lowermost scope
        $scopeOpen = $scopes[count($scopes) - 1];

        $db = self::getDB();

        $select = <<<EOS
SELECT COUNT(*) FROM GlobalVariables WHERE filename = ? AND variable = ? AND scopeopen = ? LIMIT 1
EOS;
        $st = $db->prepare($select);
        $st->execute(array($filename, $variable, $scopeOpen));
        $result = $st->fetch();
        return $result['COUNT(*)'] > 0;
    }

    /**
     * Filter the array of scopes we get from CodeSniffer
     *
     * We don't want things like conditionals in our scope list, since for our
     * purposes we're just ignoring those.
     *
     * @param array a list of stack pointers => token types as Codesniffer generates them
     * @return an array of stack pointers we care about
     */
    protected static function filterScopes($scopes) {
        $acceptScope = create_function('$type', 'return (in_array($type, array(T_CLASS, T_INTERFACE, T_FUNCTION)));');
        $scopes = array_keys(array_filter($scopes, $acceptScope));
        return $scopes;
    }

    /**
     * Figure out the relevant scope opener
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopes an array of all the currently active scopes as given by CodeSniffer
     * @return int
     */
    protected static function getScopeOpener($variable, $filename, $scopes) {
        $scopes = self::filterScopes($scopes);
        // We're using 0 for the global scope
        if (self::isGlobalVariable($variable, $filename, $scopes)) {
            $scopes = array(0);
        }
        // Get the lowermost scope
        $scopeOpen = $scopes[count($scopes) - 1];
        return $scopeOpen;
    }

}
