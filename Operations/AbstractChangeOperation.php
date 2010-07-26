<?php

/**
 * This class concentrate the wiring code for the ChangeRegistry instance needed 
 * in some Operations.
 */
abstract class Scisr_Operations_AbstractChangeOperation
{
    protected $_changeRegistry;
    protected $_dbClasses;
    protected $_dbFileIncludes;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, Scisr_Db_Classes $dbClasses, Scisr_Db_FileIncludes $dbFileIncludes)
    {
        $this->_changeRegistry = $changeRegistry;
        $this->_dbClasses = $dbClasses;
        $this->_dbFileIncludes = $dbFileIncludes;
    }
}
