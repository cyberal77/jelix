<?php
/**
* @package     jelix
* @subpackage  installer
* @author      Laurent Jouanneau
* @copyright   2007-2015 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * an HTML reporter for jInstallChecker
 * @package jelix
 */
class jHtmlInstallChecker implements jIInstallReporter {
    use jInstallerReporterTrait;

    /**
     * @var \jInstallerMessageProvider
     */
    protected $messageProvider;

    function __construct(jInstallerMessageProvider $messageProvider) {
        $this->messageProvider = $messageProvider;
    }

    function start(){
        echo '<ul class="checkresults">';
    }

    function message($message, $type=''){
        $this->addMessageType($type);
        echo '<li class="'.$type.'">'.htmlspecialchars($message).'</li>';
    }
    
    function end(){
        echo '</ul>';

        $nbError = $this->getMessageCounter('error');
        $nbWarning = $this->getMessageCounter('warning');
        $nbNotice = $this->getMessageCounter('notice');

        echo '<div class="results">';
        if ($nbError) {
            echo ' '.$nbError. $this->messageProvider->get( ($nbError > 1?'number.errors':'number.error'));
        }
        if ($nbWarning) {
            echo ' '.$nbWarning. $this->messageProvider->get(($nbWarning > 1?'number.warnings':'number.warning'));
        }
        if ($nbNotice) {
            echo ' '.$nbNotice. $this->messageProvider->get(($nbNotice > 1?'number.notices':'number.notice'));
        }

        if($nbError){
            echo '<p>'.$this->messageProvider->get(($nbError > 1?'conclusion.errors':'conclusion.error')).'</p>';
        }else if($nbWarning){
            echo '<p>'.$this->messageProvider->get(($nbWarning > 1?'conclusion.warnings':'conclusion.warning')).'</p>';
        }else if($nbNotice){
            echo '<p>'.$this->messageProvider->get(($nbNotice > 1?'conclusion.notices':'conclusion.notice')).'</p>';
        }else{
            echo '<p>'.$this->messageProvider->get('conclusion.ok').'</p>';
        }
        echo "</div>";
    }
}
