<?php

/**
 * An operation to change the name of a class
 */
class Scisr_Operations_RenameFile extends Scisr_Operations_AbstractFileOperation
{
    private $_changeRegistry;
    public $oldName;
    public $newName;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, $oldName, $newName)
    {
        $this->_changeRegistry = $changeRegistry;
        $this->oldName = Scisr_File::getAbsolutePath($oldName);
        $this->newName = Scisr_File::getAbsolutePath($newName);
    }

    public function processInclude($phpcsFile, $includedFile, $line, $column, $length, $quote, $tentative)
    {
        $isRelative = Scisr_File::isExplicitlyRelative($includedFile);
        $addChange = false;
        if ($isRelative && $this->oldName == $phpcsFile->getFileName()) {
            // This include is relative and inside the file that is being
            // renamed, we may need to change the relative path.
            $absIncludedFile = Scisr_File::getAbsolutePath($includedFile, dirname($phpcsFile->getFileName()));
            $newName = $this->pathRelativeTo($absIncludedFile, dirname($this->newName), true);
            $addChange = ($newName != $includedFile);
        } else {
            // This include matches the file we are renaming
            $base = self::matchPaths($this->oldName, $includedFile, dirname($phpcsFile->getFileName()));
            if ($base !== false) {
                $newName = $this->pathRelativeTo($this->newName, $base, $isRelative);
                $addChange = true;
            }
        }
        // Add the change if we found one
        if ($addChange) {
            $this->_changeRegistry->addChange(
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
     * @param string $currDir the current directory to calculate relative paths
     * from. If $actualPath is an explicitly relative path (i.e. "../foo"), you
     * <b>must</b> provide this!
     * @return string|bool false if the paths do not match. If they do match,
     * returns the base, if any, that is not explicitly defined by $actualPath.
     * Since this method may return the empty string on success, strict type
     * comparison must be used.
     */
    public static function matchPaths($expectedPath, $actualPath, $currDir=false)
    {
        if (Scisr_File::isExplicitlyRelative($actualPath)) {
            if ($currDir === false) {
                throw new LogicException('You provided a relative path without a current dir!');
            }
            $actualPath = Scisr_File::getAbsolutePath($actualPath, $currDir);
            // Add a trailing slash if it's not there
            if (substr($currDir, -1) != '/') {
                $currDir .= '/';
            }
            return (($expectedPath == $actualPath) ? $currDir : false);
        }

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
     * @param boolean $explicitlyRelative if true, return a path that is
     * relative to $basePath (i.e. "./foo/bar"). If false, simply return the path
     * implicitly from the base (i.e. "foo/bar").
     * @return string a relative path describing $path in relation to $basePath
     */
    public function pathRelativeTo($path, $basePath, $explicitlyRelative)
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
        if (!$explicitlyRelative) {
            return implode('/', $newPathChunks);
        } else {
            // Get the part of the base that differs
            $oldPathChunks = array_slice($baseChunks, $pos);
            // Remove the empty entry that may show up
            $oldPathChunks = array_filter($oldPathChunks);
            // Replace all intermediate directories with ".."
            $toDotDot = create_function('$v', 'return "..";');
            $newRelPath = array_map($toDotDot, $oldPathChunks);
            // Now smush it all together
            $newRelPath[] = implode('/', $newPathChunks);
            $newName = implode('/', $newRelPath);
            if ($newName{0} != '.' && $newName{0} != '/') {
                $newName = "./$newName";
            }
            return $newName;
        }
    }

}
