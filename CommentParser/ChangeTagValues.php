<?php

class Scisr_CommentParser_ChangeTagValues extends PHP_CodeSniffer_CommentParser_AbstractParser
{

    protected $_tagElements = array();

    protected function getAllowedTags() {
        return array(
            'var' => false,
            'param' => false,
        );
    }

    protected function parseVar($tokens) {
        $element = new Scisr_CommentParser_SingleElement(
            $this->previousElement,
            $tokens,
            'var',
            $this->phpcsFile
        );
        $this->_tagElements['var'][] = $element;
        return $element;
    }

    protected function parseParam($tokens) {
        $element = new Scisr_CommentParser_ParameterElement(
            $this->previousElement,
            $tokens,
            $this->phpcsFile
        );
        $this->_tagElements['param'][] = $element;
        return $element;
    }

    /**
     * Get a list of tag elements
     *
     * Returns a list of tag names => arrays of elements processed for that tag
     */
    public function getTagElements() {
        return $this->_tagElements;
    }

    /**
     * Get a list of tag element column numbers
     *
     * Returns a list of tag names => arrays of column numbers for elements of that tag
     */
    public function getTagElementColumns() {
        return $this->_tagColumns;
    }

    // Overridden because we need to track column numbers and this is really the
    // only suitable place to do so.
    protected function _parse($comment)
    {
        // Firstly, remove the comment tags and any stars from the left side.
        $lines = explode($this->phpcsFile->eolChar, $comment);
        foreach ($lines as &$line) {
            $oldLength = strlen($line);
            $line = trim($line);
            $colNum = $oldLength - strlen($line) + 1;

            if ($line !== '') {
                if (substr($line, 0, 3) === '/**') {
                    $line = substr($line, 3);
                    $colNum += 3;
                } else if (substr($line, 0, 2) === '/*') {
                    $line = substr($line, 2);
                    $colNum += 2;
                }
                if (substr($line, -2, 2) === '*/') {
                    $line = substr($line, 0, -2);
                } else if ($line{0} === '*') {
                    $line = substr($line, 1);
                    $colNum += 1;
                }

                // Add the words to the stack, preserving newlines. Other parsers
                // might be interested in the spaces between words, so tokenize
                // spaces as well as separate tokens.
                $flags = (PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                $words = preg_split(
                    '|(\s+)|',
                    $line.$this->phpcsFile->eolChar,
                    -1,
                    $flags
                );

                foreach ($words as $w) {
                    $this->words[] = $w;
                    $this->columnNums[] = $colNum;
                    $colNum += strlen($w);
                }
            }
        }//end foreach

        $this->_parseWords();

    }//end _parse()

    // Overridden to track column numbers
    protected function parseTag($tag, $start, $end) {
        parent::parseTag($tag, $start, $end);
        // Save column numbers for all non-empty members
        $cols = array();
        for ($i = $start + 1; $i < $end; $i++) {
            if (trim($this->words[$i]) != '') {
                $cols[] = $this->columnNums[$i];
            }
        }
        $this->_tagColumns[$tag][] = $cols;
    }

}
