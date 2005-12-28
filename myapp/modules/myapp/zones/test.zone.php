<?php
/**
* @package     myapp
* @subpackage  myappmodule
* @version     $Id$
* @author      Jouanneau Laurent
* @contributor
* @copyright   2005-2006 Jouanneau laurent
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

class ZoneTest extends jZone {
   protected $_tplname='testzone';


    protected function _prepareTpl(){

        $dao = jDAO::create('config');
        $users = jDAO::create('user');

        $this->_tpl->assign('config',$dao->findAll());
        $this->_tpl->assign('oneconf',$dao->get('|mailFrom'));
        $this->_tpl->assign('users',$users->findAll());
    }

}

?>