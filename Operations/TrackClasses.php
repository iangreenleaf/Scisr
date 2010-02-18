<?php

/**
 * Tracks classes
 */
class Scisr_Operations_TrackClasses implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_CLASS,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $className = $phpcsFile->getDeclarationName($stackPtr);
        Scisr_Db_Classes::registerClass($className, $phpcsFile->getFilename());
    }

}
