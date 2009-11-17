<?php

/**
 * Change a word in regular comments
 */
class Scisr_Operations_ChangeCommentWords extends Scisr_Operations_AbstractPatternMatchOperation 
{

    public function register()
    {
        return array(
            T_COMMENT,
            T_DOC_COMMENT,
        );
    }
}
