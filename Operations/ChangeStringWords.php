<?php

/**
 * Change a word in PHP strings
 */
class Scisr_Operations_ChangeStringWords extends Scisr_Operations_AbstractPatternMatchOperation 
{

    public function register()
    {
        return array(
            T_ENCAPSED_AND_WHITESPACE,
            T_CONSTANT_ENCAPSED_STRING,
            T_DOUBLE_QUOTED_STRING,
        );
    }
}
