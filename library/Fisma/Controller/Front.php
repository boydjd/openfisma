<?php
class Fisma_Controller_Front extends Zend_Controller_Front
{
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        $flag = self::$_instance->hasPlugin('Fisma_Controller_Plugin_Setting');
        if (!$flag) {
            $plSetting = new Fisma_Controller_Plugin_Setting();
            self::$_instance->registerPlugin($plSetting, 60);
        }
        return self::$_instance;
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
