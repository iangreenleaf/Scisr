<?php

/**
 * This class concentrates the wiring code for the ChangeRegistry instance needed 
 * in some Operations.
 *
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
abstract class Scisr_Operations_AbstractChangeOperation
{
    protected $_changeRegistry;

    public function __construct(Scisr_ChangeRegistry $changeRegistry)
    {
        $this->_changeRegistry = $changeRegistry;
    }
}
