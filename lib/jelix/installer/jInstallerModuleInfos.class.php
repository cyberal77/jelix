<?php
/**
* @package     jelix
* @subpackage  installer
* @author      Laurent Jouanneau
* @copyright   2009-2018 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * container for module properties, according to a specific entry point configuration
 */
class jInstallerModuleInfos {
    /**
     * @var string
     */
    public $name;
    /**
     * the access level of the module for the entry point
     * @var integer
     */
    public $access;
    /**
     * @var string
     */
    public $dbProfile;

    /**
     * indicate if the module is marked as installed for the entry point
     * @var boolean  true/false or 0/1
     */
    public $isInstalled;

    /**
     * The version of the module that has been installed.
     * @var string
     */
    public $version;

    /**
     * @var string[] parameters for installation
     */
    public $parameters = array();

    public $skipInstaller = false;


    const CONFIG_SCOPE_APP = 0;
    const CONFIG_SCOPE_LOCAL = 1;

    public $configurationScope = 0;


    protected $path;

    /**
     * @param string $name the name of the module
     * @param string $path the path to the module
     * @param array $config  configuration of modules ([modules] section),
     *                       generated by the configuration compiler for a specific
     *                       entry point
     */
    function __construct($name, $path, $config) {
        $this->name = $name;
        $this->path = $path;
        $this->access = $config[$name.'.access'];
        $this->dbProfile = $config[$name.'.dbprofile'];
        $this->isInstalled = $config[$name.'.installed'];
        $this->version = $config[$name.'.version'];

        if (isset($config[$name.'.installparam'])) {
            $this->parameters = self::unserializeParameters($config[$name.'.installparam']);
        }

        if (isset($config[$name.'.skipinstaller']) &&  $config[$name.'.skipinstaller'] == 'skip') {
            $this->skipInstaller = true;
        }

        if (isset($config[$name.'.localconf'])) {
            $this->configurationScope = ($config[$name.'.localconf']?self::CONFIG_SCOPE_LOCAL: self::CONFIG_SCOPE_APP);
        }

    }

    function getPath() {
        return $this->path;
    }

    function getName() {
        return $this->name;
    }

    function saveInfos(\Jelix\IniFile\IniModifier $configIni) {
        $previous = $configIni->getValue($this->name.'.access', 'modules');
        if ($previous !== $this->access) {
            $configIni->setValue($this->name.'.access', $this->access, 'modules');
        }

        $this->setConfigInfo($configIni, 'dbprofile', ($this->dbProfile != 'default'? $this->dbProfile: ''));
        $this->setConfigInfo($configIni, 'installparam', self::serializeParameters($this->parameters));
        $this->setConfigInfo($configIni, 'skipinstaller', ($this->skipInstaller?'skip':''));
        $this->setConfigInfo($configIni, 'localconf',
            ($this->configurationScope == self::CONFIG_SCOPE_LOCAL?self::CONFIG_SCOPE_LOCAL:0));
    }

    /**
     * @param \Jelix\IniFile\IniModifier $configIni
     * @param string $name
     * @param mixed $value
     */
    private function setConfigInfo($configIni, $name, $value) {
        // only modify the file when the value is not already set
        // to avoid to have to save the ini file  #perfs
        $previous = $configIni->getValue($this->name.'.'.$name, 'modules');
        if ($value) {
            if ($previous != $value) {
                $configIni->setValue($this->name.'.'.$name, $value, 'modules');
            }
        }
        else if ($previous) {
            $configIni->removeValue($this->name.'.'.$name, 'modules');
        }
    }

    function clearInfos(\Jelix\IniFile\IniModifier $configIni) {
        foreach(array('access', 'dbprofile', 'installparam', 'skipinstaller', 'localconf',) as $param) {
            $configIni->removeValue($this->name.'.'.$param, 'modules');
        }
    }


    static function unserializeParameters($parameters) {
        $trueParams = array();
        $params = explode(';', $parameters);
        foreach($params as $param) {
            $kp = explode("=", $param);
            if (count($kp) > 1) {
                $v = $kp[1];
                if (strpos($v, ',') !== false) {
                    $trueParams[$kp[0]] = explode(',', $v);
                }
                else {
                    $trueParams[$kp[0]] = $v;
                }
            } else {
                $trueParams[$kp[0]] = true;
            }
        }
        return $trueParams;
    }

    function getSerializedParameters() {
        return self::unserializeParameters($this->parameters);
    }

    static function serializeParameters($parameters) {
        $p = [];
        foreach($parameters as $name=>$v) {
            if (is_array($v)) {
                if (!count($v)) {
                    continue;
                }
                $v = implode(',', $v);
            }
            if ($v === true || $v === '') {
                $p[] = $name;
            }
            else {
                $p[] = $name . '=' . $v;
            }
        }
        return implode(';', $p);
    }
}
