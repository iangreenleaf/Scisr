<?php

/**
 * An operation to look for a class, and trigger a method rename
 * if it extends or implements the given class
 */
class Scisr_Operations_RenameChildMethods implements PHP_CodeSniffer_Sniff
{

    public $className;
    public $oldName;
    public $newName;
    protected $_scisr;

    /**
     * @param string $className the class containing the method
     * @param string $oldName the method to be renamed
     * @param string $newName the new method name to be given
     * @param Scisr $scisr the calling instance
     */
    public function __construct($className, $oldName, $newName, $scisr)
    {
        $this->className = $className;
        $this->oldName = $oldName;
        $this->newName = $newName;
        $this->_scisr = $scisr;
    }

    public function register()
    {
        return array(
            T_CLASS,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $thisClassNamePtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $classNames = $phpcsFile->findImplementsClassName($stackPtr);
        $classNames[] = $phpcsFile->findExtendedClassName($stackPtr);
        if (in_array($this->className, $classNames)) {
            $this->_scisr->setRenameMethod($tokens[$thisClassNamePtr]['content'], $this->oldName, $this->newName, true);
        }
    }
}
