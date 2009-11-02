<?php

/**
 * An operation to change the name of a class method
 */
class Scisr_Operations_ChangeMethodName implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;

    public function __construct($class, $oldName, $newName)
    {
        $this->class = $class;
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function register()
    {
        return array(
            T_OBJECT_OPERATOR,
            T_PAAMAYIM_NEKUDOTAYIM,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $classPtr = $phpcsFile->findPrevious(T_STRING, $stackPtr);
        $classInfo = $tokens[$classPtr];
        $methodPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $methodInfo = $tokens[$methodPtr];

        if ($tokens[$stackPtr]['code'] == T_PAAMAYIM_NEKUDOTAYIM) {
            $className = $classInfo['content'];
            $methodName = $methodInfo['content'];
            // If it's the name we're looking for, register it
            if ($className == $this->class && $methodName == $this->oldName) {
                Scisr_ChangeRegistry::addChange(
                    $phpcsFile->getFileName(),
                    $methodInfo['line'],
                    $methodInfo['column'],
                    strlen($methodName),
                    $this->newName
                );
            }
        }
    }
}
