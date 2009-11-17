<?php

/**
 * Change a word by regexp match
 */
abstract class Scisr_Operations_AbstractPatternMatchOperation implements PHP_CodeSniffer_Sniff
{

    public $oldString;
    public $newString;

    /**
     * @param string $oldString the string to change. Only this exact word will
     * be changed - words containing it will not change.
     * @param string $newString the string to change it to
     */
    public function __construct($oldString, $newString)
    {
        $this->oldString = $oldString;
        $this->newString = $newString;
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
            Scisr_ChangeRegistry::addChange(
                $phpcsFile->getFileName(),
                $tokenInfo['line'],
                $tokenInfo['column'] + $offset,
                strlen($this->oldString),
                $this->newString
            );
        }
    }
}
