<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Fisma_Zend_Application_Bootstrap_SymfonyContainerBootstrap 
 * 
 * @uses Zend_Application_Bootstrap_Bootstrap
 * @package Fisma_Zend_Application_Bootstrap 
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Application_Bootstrap_SymfonyContainerBootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    /**
     *  
     */
    protected static $_registryIndex = 'container';

    /**
     * _doCache 
     * 
     * @var mixed
     */
    protected $_doCache;

    /**
     * _cacheExists 
     * 
     * @var mixed
     */
    protected $_cacheExists;

    /**
     * _cacheFile 
     * 
     * @var mixed
     */
    protected $_cacheFile;

    /**
     * run 
     * 
     * @access public
     * @return void
     */
    public function run()
    {
        // Load service container if no cached or if we want to cache and cache doesn't esist
        if (!$this->_doCache() || ($this->_doCache() && !$this->_cacheExists())) {
            $this->_loadContainer();
        }
        // Cache loaded service container if we want to cache and cache doesn't already exist
        if ($this->_doCache() && !$this->_cacheExists()) {
            $this->_cacheContainer();
        }
        parent::run();
    }

    /**
     * getContainer 
     * 
     * @return sfServiceContainer 
     */
    public function getContainer()
    {
        $options = $this->getOption('bootstrap');

        if (null === $this->_container && $options['container']['type'] == 'symfony') {
            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->pushAutoloader(array('Fisma_Symfony_Components_Autoloader', 'autoload'));

            if ($this->_doCache() && $this->_cacheExists()) {
                $cacheFile = $this->_getCacheFile();
                $cacheName = pathinfo($cacheFile, PATHINFO_FILENAME);
                require_once $cacheFile;
                $container = new $cacheName();
            } else {
                $container = new sfServiceContainerBuilder();
            }

            $this->_container = $container;
            Zend_Registry::set(self::getRegistryIndex(), $container);
            Zend_Controller_Action_HelperBroker::addHelper(
                new Fisma_Zend_Controller_Action_Helper_DependencyInjection()
            );
        }
        return parent::getContainer();
    }

    /**
     * _doCache 
     * 
     * @return boolean 
     */
    protected function _doCache()
    {
        if (null === $this->_doCache) {
            $options = $this->getOption('bootstrap');
            $sfContainerOptions = isset($options['container']['symfony']) ? $options['container']['symfony'] : array();
            $this->_doCache = isset($sfContainerOptions['cache']) ? (bool) $sfContainerOptions['cache'] : false;
        }
        return $this->_doCache;
    }

    /**
     * _cacheExists 
     * 
     * @return boolean 
     */
    protected function _cacheExists()
    {
        if (null === $this->_cacheExists) {
            $cacheFile = $this->_getCacheFile();
            $this->_cacheExists = file_exists($cacheFile);
        }
        return $this->_cacheExists;
    }

    /**
     * _getCacheFile 
     * 
     * @return string 
     */
    protected function _getCacheFile()
    {
        if (null === $this->_cacheFile) {
            $options = $this->getOption('bootstrap');
            $sfContainerOptions = isset($options['container']['symfony']) ? $options['container']['symfony'] : array();
            if (isset($sfContainerOptions['cacheFile'])) {
                $cacheFile = $sfContainerOptions['cacheFile'];
            } else {
                $cacheFile = sys_get_temp_dir() . '/ServiceContainer.php';
            }

            $this->_cacheFile = $cacheFile;
        }
        return $this->_cacheFile;
    }

    /**
     * _loadContainer 
     * 
     * @return void
     */
    protected function _loadContainer()
    {
        $options = $this->getOption('bootstrap');
        $sfContainerOptions = isset($options['container']['symfony']) ? $options['container']['symfony'] : array();

        // Load configuration files
        if (isset($sfContainerOptions['configFiles'])) {
            foreach ($sfContainerOptions['configFiles'] as $file) {
                $this->_loadConfigFile($file);
            }
        }
        // Load configuration paths for annotated classes
        if (isset($sfContainerOptions['configPaths'])) {
            foreach ($sfContainerOptions['configPaths'] as $path) {
                $this->_loadPath($path);
            }
        }

        // Load controllers into service container
        $loader = new Fisma_Symfony_Components_ServiceContainerLoaderZendController($this->getContainer());
        $front = $this->getResource('FrontController');
        $controllerDirectories = $front->getControllerDirectory();
        foreach ($controllerDirectories as $controllerDirectory) {
            $loader->load($controllerDirectory);
        }
    }

    /**
     * _cacheContainer 
     * 
     * @return void
     */
    protected function _cacheContainer()
    {
        $cacheFile = $this->_getCacheFile();
        $cacheName = pathinfo($cacheFile, PATHINFO_FILENAME);
        $dumper = new sfServiceContainerDumperPhp($this->getContainer());
        file_put_contents($cacheFile, $dumper->dump(array('class' => $cacheName)));
    }

    /**
     * _loadConfigFile 
     * 
     * @param mixed $file 
     * @return void
     */
    protected function _loadConfigFile($file)
    {
        $container = $this->getContainer();
        $suffix = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($suffix) {
            case 'xml':
                $loader = new sfServiceContainerLoaderFileXml($container);
                break;

            case 'yml':
                $loader = new sfServiceContainerLoaderFileYaml($container);
                break;

            case 'ini':
                $loader = new sfServiceContainerLoaderFileIni($container);
                break;

            default:
                throw new Fisma_Symfony_Exception("Invalid configuration file provided; unknown config type '$suffix'");
        }
        $loader->load($file);
    }

    /**
     * _loadPath 
     * 
     * @param mixed $path 
     * @return void
     */
    protected function _loadPath($path)
    {
        $loader = new Fisma_Symfony_Components_ServiceContainerLoaderAnnotations($this->getContainer());
        $loader->load($path);
    }

    /**
     * getRegistryIndex 
     * 
     * @return void
     */
    public static function getRegistryIndex()
    {
        return self::$_registryIndex;
    }

    /**
     * setRegistryIndex 
     * 
     * @param mixed $registryIndex 
     * @return void
     */
    public static function setRegistryIndex($registryIndex)
    {
        self::$_registryIndex = $registryIndex;
        return $this;
    }
}
