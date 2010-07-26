<?php

/**
 * A simple sniff that looks for classes being used and includes the file
 * containing that class.
 */
class Scisr_Operations_TrackClassIncludes
    extends Scisr_Operations_AbstractVariableTypeOperation
{

    /**
     * A list of filenames with arrays of classes called within those files.
     * @var array
     */
    private $_files = array();

    public function register()
    {
        return array(
            T_NEW,
            T_PAAMAYIM_NEKUDOTAYIM,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Find the class name
        if ($tokens[$stackPtr]['code'] == T_NEW) {
            $classPtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        } else {
            $classPtr = $phpcsFile->findPrevious(T_STRING, $stackPtr);
        }
        $classToken = $tokens[$classPtr];
        $className = $classToken['content'];

        $this->_files[$phpcsFile->getFilename()][] = $className;
    }

    /**
     * A callback to register all the includes from classes we found.
     * Performed as a callback so that full class=>file information is
     * available to us.
     */
    public function registerIncludes()
    {
        foreach ($this->_files as $filename => $classes) {
            $classes = array_unique($classes);
            foreach ($classes as $class) {
                $include = $this->_dbClasses->getClassFile($class);
                if ($include !== null) {
                    $this->_dbFileIncludes->registerFileInclude($filename, $include);
                }
            }
        }
    }

}
