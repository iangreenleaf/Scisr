<?php

/**
 * An operation to look for the name of a class, and trigger a file rename
 * if it's situated in an appropriately named file
 */
class Scisr_Operations_RenameClassFile implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;
    protected $_scisr;

    /**
     * @param string $oldClass the class to be renamed
     * @param string $newClass the new class name to be given
     * @param Scisr $scisr the calling instance
     */
    public function __construct($oldName, $newName, $scisr)
    {
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

    /**
     * @todo Right now, this runs on both passes, even though it should really 
     * only be running on the first pass. It's not breaking anything, but I'm 
     * not crazy about this.
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $classNamePtr = $phpcsFile->findNext(T_STRING, $stackPtr);
        $tokenInfo = $tokens[$classNamePtr];
        $className = $tokenInfo['content'];
        // If it's the name we're looking for, see if the filename is a match
        if ($className == $this->oldName) {
            $filename = basename($phpcsFile->getFilename());
            $pieces = explode('_', $this->oldName);
            foreach (array_keys($this->_scisr->getAllowedFileExtensions()) as $ext) {
                if ($filename == "$this->oldName.$ext") {
                    $dir = dirname($phpcsFile->getFilename());
                    $this->_scisr->setRenameFile($phpcsFile->getFilename(), "$dir/$this->newName.$ext");
                    break;
                } else if (count($pieces) > 0) {
                    $namespacedFile = implode('/', $pieces) . ".$ext";
                    $baseDir = Scisr_Operations_ChangeFile::matchPaths($phpcsFile->getFilename(), $namespacedFile);
                    if ($baseDir !== false) {
                        $newNamespacedFile = implode('/', explode('_', $this->newName)) . ".$ext";
                        $this->_scisr->setRenameFile($phpcsFile->getFilename(), "$baseDir$newNamespacedFile");
                        break;
                    }
                }
            }
        }
    }
}
