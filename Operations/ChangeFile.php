<?php

/**
 * An operation to change the name of a class
 */
class Scisr_Operations_ChangeFile extends Scisr_Operations_AbstractFileOperation
{

    public $oldName;
    public $newName;

    public function __construct($oldName, $newName)
    {
        $this->oldName = Scisr_File::getAbsolutePath($oldName);
        $this->newName = Scisr_File::getAbsolutePath($newName);
    }

    /**
     * @todo document
     */
    public function processInclude($phpcsFile, $includedFile, $line, $column, $length, $quote, $tentative)
    {
        // If the filename matches, register it
        $base = $this->matchPaths($this->oldName, $includedFile, $phpcsFile);
        if ($base !== false) {
            $newName = $this->pathRelativeTo($this->newName, $base);
            Scisr_ChangeRegistry::addChange(
                $phpcsFile->getFileName(),
                $line,
                $column,
                $length,
                $quote . $newName . $quote,
                $tentative
            );
        }
    }

    /**
     * See if paths match satisfactorily
     * @param string $expectedPath an absolute path to target file
     * @param string $actualPath the absolute or relative path that was found
     * @param PHP_CodeSniffer_File $phpcsFile the file where $actualpath was 
     * found
     * @return string|bool false if the paths do not match. If they do match,
     * returns the base, if any, that is not explicitly defined by $actualPath.
     * Since this method may return the empty string on success, strict type
     * comparison must be used.
     */
    public function matchPaths($expectedPath, $actualPath, $phpcsFile)
    {
        // If it's an absolute path, it must match exactly
        if ($actualPath{0} == '/') {
            return (($expectedPath == $actualPath) ? '' : false);
        }
        // A simple test: see if the actual matches the end of the expected path
        if (strstr($expectedPath, $actualPath) == $actualPath) {
            $base = substr($expectedPath, 0, strpos($expectedPath, $actualPath));
            return $base;
        }
        return false;
    }

    /**
     * Get the path relative to a given base
     * @param string $path an absolute path to a file
     * @param string $basePath an absolute path serving as the base
     * @return string a relative path describing $path in relation to $basePath
     */
    public function pathRelativeTo($path, $basePath)
    {
        // Check for the implied ending slash
        if ($basePath != '' && substr($basePath, -1) != '/') {
            $basePath .= '/';
        }
        // Break up the paths and step through them
        $pathChunks = explode('/', $path);
        $baseChunks = explode('/', $basePath);
        $pos = 0;
        foreach ($baseChunks as $i => $baseChunk) {
            $pathChunk = $pathChunks[$i];
            $pos = $i;
            if ($baseChunk != $pathChunk) {
                break;
            }
        }
        // Now get the path from the point where it no longer matched the base
        $newPathChunks = array_slice($pathChunks, $pos);
        return implode('/', $newPathChunks);
    }

}
