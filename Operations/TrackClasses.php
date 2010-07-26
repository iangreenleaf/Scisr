<?php

/**
 * Tracks classes
 */
class Scisr_Operations_TrackClasses implements PHP_CodeSniffer_Sniff
{
    private $_dbClasses;

    public function __construct(Scisr_Db_Classes $dbClasses)
    {
        $this->_dbClasses = $dbClasses;
    }

    public function register()
    {
        return array(
            T_CLASS,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $className = $phpcsFile->getDeclarationName($stackPtr);
        $this->_dbClasses->registerClass($className, $phpcsFile->getFilename());

        $extends = $phpcsFile->findExtendedClassName($stackPtr);
        if ($extends !== false) {
            $this->_dbClasses->registerClassExtends($className, $extends);
        }

        $implements = $phpcsFile->findImplementsClassName($stackPtr);
        if (count($implements) > 0) {
            $this->_dbClasses->registerClassImplements($className, $implements);
        }
    }

}
