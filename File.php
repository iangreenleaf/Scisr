<?php

/**
 * A single file, as Scisr sees it.
 *
 * Used to track edits and then make the actual changes.
 *
 * @todo Perhaps this could extend PHP_CodeSniffer_File. This would enable us to 
 * move a lot of the responsibilities of Scisr_ChangeRegistry into this class, 
 * which probably makes more sense in the end. And it ought to let us nicely 
 * encapsulate the business of caching parsed info (ticket #4).
 */
class Scisr_File
{

    /**
     * The path to the file
     * @var string
     */
    public $filename;
    /**
     * Stores the pending changes to this file.
     * A 2D array by line number, then column number
     * @var array
     */
    public $changes = array();
    /**
     * A new filename.
     * If not null, indicates this file is to be renamed.
     * @var string|null
     */
    private $_newName = null;

    /**
     * Create a new Scisr_File
     * @param string $filename the path to the file
     */
    public function __construct($filename)
    {
        $this->filename = self::getAbsolutePath($filename);
    }

    /**
     * Calculate the absolute path for a file
     * @param string $filename a relative or absolute path to a file
     * @param string $currDir an absolute path to the current directory, which 
     * will be used as the base of a relative path. Defaults to the current 
     * working directory.
     * @return string the absolute path to the file
     */
    public static function getAbsolutePath($filename, $currDir=null)
    {
        // If it's not an absolute path already, calculate it from our current dir
        if ($filename{0} != '/') {
            if ($currDir === null) {
                $currDir = getcwd();
            }
            $filename = $currDir . '/' . $filename;
        }
        return self::normalizePath($filename);
    }

    protected static function normalizePath($path)
    {
        $pieces = explode('/', $path);
        // Filter out empty items
        $pieces = array_filter($pieces, create_function('$s', 'return ($s !== "");'));
        // array_filter left us with wonky keys, which will confuse array_splice, so rekey
        $pieces = array_values($pieces);
        // Now look for . and ..
        while ($i = array_search('.', $pieces)) {
            array_splice($pieces, $i, 1);
        }
        while ($i = array_search('..', $pieces)) {
            array_splice($pieces, $i - 1, 2);
        }

        return '/' . implode('/', $pieces);
    }

    /**
     * Add a pending edit
     *
     * The edit will not actually be applied until you run {@link process()}.
     *
     * @param int $line the line number of the edit
     * @param int $column the column number where the edit begins
     * @param int $length length of the text to remove
     * @param string $replacement the text to replace the removed text with
     */
    public function addEdit($line, $column, $length, $replacement)
    {
        $this->changes[$line][$column] = array($length, $replacement);
    }

    /**
     * Set a pending file rename
     *
     * Will not actually be applied until you run {@link process()}.
     *
     * @param string $newName the new name for this file
     */
    public function rename($newName)
    {
        $this->_newName = self::getAbsolutePath($newName);
    }

    /**
     * Process all pending edits to the file
     */
    public function process()
    {
        // Sort by columns and then by lines
        foreach ($this->changes as $key => &$array) {
            ksort($array);
        }
        ksort($this->changes);

        // Get the file contents and open it for writing
        $contents = file($this->filename);
        $output = array();
        // Loop through the file contents, making changes
        foreach ($contents as $i => $line) {
            $lineNo = $i + 1;
            if (isset($this->changes[$lineNo])) {
                $lineEdits = $this->reconcileEdits($this->changes[$lineNo]);
                // Track the net column change caused by edits to this line so far
                $lineOffsetDelta = 0;
                foreach ($lineEdits as $col => $edit) {
                    $col += $lineOffsetDelta;
                    $length = $edit[0];
                    $replacement = $edit[1];
                    // Update the net offset with the change caused by this edit
                    $lineOffsetDelta += strlen($replacement) - $length;
                    // Make the change
                    $line = substr_replace($line, $replacement, $col - 1, $length);
                }
            }
            // Save the resulting line to be written to the file
            $output[] = $line;
        }
        // Write all output to the file
        file_put_contents($this->filename, $output);

        // If there's a rename pending, do it
        if ($this->_newName !== null) {
            $dir = dirname($this->_newName);
            if (!is_dir($dir)) {
                $success = mkdir($dir, 0775, true);
                if (!$success) {
                    $err = "Could not create new directory ($dir)";
                    throw new Exception($err);
                }
            }
            $success = rename($this->filename, $this->_newName);
            if (!$success) {
                $err = "Could not rename file ($this->filename => $this->_newName)";
                throw new Exception($err);
            }
        }
    }

    /**
     * Reconcile edit requests for a line.
     * Handles benign conflicts automatically.
     * @param array an array of edit requests for a line, as stored in $this->changes
     * @return array the array of all edit requests to act on
     */
    private function reconcileEdits($lineChanges)
    {
        $lineEdits = array();
        foreach ($lineChanges as $startCol => $edit) {
            $lineEdits = $this->checkNewEditForConflicts($edit, $startCol, $lineEdits);
        }
        return $lineEdits;
    }

    /**
     * Try to insert a new edit request into an existing list of edit requests.
     * May remove an existing edit or not insert the new edit if a resolvable conflict exists.
     * @param array $newEdit an edit request, as stored in $this->changes[][]
     * @param int $startCol the start column of $newEdit
     * @param array $previousEdits the list of existing edit requests
     * @return array a new list of edit requests with the new edit incorporated
     */
    private function checkNewEditForConflicts($newEdit, $startCol, $previousEdits)
    {
        $length = $newEdit[0];
        $endCol = $startCol + $length - 1;
        foreach ($previousEdits as $oldStartCol => $oldEdit) {

            $oldEndCol = $oldStartCol + $oldEdit[0] - 1;
            // Ignore unless this edit request conflicts
            if ($oldEndCol < $startCol || $oldStartCol > $endCol) {
                continue;
            }

            if ($startCol >= $oldStartCol && $endCol <= $oldEndCol) {
                // Previous edit encompasses all of this edit, ignore this edit
                return $previousEdits;
            } else if ($startCol <= $oldStartCol && $endCol >= $oldEndCol) {
                // This edit encompasses all of previous edit, remove previous edit
                $newEditsList = array_splice($previousEdits, $oldStartCol, 1);
                return $this->checkNewEditForConflicts($newEdit, $startCol, $newEditsList);
            } else {
                // Edit requests are staggered, no correct resolution is possible.
                // I don't expect this to ever happen unless a developer makes a mistake,
                // so we'll just abort messily
                $err = "We've encountered conflicting edit requests. Cannot continue.";
                throw new Exception($err);
            }

        }
        $previousEdits[$startCol] = $newEdit;
        return $previousEdits;
    }

}
