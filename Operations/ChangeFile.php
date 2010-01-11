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
        $this->oldName = Scisr_File::getAbsolutePath($oldName);
        $this->newName = Scisr_File::getAbsolutePath($newName);
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
        $base = $this->matchPaths($this->oldName, $fileStr, $phpcsFile);
        if ($base !== false) {
            $newName = $this->pathRelativeTo($this->newName, $base);
            Scisr_ChangeRegistry::addChange(
                $phpcsFile->getFileName(),
                $tokenInfo['line'],
                $tokenInfo['column'],
                strlen($tokenInfo['content']),
                $quote . $newName . $quote
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
        // A simple test: see if the actual matches the end of the expected path
        //return strstr($expectedPath, $actualPath, true);
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
