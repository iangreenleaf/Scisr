<?php

class Scisr_File {

    public $filename;

    public function __construct($filename) {
        $this->filename = $filename;
    }

    /**
     * @todo detect conflicting edits
     */
    public function addEdit($line, $column, $length, $replacement) {
        $this->changes[$line][$column] = array($length, $replacement);
    }

    public function process() {
        $contents = file($this->filename);
        $handle = fopen($this->filename, "w");
        $lineNo++;
        foreach ($contents as $i => $line) {
            $lineNo = $i + 1;
            if (isset($this->changes[$lineNo])) {
                foreach ($this->changes[$lineNo] as $col => $edit) {
                    $length = $edit[0];
                    $replacement = $edit[1];
                    $line = substr_replace($line, $replacement, $col - 1, $length);
                }
            }
            fwrite($handle, $line);
        }

    }
}
