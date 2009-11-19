<?php
/**
 * A simple stub that accepts output from Scisr and simply throws it away
 */
class Scisr_NullOutput implements Scisr_Output
{
    public function outputString($message)
    {
        // Do nothing
    }
}
