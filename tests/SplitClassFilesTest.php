<?php
require_once 'SingleFileTest.php';

class SplitClassFilesTest extends Scisr_SingleFileTest
{

    protected $function = <<<EOL
/**
 * Some func comment
 */
function someFunc(\$args) {
  /* a comment !*/
};
EOL;
    protected $baz = <<<EOL
class Baz {
    function bar() {
    }
}
EOL;
    protected $bar = <<<EOL
class Bar {
    function bar() {
    }
}
EOL;
    protected $start = "<?php \n ";

    protected $comment = "/** this is a comment about a class */";


    private $outputDir;
    public function setUp() {
        parent::setUp();
        $this->outputDir = __DIR__ . "/" . "_tmp" . __CLASS__;
        /* reset test area */
        $this->delTree($this->outputDir) ;
        mkdir($this->outputDir);

    }
    public function splitAndCompare($original, $expected, $aggressive=false) {
        $this->populateFile($original);

        $s = $this->getScisr();
        if ($aggressive) {
            $s->setEditMode(ScisrRunner::MODE_AGGRESSIVE);
        }


        $s->setSplitClassFiles($this->outputDir);
        $s->addFile($this->test_file);
        $s->run();

        //$this->compareFile($this->test_file, $original);
        foreach ($expected as $filename => $content) {
            $actual = file_get_contents($this->outputDir . "/" . $filename . ".php" );
            //         var_dump(array('ac' =>$actual, 'o' => $content));
            $this->assertEquals($actual, $content);
        }
    }
    public function testSplitFilesTwoClasses() {
        $orig = "{$this->start}{$this->baz}\n{$this->bar}";
        $expected = array(
            'Baz' => $this->baz . "\n", 'Bar' => $this->bar . "\n" ); 
        $this->splitAndCompare($orig, $expected);
    }


    public function testSplitFilesTwoClassesWithComments() {
        $orig = "{$this->start}
{$this->comment}
{$this->baz}\n
{$this->comment}
{$this->bar}";
        $expected = array(
            'Baz' => $this->comment . "\n" . $this->baz . "\n", 
            'Bar' => $this->comment . "\n". $this->bar . "\n" 
            ); 
        $this->splitAndCompare($orig, $expected);
    }

    public function testSplitFilesTwoClassesWithCommentsAndAFunctionOnTop() {
        $orig = "{$this->start}
{$this->function}
{$this->comment}
{$this->baz}\n
{$this->function}
{$this->comment}
{$this->bar}";
        $expected = array(
            'Baz' => $this->comment . "\n" . $this->baz . "\n", 
            'Bar' => $this->comment . "\n". $this->bar . "\n" 
            ); 
        $this->splitAndCompare($orig, $expected);
    }

    public function testOverwriteExisting() {
        $orig = "{$this->start}
{$this->function}
{$this->comment}
{$this->baz}\n
{$this->function}
{$this->comment}
{$this->bar}";
        $expected = array(
            'Baz' => $this->comment . "\n" . $this->baz . "\n", 
            'Bar' => $this->comment . "\n". $this->bar . "\n" 
            ); 
        touch($this->outputDir . "/Baz.php");
        $this->splitAndCompare($orig, $expected, true);

    }
    /**
     * @expectedException Exception
     */
    public function testOverwriteExistingThrowsExceptionIfNotaggressive() {
        $orig = "{$this->start}
{$this->function}
{$this->comment}
{$this->baz}\n
{$this->function}
{$this->comment}
{$this->bar}";
        $expected = array(
            'Baz' => $this->comment . "\n" . $this->baz . "\n", 
            'Bar' => $this->comment . "\n". $this->bar . "\n" 
            ); 
        touch($this->outputDir . "/Baz.php");
        $this->splitAndCompare($orig, $expected, false);

    }



  function delTree($dir) {
      if ($dir == "" || $dir == null || $dir == false) {
        throw new Exception("delTree: bad directory path!");
      }

      $files = glob( $dir . '*', GLOB_MARK );
      foreach( $files as $file ){
          if( substr( $file, -1 ) == '/' )
              $this->delTree( $file );
          else
              unlink( $file );
      }
     
      if (is_dir($dir)) rmdir( $dir );
     
  } 

}
