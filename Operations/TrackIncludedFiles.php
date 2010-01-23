<?php

/**
 * Track and register included files
 */
class Scisr_Operations_TrackIncludedFiles extends Scisr_Operations_AbstractFileOperation
{

    /**
     * @todo document
     */
    public function processInclude($phpcsFile, $includedFile, $line, $column, $length, $quote, $tentative)
    {
        $includedFile = Scisr_File::getAbsolutePath($includedFile, dirname($phpcsFile->getFileName()));
        Scisr_FileIncludes::registerFileInclude($phpcsFile->getFileName(), $includedFile);
    }

}
