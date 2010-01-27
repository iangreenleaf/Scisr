<?php
/**
 * An output handler for the command line
 */
class Scisr_CLI_Output implements Scisr_Output
{
    public function outputString($message)
    {
        echo trim($message) . "\n";
    }
}
