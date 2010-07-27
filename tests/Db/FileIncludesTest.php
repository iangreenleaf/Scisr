<?php
require_once 'Scisr_TestCase.php';

class Scisr_Db_FileIncludesTest extends Scisr_TestCase {
    public function testIncludeFileRepeatedly() {
        $dbFileIncludes = new Scisr_Db_FileIncludes(Scisr_Db::getDb());
        $dbFileIncludes->init();
        $dbFileIncludes->registerFileInclude('/x/y/myfile.php', '/x/z/otherfile.php');
        $dbFileIncludes->registerFileInclude('/x/y/myfile.php', '/x/z/otherfile.php');
        $this->assertSame(array('/x/z/otherfile.php'), $dbFileIncludes->getIncludedFiles('/x/y/myfile.php'));
    }
}
