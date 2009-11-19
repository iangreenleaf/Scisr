<?php

// The scope in which we store qualified class types
define('SCISR_SCOPE_CLASS', 0);
// The scope in which we store global variable types
define('SCISR_SCOPE_GLOBAL', 0);

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
     * @param int $varPtr The position in the stack in which our variable has scope
     * @param string $varName the name of the variable. If not provided, will
     * be determined from $varPtr.
     * @return string|null the class name, or null if we don't know
     */
    protected function getVariableType($varPtr, $phpcsFile, $varName=null)
    {
        $tokens = $phpcsFile->getTokens();
        $varInfo = $tokens[$varPtr];

        if ($varName === null) {
            $varName = $varInfo['content'];
        }

        // Special case: $this inside a class
        if ($varName == '$this'
            && ($classDefPtr = array_search(T_CLASS, $varInfo['conditions'])) !== false
        ) {
            $classPtr = $phpcsFile->findNext(T_STRING, $classDefPtr);
            $type = $tokens[$classPtr]['content'];
            return $type;
        }

        $scopeOpen = $this->getScopeOwner($varPtr, $phpcsFile, $varName);

        return Scisr_VariableTypes::getVariableType($varName, $phpcsFile->getFileName(), $scopeOpen);

    }

    /**
     * Set the type of a variable
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     * @param int $varPtr The position in the stack in which our variable has scope
     * @param string $type the name of the class that this variable holds
     * @param string $varName the name of the variable. If not provided, will
     * be determined from $varPtr.
     */
    protected function setVariableType($varPtr, $type, $phpcsFile, $varName=null)
    {
        if ($varName === null) {
            $tokens = $phpcsFile->getTokens();
            $varInfo = $tokens[$varPtr];
            $varName = $varInfo['content'];
        }

        $scopeOpen = $this->getScopeOwner($varPtr, $phpcsFile, $varName);
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
     * @param int $varPtr The position in the stack in which our variable has scope
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     */
    protected function setGlobal($varPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $varName = $tokens[$varPtr]['content'];
        $scopeOpen = $this->getScopeOwner($varPtr, $phpcsFile, $varName);
        Scisr_VariableTypes::registerGlobalVariable($varName, $phpcsFile->getFileName(), $scopeOpen);
    }

    /**
     * Figure out the relevant scope opener
     * @param PHP_CodeSniffer_File $phpcsFile The file the variable is in
     * @param int $varPtr The position in the stack in which our variable has scope
     * @param string $varName the name of the variable.
     * @return int the stack pointer that opens the scope for this variable
     */
    private function getScopeOwner($varPtr, $phpcsFile, $varName)
    {
        $tokens = $phpcsFile->getTokens();
        $varInfo = $tokens[$varPtr];

        $scopes = self::filterScopes($varInfo['conditions']);

        if ($varName{0} != '$') {
            // If we're dealing with a fully qualified variable, put it in the global scope
            $scopeOpen = SCISR_SCOPE_CLASS;
        } else if ($this->isGlobal($varName, $phpcsFile->getFileName(), $scopes)) {
            // If the variable was declared global, use that
            $scopeOpen = SCISR_SCOPE_GLOBAL;
        } else {
            // Get the lowermost scope
            $scopeOpen = $scopes[count($scopes) - 1];
        }
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

    /**
     * Resolve a set of variable tokens to the most typed object we can
     * @param int $startPtr a pointer to the first token
     * @param int $endPtr a pointer to the last token
     * @param PHP_CodeSniffer_File $phpcsFile
     * @return string a type name or a partially-resolved string, such as
     * "Foo->unknownVar->property".
     */
    protected function resolveFullVariableType($startPtr, $endPtr, $phpcsFile)
    {
        $tokens = $phpcsFile->getTokens();
        $soFar = '';
        $currPtr = $startPtr;
        // Parse through the token set
        while ($currPtr <= $endPtr) {
            $currToken = $tokens[$currPtr];
            // Ignore whitespace
            if ($currToken['code'] == T_WHITESPACE) {
                $currPtr++;
                continue;
            }
            // Add the token to our string
            $soFar .= $currToken['content'];
            // See if the string resolves to a type now
            $type = $this->getVariableType($startPtr, $phpcsFile, $soFar);
            if ($type !== null) {
                $soFar = $type;
            }
            $currPtr++;
        }
        return $soFar;
    }

    /**
     * Get the start position of a variable declaration
     * @param int $varPtr a pointer to the end of the variable tokens
     * @param array $tokens the token stack
     * @return int a pointer to the first token that makes up this variable
     * @todo whitespace is an imperfect marker
     */
    protected function getStartOfVar($varPtr, $tokens)
    {
        while ($tokens[$varPtr]['code'] != T_WHITESPACE) {
            $varPtr--;
        }
        return $varPtr;
    }

}
