<?php
require_once 'MultipleFileTest.php';

class RenameClassFileSystemTest extends Scisr_Tests_MultipleFileTestCase
{
    /**
     * @dataProvider classFileProvider
     */
    public function testRenameClassFileAndCompareFile($oldName, $newName, $oldFile, $newFile) {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile($oldName, $newName);
        $s->addFile($this->test_dir . '/' . $oldFile);
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/classFileFixture-after-rename/' . $newFile, $this->test_dir . '/' . $newFile);
    }

    public function classFileProvider() {
        return array(
            array('Foo', 'Baz', 'Foo.php', 'Baz.php'),
            array('MyOtherClass', 'NewClass', 'dir/MyOtherClass.inc', 'dir/NewClass.inc'),
            array('ThirdClass', 'FourthClass', 'some_class_files.php', 'some_class_files.php'),
        );
    }

    public function testDontRenameClassFileWithoutAllowedExtension() {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile('MyOtherClass', 'NewClass');
        $s->setAllowedFileExtensions(array('php'));
        $s->addFile($this->test_dir);
        $s->run();

        // Make sure nothing changed
        $this->compareFile(dirname(__FILE__) . '/_files/classFileFixture/dir/MyOtherClass.inc', $this->test_dir . '/dir/MyOtherClass.inc');
    }

    public function testRenameClassFileWithFunnyExtension() {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile('StrangeClass', 'StrangeQuark');
        $s->setAllowedFileExtensions(array('php', 'foo'));
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareFile(dirname(__FILE__) . '/_files/classFileFixture-after-rename/StrangeQuark.foo', $this->test_dir . '/StrangeQuark.foo');
    }

    public function testRenameDirWithNamespacing() {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileNamespacedFixture', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile('Foo_Bar_Quark', 'Foo_Baz_Quack');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/classFileNamespacedFixture-after-rename', $this->test_dir);
    }

    public function testDontCreateDirNamespacingIfOriginalIsnt() {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileNamespacedFixture2', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile('Foo_Bar_Quark', 'Foo_Baz_Quack');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/classFileNamespacedFixture2-after-rename', $this->test_dir);
    }

    public function testRenameClassFileAndCompareDir() {
        $this->populateDir(dirname(__FILE__) . '/_files/classFileFixture-dir', $this->test_dir);

        $s = $this->getScisr();
        $s->setRenameClassFile('Foo', 'Baz');
        $s->addFile($this->test_dir);
        $s->run();

        $this->compareDir(dirname(__FILE__) . '/_files/classFileFixture-dir-after-rename', $this->test_dir);
    }
}
