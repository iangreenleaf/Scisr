<?php

/**
 * An operation to change the name of a class
 */
class Scisr_Operations_ChangeFile implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;

    public function __construct($oldName, $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function register()
    {
        return array(
            T_INCLUDE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_REQUIRE_ONCE,
            );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        // Find the actual string designating the file
        $fileStrPtr = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, $stackPtr);
        $tokenInfo = $tokens[$fileStrPtr];
        $fileStr = $tokenInfo['content'];
        // Strip the quotes
        $quote = $fileStr{0};
        $fileStr = substr($fileStr, 1, -1);
        // If it's the filename we're looking for, register it
        if ($fileStr == $this->oldName) {
            Scisr_ChangeRegistry::addChange(
                $phpcsFile->getFileName(),
                $tokenInfo['line'],
                $tokenInfo['column'],
                strlen($tokenInfo['content']),
                $quote . $this->newName . $quote
            );
        }
    }

}
