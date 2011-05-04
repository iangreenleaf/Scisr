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
     * A 2D array by line number, then column number.
     * @var array
     */
    public $changes = array();
    /**
     * Stores the pending tentative changes to this file.
     * These changes will only be made if we are in aggressive mode.
     * A 2D array by line number, then column number.
     * @var array
     */
    public $tentativeChanges = array();
    /**
     * A new filename.
     * If not null, indicates this file is to be renamed.
     * @var string|null
     */
    private $_newName = null;
    /**
     * Changes that were suggested but are not actually being made to this file.
     * @var array
     */
    private $_changesNotProcessed = array();

    /**
     * Create a new Scisr_File
     * @param string $filename the path to the file
     */
    public function __construct($filename)
    {
        $this->filename = self::getAbsolutePath($filename);
    }

    /**
     * Is a path explicitly relative?
     * Paths like "./foo" are explicitly relative. Paths like "/x/foo" clearly
     * are not, and paths like "x/foo" are considered implicitly relative but
     * not explicitly so.
     * @return boolean true if the path is explicitly relative
     */
    public static function isExplicitlyRelative($path) {
        return (preg_match('_^[.]+/_', $path) === 1);
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
        while (($i = array_search('.', $pieces)) !== false) {
            array_splice($pieces, $i, 1);
        }
        while (($i = array_search('..', $pieces)) !== false) {
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
    public function addEdit($line, $column, $length, $replacement, $tentative)
    {
        if ($tentative) {
            $this->tentativeChanges[$line][$column] = array($length, $replacement);
        } else {
            $this->changes[$line][$column] = array($length, $replacement);
        }
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
     * @param int $mode a constant from {@link ScisrRunner} indicating what mode we are running in
     * @return boolean true if this file was actually changed
     */
    public function process($mode)
    {
        if ($mode === ScisrRunner::MODE_AGGRESSIVE) {
            // In aggressive mode, all tentative changes get slated for application
            $this->changes = self::mergeChanges($this->tentativeChanges, $this->changes);
            $this->tentativeChanges = array();
        } else {
            if ($mode == ScisrRunner::MODE_TIMID) {
                // In timid mode, all changes become tentative
                $this->tentativeChanges = self::mergeChanges($this->tentativeChanges, $this->changes);
                $this->changes = array();
            }
            ksort($this->tentativeChanges);
            // We need to get rid of multiple tentative changes and any that are covered by actual changes
            foreach ($this->tentativeChanges as $lineNo => $changes) {
                $realChanges = (isset($this->changes[$lineNo]) ? $this->changes[$lineNo] : array());
                $this->_changesNotProcessed[$lineNo] = $this->reconcileNonEdits($changes, $realChanges);
            }
        }

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
        if ($this->_newName !== null && $mode !== ScisrRunner::MODE_TIMID) {
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
        return (count($this->changes) > 0);
    }

    /**
     * Merge two arrays of changes.
     * None of the php array functions quite hits the combination of recursion
     * and overwriting that we need here, so I rolled my own.
     * @param array $changes1 the first array of changes
     * @param array $changes2 the second array of changes
     * @return array the merged changes.
     */
    protected static function mergeChanges($changes1, $changes2)
    {
        $result = array();
        $keys = array_merge(array_keys($changes1), array_keys($changes2));
        foreach ($keys as $key) {
            if (!isset($changes1[$key])) {
                $result[$key] = $changes2[$key];
            } else if (!isset($changes2[$key])) {
                $result[$key] = $changes1[$key];
            } else {
                $result[$key] = $changes2[$key] + $changes1[$key];
            }
        }
        return $result;
    }

    /**
     * Get the number of edits which were not applied to this file
     * @return int
     */
    public function getNumChangesNotProcessed()
    {
        $sum = 0;
        foreach ($this->_changesNotProcessed as $line) {
            $sum += count($line);
        }
        return $sum;
    }

    /**
     * Get a list of lines that had edits which were not actually applied
     * @return array
     */
    public function getLinesNotProcessed()
    {
        return array_keys($this->_changesNotProcessed);
    }

    /**
     * Reconcile edit requests for a line.
     * Handles benign conflicts automatically.
     * @param array an array of edit requests for a line, as stored in $this->changes
     * @return array the array of all edit requests to act on
     */
    private function reconcileEdits($lineChanges)
    {
        $goodChanges = array();
        foreach ($lineChanges as $startCol => $edit) {
            $goodChanges = $this->checkNewEditForConflicts($edit, $startCol, $goodChanges);
        }
        return $goodChanges;
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

    /**
     * Reconcile edit notifications for a line, always removing from the first array
     * @param array $lineChanges an array of edit requests for a line
     * @param array $masterChanges an array of edit requests for the line that
     * will always win if there is a conflict
     * @return array all edits contained in $lineChanges that did not conflict
     * with other items in that list or in $masterChanges
     */
    private function reconcileNonEdits($lineChanges, $masterChanges)
    {
        $goodChanges = array();
        foreach ($lineChanges as $startCol => $edit) {
            if ($this->doesEditConflict($edit, $startCol, $masterChanges)) {
                continue;
            }
            $goodChanges = $this->checkNewEditForConflicts($edit, $startCol, $goodChanges);
        }
        return $goodChanges;
    }

    /**
     * See if an edit conflicts with the given list of edit requests
     * @param array $newEdit an edit request, as stored in $this->changes[][]
     * @param int $startCol the start column of $newEdit
     * @param array $existingEdits the list of existing edit requests
     * @return boolean true if $newEdit conflicts with something in $existingEdits
     */
    private function doesEditConflict($newEdit, $startCol, $existingEdits)
    {
        $length = $newEdit[0];
        $endCol = $startCol + $length - 1;
        foreach ($existingEdits as $oldStartCol => $oldEdit) {
            $oldEndCol = $oldStartCol + $oldEdit[0] - 1;
            // Ignore unless this edit request conflicts
            if ($oldEndCol >= $startCol && $oldStartCol <= $endCol) {
                return true;
            }
        }
        return false;
    }

}
