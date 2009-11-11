<?php

/**
 * An abstract operation class that helps you deal with variable types
 *
 * This should sit completely between any Scisr Operations and the
 * Scisr_VariableTypes storage class, since that may change formats,
 * and this has domain knowledge about CodeSniffer.
 */
abstract class Scisr_Operations_AbstractVariableTypeOperation implements PHP_CodeSniffer_Sniff
{

    /**
     * Get the type of a variable
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     * @param int $varPtr  The variable's position in the token stack
     * @return string|null the class name, or null if we don't know
     */
    protected function getVariableType($varPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $varInfo = $tokens[$varPtr];
        if ($varInfo['content'] == '$this'
            && ($classDefPtr = array_search(T_CLASS, $varInfo['conditions'])) !== false
        ) {
            // If our variable is $this, get the containing class
            $classPtr = $phpcsFile->findNext(T_STRING, $classDefPtr);
            $type = $tokens[$classPtr]['content'];
        } else {
            // Otherwise just see if it's stored
            $scopeOpen = $this->getScopeOpener($varPtr, $phpcsFile);
            $type = Scisr_VariableTypes::getVariableType($varInfo['content'], $phpcsFile->getFileName(), $scopeOpen);
        }

        return $type;

    }

    /**
     * Set the type of a variable
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     * @param int $varPtr  The variable's position in the token stack
     * @param string $type the name of the class that this variable holds
     * @param string $varName the name of the variable. If not provided, will
     * be determined from $varPtr.
     * @todo currently, $varPtr is slightly misdocumented - the PHPDoc stuff
     * sometimes passes not the var pointer, just something that's close enough,
     * and we count on this function not getting confused
     */
    protected function setVariableType($varPtr, $type, $phpcsFile, $varName=null)
    {
        if ($varName === null) {
            $tokens = $phpcsFile->getTokens();
            $varInfo = $tokens[$varPtr];
            $varName = $varInfo['content'];
        }
        $scopeOpen = $this->getScopeOpener($varPtr, $phpcsFile);
        Scisr_VariableTypes::registerVariableType($varName, $type, $phpcsFile->getFileName(), $scopeOpen);
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
    protected static function filterScopes($scopes)
    {
        $acceptScope = create_function('$type', 'return (in_array($type, array(T_CLASS, T_INTERFACE, T_FUNCTION)));');
        $scopes = array_keys(array_filter($scopes, $acceptScope));
        return $scopes;
    }

    /**
     * Set a variable as global
     * @param string $variable the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     */
    protected function setGlobal($varPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $varInfo = $tokens[$varPtr];
        $scopeOpen = $this->getScopeOpener($varPtr, $phpcsFile);
        Scisr_VariableTypes::registerGlobalVariable($varInfo['content'], $phpcsFile->getFileName(), $scopeOpen);
    }

    /**
     * Figure out the relevant scope opener
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     * @param int $varPtr  The variable's position in the token stack
     * @return int the stack pointer that opens the scope for this variable
     */
    private function getScopeOpener($varPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $varInfo = $tokens[$varPtr];

        $scopes = self::filterScopes($varInfo['conditions']);
        // We're using 0 for the global scope
        if ($this->isGlobal($varInfo['content'], $phpcsFile->getFileName(), $scopes)) {
            $scopes = array(0);
        }
        // Get the lowermost scope
        $scopeOpen = $scopes[count($scopes) - 1];
        return $scopeOpen;
    }

    /**
     * See if a variable is in the global scope
     * @param string $name the name of the variable (including the dollar sign)
     * @param string $filename the file we're in
     * @param array $scopes an array of scope opener pointers (not as received from CodeSniffer)
     * @return boolean true if the variable is global
     */
    private function isGlobal($name, $filename, $scopes)
    {

        // If we have no scope, we're global without trying
        if (count($scopes) == 0) {
            return true;
        }
        // Get the lowermost scope
        $scopeOpen = $scopes[count($scopes) - 1];

        return Scisr_VariableTypes::isGlobalVariable($name, $filename, $scopeOpen);
    }
}
