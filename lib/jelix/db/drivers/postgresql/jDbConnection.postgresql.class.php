<?php
/**
* @package    jelix
* @subpackage db
* @author     Croes G�rald, Laurent Jouanneau
* @contributor Laurent Jouanneau
* @contributor Yannick Le Gu�dart
* @copyright  2001-2005 CopixTeam, 2005-2007 Laurent Jouanneau
* Classe orginellement issue du framework Copix 2.3dev20050901. http://www.copix.org (CopixDBConnectionPostgreSQL)
* Une partie du code est sous Copyright 2001-2005 CopixTeam (licence LGPL)
* Auteurs initiaux : Gerald Croes et Laurent Jouanneau
* Adapt�e et am�lior�e pour Jelix par Laurent Jouanneau
* @link        http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 *
 * @package    jelix
 * @subpackage db
 */
class jDbConnectionPostgreSQL extends jDbConnection {
    protected $_charsets =array( 'UTF-8'=>'UNICODE', 'ISO-8859-1'=>'LATIN1');

    function __construct($profil){
        if(!function_exists('pg_connect')){
            throw new JException('jelix~db.error.nofunction','posgresql');
        }
        parent::__construct($profil);
    }

    public function beginTransaction (){
        return $this->_doExec('BEGIN');
    }

    public function commit (){
        return $this->_doExec('COMMIT');
    }

    public function rollback (){
        return $this->_doExec('ROLLBACK');
    }

    public function prepare ($query){
        $id=(string)mktime();
        $res = pg_prepare($this->_connection, $id, $query);
        if($res){
            $rs= new jDbResultSetPostgreSQL ($res, $id, $this->_connection );
        }else{
            throw new jException('jelix~db.error.query.bad',  pg_last_error($this->_connection).'('.$query.')');
        }
        return $rs;
    }

    public function errorInfo(){
        return array( 'HY000' ,pg_last_error($this->_connection), pg_last_error($this->_connection));
    }

    public function errorCode(){
        return pg_last_error($this->_connection);
    }

    protected function _connect (){
        $funcconnect= ($this->profil['persistent'] ? 'pg_pconnect':'pg_connect');

        $str = 'dbname='.$this->profil['database'].' user='.$this->profil['user'].' password='.$this->profil['password'];

        // on fait une distinction car si host indiqu� -> connection TCP/IP, sinon socket unix
        if($this->profil['host'] != '')
            $str = 'host='.$this->profil['host'].' '.$str;

        // Si le port est d�fini on le rajoute � la chaine de connexion
        if (isset($this->profil['port'])) {
            $str .= ' port='.$this->profil['port'];
        }

        if($cnx=@$funcconnect ($str)){
            if(isset($this->profil['force_encoding']) && $this->profil['force_encoding'] == true
               && isset($this->_charsets[$GLOBALS['gJConfig']->defaultCharset])){
                pg_set_client_encoding($cnx, $this->_charsets[$GLOBALS['gJConfig']->defaultCharset]);
            }
            return $cnx;
        }else{
            throw new jException('jelix~db.error.connection',$this->profil['host']);
        }
    }

    protected function _disconnect (){
        return pg_close ($this->_connection);
    }

    protected function _doQuery ($queryString){
        if ($qI = pg_query ($this->_connection, $queryString)){
            $rs= new jDbResultSetPostgreSQL ($qI);
            $rs->_connector = $this;
        }else{
            $rs = false;
            throw new jException('jelix~db.error.query.bad',  pg_last_error($this->_connection).'('.$queryString.')');
        }
        return $rs;
    }

    protected function _doExec($query){
        if($rs = $this->_doQuery($query)){
            return pg_affected_rows($rs->id());
        }else
            return 0;
    }

    protected function _doLimitQuery ($queryString, $offset, $number){
        if($number < 0)
            $number='ALL';
        $queryString.= ' LIMIT '.$number.' OFFSET '.$offset;
        $result = $this->_doQuery($queryString);
        return $result;
    }




    public function lastInsertId($seqname=''){

        if($seqname == ''){
            trigger_error(get_class($this).'::lastInstertId invalide sequence name',E_USER_WARNING);
            return false;
        }
        $cur=$this->query("select currval('$seqname') as id");
        if($cur){
            $res=$cur->fetch();
            if($res)
                return $res->id;
            else
                return false;
        }else{
            trigger_error(get_class($this).'::lastInstertId invalide sequence name',E_USER_WARNING);
            return false;
        }
    }

    protected function _autoCommitNotify ($state){

        $this->query ('SET AUTOCOMMIT='.$state ? 'on' : 'off');
    }

    protected function _quote($text){
        return pg_escape_string($text);
    }
}
?>
