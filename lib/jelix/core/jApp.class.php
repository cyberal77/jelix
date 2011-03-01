<?php
/**
* @package    jelix
* @subpackage core
* @author     Laurent Jouanneau
* @copyright  2011 Laurent Jouanneau
* @link       http://jelix.org
* @licence    http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
* 
* @package    jelix
* @subpackage utils
*/
class jApp {

    protected static $tempBasePath = '';

    protected static $tempPath = '';

    protected static $appPath = '';

    protected static $varPath = '';

    protected static $logPath = '';

    protected static $configPath = '';

    protected static $wwwPath = '';

    protected static $scriptPath = '';

    protected static $_isInit = false;

    /**
     * initialize the application paths
     * @param string $appPath  application directory
     * @param string $wwwPath  www directory
     * @param string $varPath  var directory
     * @param string $logPath log directory
     * @param string $configPath config directory
     * @param string $scriptPath scripts directory
     */
    public static function initPaths ($appPath,
                                 $wwwPath = null,
                                 $varPath = null,
                                 $logPath = null,
                                 $configPath = null,
                                 $scriptPath = null
                                 ) {
        self::$appPath = $appPath;
        self::$wwwPath = (is_null($wwwPath)?$appPath.'www/':$wwwPath);
        self::$varPath = (is_null($varPath)?$appPath.'var/':$varPath);
        self::$logPath = (is_null($logPath)?self::$varPath.'log/':$logPath);
        self::$configPath = (is_null($configPath)?self::$varPath.'config/':$configPath);
        self::$scriptPath = (is_null($scriptPath)?$appPath.'scripts/':$scriptPath);
        self::$_isInit = true;
    }

    /**
     * indicate if path have been set
     * @return boolean  true if it is ok
     */
    public static function isInit() { return self::$_isInit; }

    /**
     * init path from JELIX_APP_* defines or define JELIX_APP_*,
     * depending of how the bootstrap has been initialized.
     * The goal of this method is to support the transition
     * between the old way of defining path, and the new way
     * in jelix 1.3.
     * @deprecated
     */
    public static function initLegacy() {
        if (self::$_isInit) {
            if (!defined('JELIX_APP_PATH')) {
                define ('JELIX_APP_TEMP_PATH',    self::tempPath());
                define ('JELIX_APP_VAR_PATH',     self::$varPath);
                define ('JELIX_APP_LOG_PATH',     self::$logPath);
                define ('JELIX_APP_CONFIG_PATH',  self::$configPath);
                define ('JELIX_APP_WWW_PATH',     self::$wwwPath);
                define ('JELIX_APP_CMD_PATH',     self::$scriptPath);
            }
        }
        else if (defined('JELIX_APP_PATH')) {
            self::initPaths(JELIX_APP_PATH,
                            JELIX_APP_WWW_PATH,
                            JELIX_APP_VAR_PATH,
                            JELIX_APP_LOG_PATH,
                            JELIX_APP_CONFIG_PATH,
                            JELIX_APP_CMD_PATH);
            self::setTempBasePath(JELIX_APP_TEMP_PATH);
        }
    }

    public static function appPath($file='') { return self::$appPath.$file; }
    public static function varPath($file='') { return self::$varPath.$file; }
    public static function logPath($file='') { return self::$logPath.$file; }
    public static function configPath($file='') { return self::$configPath.$file; }
    public static function wwwPath($file='') { return self::$wwwPath.$file; }
    public static function scriptsPath($file='') { return self::$scriptPath.$file; }
    public static function tempPath($file='') { return self::$tempBasePath.$file; }

    public static function setTempBasePath($path) {
        self::$tempBasePath = $path;
    }

    /**
     * load a plugin from a plugin directory (any type of plugins)
     * @param string $name the name of the plugin
     * @param string $type the type of the plugin
     * @param string $suffix the suffix of the filename
     * @param string $classname the name of the class to instancy
     * @param mixed $args  the argument for the constructor of the class. null = no argument.
     * @return null|object  null if the plugin doesn't exists
     */
    public static function loadPlugin($name, $type, $suffix, $classname, $args = null) {

        if (!class_exists($classname,false)) {
            global $gJConfig;
            $optname = '_pluginsPathList_'.$type;
            if (!isset($gJConfig->$optname))
                return null;
            $opt = & $gJConfig->$optname;
#ifnot ENABLE_OPTIMIZED_SOURCE
            if (!isset($opt[$name])
                || !file_exists($opt[$name]) ){
                return null;
            }
#endif
            require_once($opt[$name].$name.$suffix);
        }
        if (!is_null($args))
            return new $classname($args);
        else
            return new $classname();
    }
}
