<?php

/**
 * A simple output class that captures all output and provides it as a string 
 * upon request.
 */
class Scisr_Output_String implements Scisr_Output
{
    public function outputString($message)
    {
        $this->output .= trim($message) . "\n";
    }
    public function getOutput()
    {
        return $this->output;
    }
}
