<?php

/**
 * An operation to change the name of a class
 */
abstract class Scisr_Operations_AbstractFileOperation implements PHP_CodeSniffer_Sniff
{

    public function register()
    {
        return array(
            T_INCLUDE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_REQUIRE_ONCE,
            );
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        // Find the arguments to this call
        $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        $nextToken = $tokens[$nextPtr];
        // We have to account for the possibility of these calls not having parentheses
        if ($nextToken['code'] == T_OPEN_PARENTHESIS) {
            $strTokens = $this->getStringTokens($tokens, $nextPtr + 1, $nextToken['parenthesis_closer']);
        } else {
            $endStmtPtr = $phpcsFile->findNext(T_SEMICOLON, $stackPtr);
            $strTokens = $this->getStringTokens($tokens, $nextPtr, $endStmtPtr);
        }
        // Decide what to do with the results
        if (!$strTokens) {
            // We failed or didn't get any tokens, quit
            return false;
        } else if (count($strTokens) == 1) {
            // If there's only one token, we can go ahead and make the change confidently
            $fileToken = $strTokens[0];
            $fileStr = $fileToken['content'];
            $length = strlen($fileStr);
            $line = $fileToken['line'];
            $column = $fileToken['column'];
            // Strip the quotes
            $quote = $fileStr{0};
            $fileStr = substr($fileStr, 1, -1);
            $intact = true;
        } else {
            // Otherwise we'll be more cautious - but if aggressive, we'll mush the
            // string tokens into one big string. This could get messy.
            $firstToken = $strTokens[0];
            $quote = $firstToken['content']{0};
            $column = $firstToken['column'];
            $line = $firstToken['line'];
            $lastToken = $strTokens[count($strTokens) - 1];
            $length = $lastToken['column'] + strlen($lastToken['content']) - $column;
            $fileStr = '';
            foreach ($strTokens as $str) {
                $fileStr .= substr($str['content'], 1, -1);
            }
            $intact = false;
        }
        $this->processInclude($phpcsFile, $fileStr, $line, $column, $length, $quote, !$intact); 
    }

    /**
     * Parse a section of tokens, looking for string tokens.
     *
     * Filters out whitespace and string concats. Quits in failure if any
     * other kind of tokens are encountered.
     *
     * @param array $tokens the array of tokens
     * @param int $startPtr the stack pointer at which to begin parsing
     * @param int $endPtr the stack pointer (exclusive) at which to halt
     * @return array|null an array of all the string tokens, or false if we
     * did not succeed.
     */
    protected function getStringTokens($tokens, $startPtr, $endPtr)
    {
        $currPtr = $startPtr;
        $result = array();
        while ($currPtr < $endPtr) {
            $currToken = $tokens[$currPtr];
            if ($currToken['code'] == T_CONSTANT_ENCAPSED_STRING) {
                $result[] = $currToken;
            } else if (!in_array($currToken['code'], array(T_STRING_CONCAT, T_WHITESPACE))) {
                // We've hit something we can't handle, fail
                return false;
            }
            $currPtr++;
        }
        return $result;
    }

    abstract public function processInclude($phpcsFile, $includedFile, $line, $column, $length, $quote, $tentative);
}
