<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * A base class for implementing command-line tools
 *
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Cli
 */
abstract class Fisma_Cli_Abstract
{
    /**
     * Indicates successful execution.
     *
     * @var int
     */
    const EXIT_SUCCESS = 0;

    /**
     * Indicates failed execution due to bad command line arguments.
     *
     * @var int
     */
    const EXIT_BAD_ARGS = 1;

    /**
     * Indicates failed execution due to unhandled exception.
     *
     * @var int
     */
    const EXIT_UNHANDLED_EXCEPTION = 2;

    /**
     * Command line options
     *
     * @see getOption()
     * @var Zend_Console_Getopt
     */
    private $_cliArguments;

    /**
     * Default command line options
     *
     * This is specified mainly because getopt bombs out if there are no options
     */
    private $_defaultArgumentsDefinitions = array('help|h' => 'Display help');

    /**
     * Subclasses must implement this method to do their work
     *
     * @return void
     */
    abstract protected function _run();

    /**
     * A log object for subclasses to write to.
     *
     * @var Zend_log
     */
    private $_log;

    /**
     * Subclasses may override this method to set their console options
     *
     * @see http://framework.zend.com/manual/en/zend.console.getopt.rules.html
     *
     * @return array An array containing getopt long syntax
     */
    public function getArgumentsDefinitions()
    {
        return array();
    }

    /**
     * Get command line arguments that are no associated with an option flag
     *
     * return @array
     */
    public function getArguments()
    {
        $this->_cliArguments->getRemainingArgs();
    }

    /**
     * Return help text
     */
    public function getHelpText()
    {
        return $this->_cliArguments->getUsageMessage();
    }

    /**
     * Get a command line option by name
     *
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        return $this->_cliArguments->getOption($optionName);
    }

    /**
     * Get a standardized progress bar (with a console driver)
     *
     * @param int $total The total number of items this progress bar represents
     * @return Zend_ProgressBar
     */
    protected function _getProgressBar($total)
    {
        // Progress bar for console progress monitoring
        $progressBar = new Zend_ProgressBar(new Zend_ProgressBar_Adapter_Console, 0, $total);

        $elements = array(
            Zend_ProgressBar_Adapter_Console::ELEMENT_TEXT,
            Zend_ProgressBar_Adapter_Console::ELEMENT_BAR,
            Zend_ProgressBar_Adapter_Console::ELEMENT_PERCENT,
            Zend_ProgressBar_Adapter_Console::ELEMENT_ETA
        );

        $progressBar->getAdapter()->setElements($elements);

        return $progressBar;
    }

    /**
     * A generic run method which handles options and times the length of execution
     *
     * @return int The exit code for this process.
     */
    final public function run()
    {
        $start = time();

        // Get options from the command line
        $argumentsDefinitions = $this->getArgumentsDefinitions();
        $argumentsDefinitions = array_merge($this->_defaultArgumentsDefinitions, $argumentsDefinitions);

        try {
            $this->_cliArguments = new Zend_Console_Getopt($argumentsDefinitions);
            $this->_cliArguments->parse();

            $this->getLog()->debug("Script arguments: " . $this->_cliArguments);

            // If help is requested, then display help text and exit out
            $help = $this->_cliArguments->getOption('h');
            if ($help) {
                $this->getLog()->info($this->getHelpText());
                return self::EXIT_SUCCESS;
            }
        } catch (Zend_Console_Getopt_Exception $e) {
            $this->getLog()->info($e->getUsageMessage());

            if (Fisma::RUN_MODE_TEST != Fisma::mode()) {
                return self::EXIT_BAD_ARGS;
            }
        }

       // Invoke subclass worker method
        try {
            $this->_run();
        } catch (Zend_Config_Exception $zce) {
            // A zend config exception indicates that the application may not be installed properly
            $this->getLog()->err('The application is not installed correctly.');
            $this->getLog()->err($zce);
            return;
        } catch (Exception $e) {
            $this->getLog()->err($e->getMessage());

            // Don't print stack traces for user-level exceptions.
            if (!($e instanceof Fisma_Zend_Exception_User)) {
                $this->getLog()->err($e);
            }

            return self::EXIT_UNHANDLED_EXCEPTION;
        }

        // Calculate elapsed time
        $stop = time();
        $elapsed = $stop - $start;
        $minutes = floor($elapsed / 60);
        $seconds = $elapsed - ($minutes * 60);

        $this->getLog()->info("Finished in $minutes minutes and $seconds seconds.\n");

        return self::EXIT_SUCCESS;
    }

    /**
     * Check InnoDb whether is supported or not in mysql
     *
     * @return boolean
     */
    public static function checkInnoDb()
    {
        $db = Fisma::$appConf['db'];
        $host = $db['host'];
        $user = $db['username'];
        $passward = $db['password'];

        $dbh = new PDO("mysql:host={$host}", $user, $passward);
        $engines = $dbh->query("SHOW ENGINES")->fetchAll();

        $innodb = null;
        foreach ($engines as $engine) {
            if ('innodb' === strtolower($engine['Engine'])) {
                $innodb = $engine;
                break;
            }
        }

        return !empty($innodb) && 'no' !== strtolower($innodb['Support']);
    }

    /**
     * Set the logger used by this command line script.
     *
     * @param Zend_Log $log
     */
    public function setLog(Zend_Log $log)
    {
        $this->_log = $log;
    }

    /**
     * Get the log instance (or create a default one if none exists).
     *
     * @return Zend_Log
     */
    public function getLog()
    {
        if (!$this->_log) {
            // STDOUT shows INFO only in production mode, but shows INFO and DEBUG in debug mode
            $stdoutWriter = new Zend_Log_Writer_Stream("php://stdout");

            if (Fisma::debug()) {
                $stdoutWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::INFO, ">="));
            } else {
                $stdoutWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::INFO, "=="));
            }

            // STDERR shows NOTICE, WARN, ERR, CRIT, ALERT, and EMERG
            $stderrWriter = new Zend_Log_Writer_Stream("php://stderr");
            $stderrWriter->addFilter(new Zend_Log_Filter_Priority(Zend_Log::NOTICE, "<="));

            // The format for STDOUT and STDERR is no frills:
            $formatter = new Zend_Log_Formatter_Simple("%message%\n");
            $stdoutWriter->setFormatter($formatter);
            $stderrWriter->setFormatter($formatter);

            $this->_log = new Zend_Log;
            $this->_log->addWriter($stdoutWriter);
            $this->_log->addWriter($stderrWriter);
        }

        return $this->_log;
    }
}
