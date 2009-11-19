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
     * Create a new Scisr_File
     * @param string $filename the path to the file
     */
    public function __construct($filename)
    {
        $this->filename = $filename;
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
     * Process all pending edits to the file
     */
    public function process()
    {
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

    }
}
