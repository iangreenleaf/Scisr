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
            T_FUNCTION,
            T_PAAMAYIM_NEKUDOTAYIM,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $methodPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $methodInfo = $tokens[$methodPtr];

        if ($tokens[$stackPtr]['code'] == T_PAAMAYIM_NEKUDOTAYIM) {
            $classPtr = $phpcsFile->findPrevious(T_STRING, $stackPtr);
            $classInfo = $tokens[$classPtr];
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
        } else if ($tokens[$stackPtr]['code'] == T_FUNCTION) {
            $methodName = $methodInfo['content'];
            // If we found a correctly named method inside the specified class, continue
            if ($methodName == $this->oldName
                && ($classDefPtr = array_search(T_CLASS, $methodInfo['conditions'])) !== false) {

                $classPtr = $phpcsFile->findNext(T_STRING, $classDefPtr);
                $classInfo = $tokens[$classPtr];
                if ($classInfo['content'] == $this->class) {
                    Scisr_ChangeRegistry::addChange(
                        $phpcsFile->getFileName(),
                        $methodInfo['line'],
                        $methodInfo['column'],
                        strlen($methodName),
                        $this->newName
                    );
                }
            }
        } else if ($tokens[$stackPtr]['code'] == T_OBJECT_OPERATOR) {
        }
    }
}
