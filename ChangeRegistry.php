<?php
/**
 * A registry to track potential changes to files we have parsed
 *
 * This could extend Zend_Registry or something similar if we desired.
 */
class Scisr_ChangeRegistry
{

    /**
     * Stores the raw data we've been given
     * @var array
     */
    private $_data;

    /**
     * Set a value
     * @param string $name the name of the value
     * @param mixed $value the value
     */
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Get a value
     * @param string $name the name of the value
     * @return mixed the value stored for this name
     */
    public function get($name)
    {
        return (isset($this->_data[$name]) ? $this->_data[$name] : null);
    }

    /**
     * Clear all data stored in the registry
     */
    public static function clearAll()
    {
    }

    /**
     * Set a potential change to a file
     * @param string $filename the filename
     * @param int $line the line number that our change begins on
     * @param int $column the column number that our change begins at
     * @param int $length the length of the original text to be replaced
     * @param string $replacement the text to insert in its place
     * @param boolean $tentative true if this change is something we "aren't
     * sure about" - for example, a word match found in a string. Changes marked 
     * tentative will only be acted upon if we are in "aggressive" mode.
     */
    public function addChange($filename, $line, $column, $length, $replacement, $tentative=false)
    {
        $file = $this->getFile($filename);
        $file->addEdit($line, $column, $length, $replacement, $tentative);
        $this->setFile($file);
    }
    /**
     * Create a new file.
     * @param string $filename the filename
     * @param string $content file content
     * @param boolean $tentative true if we want to overwrite the file
     */
    public function createFile($filename, $content, $tentative = false) {
      $file = new Scisr_CreateFile($filename, $content);
      $this->setFile($file);
    
    }

    /**
     * Get the stored file object for a given filename
     * @param string $filename the filename
     * @return Scisr_File
     */
    protected function getFile($filename)
    {
        $changes = $this->getChanges();
        // We just store our pending changes as file objects themselves. If one
        // doesn't exist yet for this file, create it
        if (!isset($changes[$filename])) {
            $changes[$filename] = new Scisr_File($filename);
        }
        return $changes[$filename];
    }

    /**
     * Save the stored file object
     * @param Scisr_File the file to save
     */
    protected function setFile($file)
    {
        $changes = $this->getChanges();
        $changes[$file->filename] = $file;
        $this->set('storedChanges', $changes);
    }

    /**
     * Get stored file changes
     * @return array(Scisr_File)
     */
    private  function getChanges()
    {
        $changes = $this->get('storedChanges');
        if (!is_array($changes)) {
            $changes = array();
        }
        return $changes;
    }

    /**
     * Set a file to be renamed
     * @param string $oldName the path to the file to be renamed
     * @param string $newName the new path to give it
     */
    public function addRename($oldName, $newName)
    {
        $file = $this->getFile($oldName);
        $file->rename($newName);
        $this->setFile($file);
    }

}
