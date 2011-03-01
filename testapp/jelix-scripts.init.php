<?php
/**
* @package  jelix
* @subpackage testapp
* @author   Laurent Jouanneau
* @contributor
* @copyright 2009-2011 Laurent Jouanneau
* @link     http://jelix.org
* @licence  http://www.gnu.org/licenses/gpl.html GNU General Public Licence, see LICENCE file
*/

$appPath = dirname (__FILE__).'/';
require ($appPath.'../lib/jelix/init.php');

jApp::initPaths(
    $appPath
    //$appPath.'www/',
    //$appPath.'var/',
    //$appPath.'var/log/',
    //$appPath.'var/config/',
    //$appPath.'scripts/'
);
jApp::setTempBasePath(realpath($appPath.'../temp/testapp-scripts/').'/');

define ('JELIX_APP_TEMP_CLI_PATH',    realpath($appPath.'../temp/testapp-cli/').'/');

define ('JELIX_APP_REAL_TEMP_PATH',    realpath($appPath.'../temp/testapp/').'/');
