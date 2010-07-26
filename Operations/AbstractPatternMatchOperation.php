<?php

/**
 * Change a word by regexp match
 */
abstract class Scisr_Operations_AbstractPatternMatchOperation extends Scisr_Operations_AbstractChangeOperation implements PHP_CodeSniffer_Sniff
{

    public $oldString;
    public $newString;
    public $tentative;

    /**
     * @param string $oldString the string to change. Only this exact word will
     * be changed - words containing it will not change. May contain regexp 
     * features (but do not surround in '/'s).
     * @param string $newString the string to change it to. May contain 
     * backreferences, marked with a double-backslash, i.e. '\\1'
     * @param boolean $tentative whether the changes we detect should be
     * considered tentative
     */
    public function __construct(Scisr_ChangeRegistry $changeRegistry, Scisr_Db_Classes $dbClasses, $oldString, $newString, $tentative=true)
    {
        parent::__construct($changeRegistry, $dbClasses);
        $this->oldString = $oldString;
        $this->newString = $newString;
        $this->tentative = $tentative;
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $tokenInfo = $tokens[$stackPtr];
        $matches = array();
        // Look for exact matches of our word
        preg_match_all(
            "/\b$this->oldString\b/",
            $tokenInfo['content'],
            $matches,
            PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
        );
        foreach ($matches[0] as $match) {
            $offset = $match[1];
            $oldStringMatch = $match[0];
            $newString = preg_replace("/$this->oldString/", $this->newString, $oldStringMatch);
            $this->_changeRegistry->addChange(
                $phpcsFile->getFileName(),
                $tokenInfo['line'],
                $tokenInfo['column'] + $offset,
                strlen($oldStringMatch),
                $newString,
                $this->tentative
            );
        }
    }
}
