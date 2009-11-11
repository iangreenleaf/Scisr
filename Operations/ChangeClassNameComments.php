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

    public function __construct($oldName, $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    protected function processVar($var, $commentToken, $columns)
    {
        $this->findWordChanges($var, array('content'), $commentToken, $columns);
    }

    protected function processParam($param, $commentToken, $columns)
    {
        $this->findWordChanges($param, array('type'), $commentToken, $columns);
    }

    protected function processReturn($param, $commentToken, $columns)
    {
        $this->findWordChanges($param, array('value'), $commentToken, $columns);
    }

    protected function findWordChanges($docElement, $wordTypes, $commentToken, $columns)
    {
        $subElements = $docElement->getSubElementValues();
        $line = $commentToken['line'] + $docElement->getLine();
        $i = 0;
        foreach ($subElements as $name => $value) {

            if (in_array($name, $wordTypes) && $value == $this->oldName) {
                $column = $columns[$i];
                // Now register the change
                Scisr_ChangeRegistry::addChange(
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
