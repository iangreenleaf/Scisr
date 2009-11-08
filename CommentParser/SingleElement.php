<?php

class Scisr_CommentParser_SingleElement extends PHP_CodeSniffer_CommentParser_SingleElement
{

    /**
     * Get all subelement values
     *
     * @return array a list of subelement names => contents
     */
    public function getSubElementValues()
    {
        return array('content' => $this->content);

    }//end getSubElements()
}
