<?php
/**
* @package    jelix
* @subpackage db
* @version    $Id:$
* @author     Croes G�rald, Laurent Jouanneau
* @contributor Laurent Jouanneau
* @copyright  2001-2005 CopixTeam, 2005-2006 Laurent Jouanneau
* @link      http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*
* Classe orginellement issue du framework Copix 2.3dev20050901. http://www.copix.org (CopixDbToolsMysql)
* Une partie du code est sous Copyright 2001-2005 CopixTeam (licence LGPL)
* Auteurs initiaux : Gerald Croes et Laurent Jouanneau
* Adapt�e pour Jelix par Laurent Jouanneau
*/

/**
 * classe d'outils pour g�rer une base de donn�es
 */
class jDbToolsMySQL extends jDbTools {
   function __construct($connector){
      parent::__construct($connector);
   }

   /**
   * retourne la liste des tables
   * @return   array    $tab[] = $nomDeTable
   */
   function _getTableList (){
      $results = array ();

      $rs = $this->_connector->doQuery ('SHOW TABLES FROM '.$this->_connector->profil['database']);
      $col_name = 'Tables_in_'.$this->_connector->profil['database'];

      while ($line = $rs->fetch ()){
         $results[] = $line->$col_name;
      }

      return $results;
   }

   /**
   * r�cup�re la liste des champs pour une base donn�e.
   * @return   array    $tab[NomDuChamp] = obj avec prop (tye, length, lengthVar, notnull)
   */
   function _getFieldList ($tableName){
      $results = array ();

      $sql_get_fields = 'SHOW FIELDS FROM ' . $tableName;
      $rs = $this->_connector->doQuery ($sql_get_fields);

      while ($result_line = $rs->fetch ()){
         $p_result_line->type      = $result_line->Type;
         $type = $result_line->Type;

          /**
          * r�cup�r� depuis phpMyAdmin
          */
          // set or enum types: slashes single quotes inside options
          $type   = str_replace('BINARY', '', $type);
          $type   = str_replace('ZEROFILL', '', $type);
          $type   = str_replace('UNSIGNED', '', $type);
          /*
          if (eregi('^(set|enum)\((.+)\)$', $type, $tmp)){
              $type   = $tmp[1];
              $length = substr(ereg_replace('([^,])\'\'', '\\1\\\'', ',' . $tmp[2]), 1);
          }else{
              $length = $type;
              $type   = chop(eregi_replace('\\(.*\\)', '', $type));
              if (!empty($type)){
                  $length = eregi_replace("^$type\(", '', $length);
                  $length = eregi_replace('\)$', '', trim($length));
              }
              if ($length == $type){
                  $length = '';
              }
          }*/

          preg_match('/^(\w+).*$/',$type,$m);


          $p_result_line->type      = $m[1];
          //$p_result_line->length    = $length;
          $p_result_line->notnull   = (trim ($result_line->Null) != 'YES');
          $p_result_line->primary  = (trim ($result_line->Key) == 'PRI');
          $p_result_line->auto_increment  = ($result_line->Extra == 'auto_increment');
          $results[$result_line->Field] = $p_result_line;
      }
      return $results;
   }
}
?>