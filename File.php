<?php

/**
 * A single file, as Scisr sees it.
 *
 * Used to track edits and then make the actual changes.
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
    private $newName = null;

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
     * @return string the absolute path to the file
     * @todo calculate something similar to realpath()
     */
    public static function getAbsolutePath($filename)
    {
        // If it's not an absolute path already, calculate it from our current dir
        if ($filename{0} != '/') {
            $base = getcwd();
            $filename = $base . '/' . $filename;
        }
        return $filename;
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
     * @todo detect conflicting edits
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
    public function rename($newName) {
        $this->newName = self::getAbsolutePath($newName);
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
        $handle = fopen($this->filename, "w");
        // Loop through the file contents, making changes
        foreach ($contents as $i => $line) {
            $lineNo = $i + 1;
            if (isset($this->changes[$lineNo])) {
                // Track the net column change caused by edits to this line so far
                $lineOffsetDelta = 0;
                // Track the (offset-adjusted) last column modified to prevent edit conflicts
                $lastChanged = 0;
                foreach ($this->changes[$lineNo] as $col => $edit) {
                    if ($col <= $lastChanged) {
                        // I don't expect this to ever happen unless a developer makes a mistake,
                        // so we'll just abort messily
                        $err = "We've encountered conflicting edit requests. Cannot continue.";
                        throw new Exception($err);
                    }
                    $col += $lineOffsetDelta;
                    $length = $edit[0];
                    $replacement = $edit[1];
                    // Update the net offset with the change caused by this edit
                    $lineOffsetDelta += strlen($replacement) - $length;
                    // Make the change
                    $line = substr_replace($line, $replacement, $col - 1, $length);
                    // Update to the last column this edit affected
                    $lastChanged = $col + $length - 1;
                }
            }
            // Write the resulting line to the file, whether or not it was modified
            fwrite($handle, $line);
        }

        // If there's a rename pending, do it
        if ($this->newName !== null) {
            rename($this->filename, $this->newName);
        }
    }
}
