<?php

class Scisr_CommentParser_PairElement extends PHP_CodeSniffer_CommentParser_PairElement
{

    /**
     * Get all subelement values
     *
     * @return array a list of subelement names => contents
     */
    public function getSubElementValues()
    {
        return array(
            'value' => $this->getValue(),
            'comment' => $this->getComment(),
        );

    }//end getSubElements()
}
