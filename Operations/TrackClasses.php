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

        $extends = $phpcsFile->findExtendedClassName($stackPtr);
        if ($extends !== false) {
            Scisr_Db_Classes::registerClassExtends($className, $extends);
        }

        $implements = $phpcsFile->findImplementsClassName($stackPtr);
        if (count($implements) > 0) {
            Scisr_Db_Classes::registerClassImplements($className, $implements);
        }
    }

}
