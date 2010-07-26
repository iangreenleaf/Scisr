<?php
/*
 * Copyright (C) 2009, 2010 Ian Young
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Turn on error reporting
error_reporting(E_ALL);
// Register our autoloader
spl_autoload_register(array('Scisr', 'scisrAutoload'));
// Include the main CodeSniffer file (this will register its own autoloader as well)
require_once(dirname(__FILE__) . '/PHP/CodeSniffer.php');

/**
 * The main Scisr controller
 *
 * Coordinates the tasks to take place for the given action(s)
 */
class Scisr
{

    const MODE_TIMID = 0;
    const MODE_CONSERVATIVE = 1;
    const MODE_AGGRESSIVE = 2;

    /**
     * Callbacks to be invoked after the first, read-only pass
     * @var array
     */
    protected $_firstPassCallbacks = array();
    /**
     * CodeSniffer listener objects to be used during the main processing pass
     * @var array
     */
    protected $_listeners = array();
    /**
     * The output object to handle our messages
     * @var Scisr_Output
     */
    protected $_output;
    /**
     * Our codesniffer instance
     * @var Scisr_CodeSniffer
     */
    private $_sniffer;
    /**
     * If we must run a first pass
     * @var boolean
     */
    protected $_firstPassRequired = false;
    /**
     * The edit mode
     * @var int one of MODE_TIMID, MODE_CONSERVATIVE, or MODE_AGGRESSIVE
     */
    private $_mode;

    /**
     * @var Scisr_ChangeRegistry
     */
    private $_changeRegistry;

    /**
     * @var Scisr_Operations_Factory
     */
    private $_operationsFactory;

    /**
     * @var Scisr_Db_Files
     */
    private $_dbFiles;

    /**
     * @var Scisr_Db_FileIncludes
     */
    private $_dbFileIncludes;

    /**
     * @var Scisr_Db_Classes
     */
    private $_dbClasses;

    public function __construct($output=null)
    {
        $this->setEditMode(self::MODE_CONSERVATIVE);
        if ($output === null) {
            $output = new Scisr_Output_Null();
        }
        $this->_output = $output;
        $this->_dbFiles = new Scisr_Db_Files();
        $this->_dbFileIncludes = new Scisr_Db_FileIncludes();
        $this->_dbClasses = new Scisr_Db_Classes();
        $this->_dbVariableTypes = new Scisr_Db_VariableTypes();
        $this->_variableTypes = new Scisr_Operations_VariableTypes($this->_dbClasses, $this->_dbFileIncludes, $this->_dbVariableTypes);
        $this->_changeRegistry = new Scisr_ChangeRegistry();
        $this->_sniffer = new Scisr_CodeSniffer($this->_dbFiles);
        $this->_operationsFactory = new Scisr_Operations_Factory(array(
            $this->_changeRegistry,
            $this->_dbFiles,
            $this->_dbFileIncludes,
            $this->_dbClasses,
            $this->_dbVariableTypes,
            $this->_variableTypes
        ));
    }

    public function getFactory()
    {
        return $this->_operationsFactory;
    }

    /**
     * For testing use only. Dependency injection.
     * @ignore
     * @param Scisr_CodeSniffer
     */
    public function setSniffer($sniffer)
    {
        $this->_sniffer = $sniffer;
    }

    /**
     * Set filename patterns to ignore when parsing
     * @see PHP_CodeSniffer::setIgnorePatterns
     */
    public function setIgnorePatterns($patterns)
    {
        // Just pass it right through to phpcs
        $this->_sniffer->setIgnorePatterns($patterns);
    }

    /**
     * Set allowed file extensions
     * @see PHP_CodeSniffer::setAllowedFileExtensions
     */
    public function setAllowedFileExtensions($extensions)
    {
        // Just pass it right through to phpcs
        $this->_sniffer->setAllowedFileExtensions($extensions);
    }

    /**
     * Get the list of allowed file extensions
     * @return array(string) a list of file extensions
     */
    public function getAllowedFileExtensions()
    {
        return $this->_sniffer->allowedFileExtensions;
    }

    /**
     * Get the listeners to be run on the first (read-only) pass
     */
    protected function getFirstPassListeners()
    {
        $listeners = array();
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackGlobalVariables');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackVariableTypes');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackVariableTypeHints');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackCommentVariableTypes');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackIncludedFiles');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackClasses');
        $listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_TrackReturnStatementTypes');
        $includesOp = $this->_operationsFactory->getOperation('Scisr_Operations_TrackClassIncludes');
        $this->_firstPassCallbacks[] = array(array($includesOp, 'registerIncludes'), array());
        $listeners[] = $includesOp;

        return $listeners;
    }

    /**
     * Rename a class
     * @param string $oldClass the class to be renamed
     * @param string $newClass the new class name to be given
     */
    public function setRenameClass($oldClass, $newClass)
    {
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_RenameClass', $oldClass, $newClass);
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeClassNameComments', $oldClass, $newClass);
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeCommentWords', $oldClass, $newClass);
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeStringWords', $oldClass, $newClass);
    }

    /**
     * Rename a class method
     * @param string $class the class that contains the method to be renamed
     * @param string $oldMethod the method to be renamed
     * @param string $newMethod the new method name
     * @param boolean $withInheritance if true, also rename method on child classes
     */
    public function setRenameMethod($class, $oldMethod, $newMethod, $withInheritance)
    {
        if ($withInheritance) {
            $this->_firstPassCallbacks[] = array(array($this, 'doRenameChildMethods'), array($class, $oldMethod, $newMethod));
        }
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_RenameMethod', $class, $oldMethod, $newMethod);

        // Look for matches in comments and strings
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeCommentWords', $oldMethod, $newMethod);
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeStringWords', $oldMethod, $newMethod);
        $fullOldString = "$class(->|::)$oldMethod";
        $fullNewString = "$class\\1$newMethod";
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeCommentWords', $fullOldString, $fullNewString, false);
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_ChangeStringWords', $fullOldString, $fullNewString, false);

        $this->_firstPassRequired = true;
    }

    /**
     * Callback that uses the class information from the first pass to rename 
     * methods on child classes.
     */
    protected function doRenameChildMethods($class, $oldMethod, $newMethod)
    {
        $classes = $this->_dbClasses->getChildClasses($class);
        foreach ($classes as $child) {
            $this->setRenameMethod($child, $oldMethod, $newMethod, false);
        }
    }

    /**
     * Rename a file
     * @param string $oldFilePath the path to the file to be renamed
     * @param string $newFilePath the new path to give it
     */
    public function setRenameFile($oldFilePath, $newFilePath)
    {
        $this->_listeners[] = $this->_operationsFactory->getOperation('Scisr_Operations_RenameFile', $oldFilePath, $newFilePath);
        if (!file_exists($oldFilePath)) {
            $msg = 'does not exist, so will not be moved.';
        } else if (!is_writeable($oldFilePath)) {
            $msg = 'could not be moved.';
        } else {
            $this->_changeRegistry->addRename($oldFilePath, $newFilePath);
        }

        if (isset($msg)) {
            $msg = "File \"$oldFilePath\" " . $msg;
            $this->sendOutput($msg);
        }
    }

    /**
     * Rename a class, renaming the file as well if possible
     * @param string $oldClass the class to be renamed
     * @param string $newClass the new class name to be given
     */
    public function setRenameClassFile($oldClass, $newClass)
    {
        $this->_firstPassCallbacks[] = array(array($this, 'doRenameClassFile'), array($oldClass, $newClass));
        $this->setRenameClass($oldClass, $newClass);
        $this->_firstPassRequired = true;
    }

    /**
     * Callback that uses the class information from the first pass to figure 
     * out how to rename class files.
     */
    protected function doRenameClassFile($oldClass, $newClass)
    {
        $oldFilePath = $this->_dbClasses->getClassFile($oldClass);
        if ($oldFilePath !== null) {
            $pieces = explode('_', $oldClass);
            foreach (array_keys($this->getAllowedFileExtensions()) as $ext) {
                if (basename($oldFilePath) == "$oldClass.$ext") {
                    $dir = dirname($oldFilePath);
                    $this->setRenameFile($oldFilePath, "$dir/$newClass.$ext");
                    break;
                } else if (count($pieces) > 1) {
                    $namespacedFile = implode('/', $pieces) . ".$ext";
                    $baseDir = Scisr_Operations_RenameFile::matchPaths($oldFilePath, $namespacedFile);
                    if ($baseDir !== false) {
                        $newNamespacedFile = implode('/', explode('_', $newClass)) . ".$ext";
                        $this->setRenameFile($oldFilePath, "$baseDir$newNamespacedFile");
                        break;
                    }
                }
            }
        }
    }

    /**
     * Add a file or directory to be parsed
     * @param string $filename the path to the file or directory
     */
    public function addFile($filename)
    {
        $filename = Scisr_File::getAbsolutePath($filename);
        $this->files[] = $filename;
    }

    /**
     * Add multiple files or directories to be parsed
     * @param array an array of file or directory paths
     */
    public function addFiles($fileArray)
    {
        array_map(array($this, 'addFile'), $fileArray);
    }

    /**
     * Set how destructive we are editing
     * @param int one of the following class constants:
     *   + MODE_TIMID: Make no changes to the files, just report possible changes
     *   + MODE_CONSERVATIVE: Make changes we are relatively sure are correct.
     *     Warn about possible changes we aren't sure about.
     *   + MODE_AGGRESSIVE: Make any changes we find.
     */
    public function setEditMode($mode)
    {
        $this->_mode = $mode;
    }

    /**
     * Perform the requested changes
     */
    public function run()
    {
        $this->_dbVariableTypes->init();
        $this->_dbFileIncludes->init();
        $this->_dbFiles->init();
        $this->_dbClasses->init();

        $sniffer = $this->_sniffer;

        // If we need to, make a read-only pass to populate our type information
        if ($this->_firstPassRequired) {
            foreach ($this->getFirstPassListeners() as $listener) {
                $sniffer->addListener($listener);
            }
            $sniffer->process($this->files, false, true);
        }

        // Now call any callbacks registered to run after the first pass
        foreach ($this->_firstPassCallbacks as $cbArray) {
            $callback = $cbArray[0];
            $args = $cbArray[1];
            call_user_func_array($callback, $args);
        }

        // Clear out the first pass listeners before we run the second pass
        $sniffer->clearListeners();
        foreach ($this->_listeners as $listener) {
            $sniffer->addListener($listener);
        }
        // Run the sniffer
        $sniffer->process($this->files);

        // Get the changes that have been registered
        $changes = $this->_changeRegistry->get('storedChanges');
        if (!is_array($changes)) {
            $changes = array();
        }

        // Now make the actual changes
        $filesChanged = 0;
        $totalWarnings = 0;
        $warnings = array();
        foreach ($changes as $file) {
            $changed = $file->process($this->_mode);
            $filesChanged += ($changed ? 1 : 0);
            $numWarnings = $file->getNumChangesNotProcessed();
            if ($numWarnings > 0) {
                $totalWarnings += $numWarnings;
                $warnings[$file->filename] = $file->getLinesNotProcessed();
            }
        }

        // Display a summary line
        $msg = "Changed $filesChanged files";
        $this->sendOutput($msg);

        // If we have any notifications, display them
        if ($totalWarnings > 0) {
            // Display a summary
            $numFiles = count($warnings);
            $msg = "Found $totalWarnings possible changes in $numFiles files that were not applied:";
            $this->sendOutput($msg);
            // Now display each line where we found changes
            foreach ($warnings as $filename => $lines) {
                foreach ($lines as $lineNo) {
                    $this->sendOutput("$filename:$lineNo");
                }
            }
        }
    }

    /**
     * Send output to the user.
     * @param string $message the message to send
     */
    public function sendOutput($message)
    {
        $this->_output->outputString($message);
    }

    /**
     * An autoload function for Scisr
     * @param string
     */
    public static function scisrAutoload($className)
    {
        if (strpos($className, 'Scisr_') === 0) {
            $className = substr($className, 6);
            $path = str_replace('_', '/', $className).'.php';
            if (is_file(dirname(__FILE__).'/'.$path) === true) {
                include dirname(__FILE__).'/'.$path;
            }
        }
    }

}

