<?php

/**
 * An operation to change the name of a class in PHPDoc tags
 */
class Scisr_Operations_ChangeClassNameComments
    extends Scisr_Operations_AbstractTrackVariableTypeOperation
    implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;

    public function __construct(Scisr_ChangeRegistry $changeRegistry, Scisr_Db_Classes $dbClasses, Scisr_Db_FileIncludes $dbFileIncludes, $oldName, $newName)
    {
        parent::__construct($changeRegistry, $dbClasses, $dbFileIncludes);
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    protected function processVar($var, $commentPtr, $phpcsFile, $columns)
    {
        $this->findWordChanges($var, array('content'), $commentPtr, $phpcsFile, $columns);
    }

    protected function processParam($param, $commentPtr, $phpcsFile, $columns)
    {
        $this->findWordChanges($param, array('type'), $commentPtr, $phpcsFile, $columns);
    }

    protected function processReturn($return, $commentPtr, $phpcsFile, $columns)
    {
        $this->findWordChanges($return, array('value'), $commentPtr, $phpcsFile, $columns);
    }

    protected function findWordChanges($docElement, $wordTypes, $commentPtr, $phpcsFile, $columns)
    {
        $subElements = $docElement->getSubElementValues();
        $tokens = $phpcsFile->getTokens();
        $commentToken = $tokens[$commentPtr];
        $line = $commentToken['line'] + $docElement->getLine();
        $i = 0;
        foreach ($subElements as $name => $value) {

            if (in_array($name, $wordTypes) && $value == $this->oldName) {
                $column = $columns[$i];
                // Now register the change
                $this->_changeRegistry->addChange(
                    $this->_phpcsFile->getFileName(),
                    $line,
                    $column,
                    strlen($this->oldName),
                    $this->newName
                );
            }
            $i++;
        }
    }
}
