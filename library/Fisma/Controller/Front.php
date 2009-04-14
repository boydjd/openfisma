<?php
class Fisma_Controller_Front extends Zend_Controller_Front
{
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    /**
     * Read configurations of any sections.
     * This function manages the storage, the cache, lazy initializing issue.
     * 
     * @param $key string key name
     * @param $is_fresh boolean to read from persisten storage or not.
     * @return string configuration value.
     */ 
    public static function readSysConfig($key)
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getConfig($key);
    }
    
    public static function getLogInstance()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getLogInstance();
    }
    
    public static function getPath($part)
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->getPath($part);
    }
    
    public static function debug()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->debug();
    }
    
    /**
     * Retrieve the current time
     *
     * @return unix timestamp
     */
    public static function now()
    {
        $setting = self::getInstance();
        return $setting->getPlugin('Fisma_Controller_Plugin_Setting')->now();
    }
    
}
