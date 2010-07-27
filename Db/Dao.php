<?php

/**
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
abstract class Scisr_Db_Dao
{
    protected $_db;

    public function __construct(PDO $db) {
        $this->_db = $db;
    }
}
