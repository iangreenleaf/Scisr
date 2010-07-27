<?php
require_once 'MultipleFileTest.php';

class RenameMethodSystemTest extends Scisr_Tests_MultipleFileTestCase
{

    public function testRenameMethodAndCompareFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameMethod('Foo', 'bar', 'quark', false);
        $s->addFile($this->test_dir . '/test.php');
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-method/test.php', $this->test_dir . '/test.php');
    }

    public function testRenameMethodAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/cliFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameMethod('Foo', 'bar', 'quark', false);
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/cliFixture-after-rename-method', $this->test_dir);
    }

    public function testRenameMethodWithIncludedFile() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameMethod('Foo', 'bar', 'baz', false);
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameMethodWithIncludesFixture-after-rename', $this->test_dir);
    }

    public function testClassInclusionOrder() {
        $this->populateDir(dirname(__FILE__) . '/_files/renameMethodWithClassFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameMethod('Foo', 'bar', 'baz', false);
        // We want to force an ordering here
        $s->addFile($this->test_dir . '/a.php');
        $s->addFile($this->test_dir . '/b.php');
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/renameMethodWithClassFixture-after-rename', $this->test_dir);
    }

}
