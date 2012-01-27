<?php

/**
 * Creates a file.
 * Todo: Create abstract baseclass for both file classes
 * Author: Tarjei Huse (tarjei@scanmine.com) http://www.kraken.no
 */
class Scisr_CreateFile extends Scisr_File {

    public function __construct($filename, $content)
    {

        $this->content = $content;
        $this->filename = $filename;
    }

    public function process($mode)
    {
        if ($mode != ScisrRunner::MODE_AGGRESSIVE && file_exists($this->filename)) {
            throw new RuntimeException("Cannot overwrite {$this->filename} in timid mode!");
        }
        file_put_contents($this->filename, $this->content);
    }

    public function addEdit($line, $column, $length, $replacement, $tentative)
    {
        throw new Exception("Unsupported!");
    }
    public function rename($newName)
    {
        throw new Exception("Unsupported!");
    }


}

