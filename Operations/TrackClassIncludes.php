<?php

/**
 * A simple sniff that looks for classes being used and includes the file
 * containing that class.
 */
class Scisr_Operations_TrackClassIncludes
    extends Scisr_Operations_AbstractVariableTypeOperation
{

    public function register()
    {
        return array(
            T_NEW,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Find the class name
        $classPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $classToken = $tokens[$classPtr];
        $className = $classToken['content'];

        $filename = Scisr_Db_Classes::getClassFile($className);
        if ($filename !== null) {
            Scisr_Db_FileIncludes::registerFileInclude($phpcsFile->getFileName(), $filename);
        }
    }

}
