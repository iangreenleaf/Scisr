<?php

/**
 * This class concentrate the wiring code for the ChangeRegistry instance needed 
 * in some Operations.
 */
abstract class Scisr_Operations_AbstractChangeOperation
{
    protected $_changeRegistry;
    protected $_dbClasses;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, Scisr_Db_Classes $dbClasses)
    {
        $this->_changeRegistry = $changeRegistry;
        $this->_dbClasses = $dbClasses;
    }
}
