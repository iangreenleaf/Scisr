<?php
/**
 * Defines an object that takes output from Scisr and displays it as appropriate
 */
interface Scisr_Output
{
    /**
     * Accepts a string as output
     * @param string
     */
    public function outputString($message);
}
