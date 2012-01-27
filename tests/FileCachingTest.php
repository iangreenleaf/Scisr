<?php
require_once 'SingleFileTest.php';

/**
 * Test caching of parser results
 *
 * Here's the deal: info gathered from the first pass may be cached, because no 
 * modification takes place during that pass. We still have to run these 
 * listeners during the second pass, because we get more accurate info when we 
 * are parsing sequentially (i.e. the most recent type assignment is most likely 
 * to be accurate).
 */
class FileCachingTest extends Scisr_SingleFileTest
{

    public function testUseCachedFirstPass() {
        $code = <<<EOL
<?php
include "Foo.php"
EOL;
        $this->populateFile($code);
        // We're using the include file sniff simply because it's easy
        $mock = $this->getSniff();
        // Sniff should be activated three times: twice the first run (once on 
        // each pass), and only once the second (second pass only)
        $mock->expects($this->exactly(1))->method('process');
        // Make sure we don't get confused by an mtime exactly equal to the cache time
        touch($this->test_file, time() - 1);

        $this->runWithMock($mock);
        $this->runWithMock($mock);
    }

    public function testDontUseCacheWhenMTimeNewer() {
        $code = <<<EOL
<?php
include "Foo.php"
EOL;
        $this->populateFile($code);
        $mock = $this->getSniff();
        $mock->expects($this->exactly(2))->method('process');
        $this->runWithMock($mock);
        touch($this->test_file, time() + 1);
        $this->runWithMock($mock);
    }

    /**
     * We need to make sure to ignore results from rename-class-file and any 
     * other operations that use the first pass, because right now the only one 
     * that is cacheable is rename-method results.
     */
    public function testDontUseIncompleteCacheResults() {
        $code = <<<EOL
<?php
include "Foo.php"
EOL;
        $this->populateFile($code);
        $s = $this->getScisr();
        $s->setRenameClassFile('Foo', 'Bar');
        $s->addFile($this->test_file);
        $s->run();
        $mock = $this->getSniff();
        $mock->expects($this->exactly(1))->method('process');
        $this->runWithMock($mock);
    }

    public function testDontKeepStaleResults() {
        $this->markTestIncomplete();
    }

    private function getSniff()
    {
        return $this->getMock('Scisr_Operations_TrackIncludedFiles', array('process'), array(), '', false);
    }

    private function runWithMock($mock) {
        $s = ScisrRunner::createRunner('Scisr_FileTest');
        $s->setFirstPassListener($mock);
        $s->addFile($this->test_file);
        $s->run();
    }

}

class Scisr_FileTest extends ScisrRunner
{
    private $_extraListeners = array();
    public function setFirstPassListener($listener) {
        $this->_extraListeners[] = $listener;
        $this->_firstPassRequired = true;
    }
    public function getFirstPassListeners() {
        return array_merge(parent::getFirstPassListeners(), $this->_extraListeners);
    }
}
