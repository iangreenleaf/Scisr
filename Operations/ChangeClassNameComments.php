<?php

/**
 * An operation to change the name of a class in PHPDoc tags
 */
class Scisr_Operations_ChangeClassNameComments implements PHP_CodeSniffer_Sniff
{

    public $oldName;
    public $newName;
    protected $phpcsFile;
    protected $lastParsed = 0;

    public function __construct($oldName, $newName)
    {
        $this->oldName = $oldName;
        $this->newName = $newName;
    }

    public function register()
    {
        return array(
            T_COMMENT,
            T_DOC_COMMENT,
        );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        // If we've already parsed this comment, pass
        if ($phpcsFile == $this->phpcsFile && $stackPtr < $this->lastParsed) {
            return;
        }

        $this->phpcsFile = $phpcsFile;
        $tokens = $phpcsFile->getTokens();
        // Find the end of the comment block
        $endPtr = $phpcsFile->findNext($tokens[$stackPtr]['code'], $stackPtr + 1, null, true);
        $this->lastParsed = $endPtr;
        // Get the whole comment text
        $comment = $phpcsFile->getTokensAsString($stackPtr, $endPtr - $stackPtr + 1);
        $this->parser = new Scisr_CommentParser_ChangeTagValues($comment, $phpcsFile);
        $this->parser->parse();

        $elements = $this->parser->getTagElements();
        $columns = $this->parser->getTagElementColumns();

        foreach ($elements as $tagName => $tagArray) {
            $method = array($this, 'process' . ucfirst($tagName));
            if (is_callable($method)) {
                foreach ($tagArray as $i => $tag) {
                    call_user_func($method, $tag, $tokens[$stackPtr], $columns[$tagName][$i]);
                }
            }
        }
    }

    protected function processVar($var, $commentToken, $columns) {
        $this->findWordChanges($var, array('content'), $commentToken, $columns);
    }

    protected function processParam($param, $commentToken, $columns) {
        $this->findWordChanges($param, array('type'), $commentToken, $columns);
    }

    protected function processReturn($param, $commentToken, $columns) {
        $this->findWordChanges($param, array('value'), $commentToken, $columns);
    }

    protected function findWordChanges($docElement, $wordTypes, $commentToken, $columns) {
        $subElements = $docElement->getSubElementValues();
        $line = $commentToken['line'] + $docElement->getLine();
        $i = 0;
        foreach ($subElements as $name => $value) {

            if (in_array($name, $wordTypes) && $value == $this->oldName) {
                $column = $columns[$i];
                // Now register the change
                Scisr_ChangeRegistry::addChange(
                    $this->phpcsFile->getFileName(),
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
