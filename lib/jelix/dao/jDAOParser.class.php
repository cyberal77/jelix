<?php
/**
* @package    jelix
* @subpackage dao
* @version    $Id:$
* @author     Croes G�rald, Laurent Jouanneau
* @contributor Laurent Jouanneau
* @copyright  2001-2005 CopixTeam, 2005-2006 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*
* Une partie du code est issue de la classe CopixDAODefinitionV1
* du framework Copix 2.3dev20050901. http://www.copix.org
* il est sous Copyright 2001-2005 CopixTeam (licence LGPL)
* Auteurs initiaux : Gerald Croes et Laurent Jouanneau
* Adapt�e et am�lior�e pour Jelix par Laurent Jouanneau
*/

/**
* Analyse un fichier xml de dao
*/
class jDAOParser {
    /**
    * the properties list.
    * keys = field code name
    * values = jDAOProperty
    */
    private $_properties = array ();

    /**
    * all tables with their properties, and their own fields
    * keys = table code name
    * values = array()
    *          'name'=> table code name, 'tablename'=>'real table name', 'JOIN'=>'join type',
    *          'primary'=>'bool', 'fields'=>array(list of field code name)
    */
    private $_tables = array();

    /**
    * primary table code name
    */
    private $_primaryTable = '';

    /**
    * liste des jointures, entre toutes les tables
    * values = array('join'=>'type jointure', 'left'=>'table name', 'right'=>'table name',
    *               'leftfield'=>'real field name', 'rightfield'=>'real field name');
    */
    private $_ojoins = array ();
    private $_ijoins = array ();


    private $_methods = array();


    public $_compiler;

    /**
    * Constructor
    * @param jDAOCompiler compiler the compiler object
    */
    function __construct($compiler){
        $this->_compiler= $compiler;
    }

    /**
    * loads an XML file if given.
    */
    public function parse( $xml){
        // -- tables
        if(isset ($xml->datasources) && isset ($xml->datasources[0]->table)){
            $t = $this->_parseTable ($xml->datasources[0]->table[0]);
            $this->_primaryTable = $t['name'];
            if(isset($xml->datasources[0]->table[1])){
               $this->_compiler->doDefError ('table.two.many');
            }
        }else{
            $this->_compiler->doDefError ('datasource.missing');
        }

        //add the record properties
        if(isset($xml->record) && isset($xml->record[0]->property)){
            foreach ($xml->record[0]->property as $prop){
               $p = new jDAOProperty ($prop->attributes(), $this);
               $this->_properties[$p->name] = $p;
               $this->_tables[$p->table]['fields'][] = $p->name;
            }
        }else
           $this->_compiler->doDefError ('properties.missing');

        // get additionnal methods definition
        if(isset ($xml->methods) && isset ($xml->methods[0]->method)){
           foreach($xml->methods[0]->method as $method){
               $m = new jDAOMethod ($method, $this);
               if(isset ($this->_methods[$m->name])){
                  $this->_compiler->doDefError ('method.duplicate',$m->name);
               }
               $this->_methods[$m->name] = $m;
           }
        }
    }

   /**
   * parse a join definition
   */
   private function _parseTable ($tabletag, $noMoreJoin =false){
      $infos = $this->getAttr($tabletag, array('name','realname'));

      if ($infos['name'] === null )
         $this->_compiler->doDefError('table.name');

      if($infos['realname'] === null)
         $infos['realname'] = $infos['name'];

      $infos['pk']=array();
      foreach($tabletag->primarykey as $pk){
         if(!isset($pk['fieldname'])){
            $this->_compiler->doDefError ('primarykey.fieldname.missing');
         }else{
            $infos['pk'][]=(string)$pk['fieldname'];
         }
      }
      if(!count($infos['pk'])){
         $this->_compiler->doDefError ('primarykey.fieldname.missing');
      }

      if($noMoreJoin && isset($tabletag->foreignkey)){
         $this->_compiler->doDefError ('primarykey.innerafterouter.forbidden');
      }

      if(!$noMoreJoin){
         foreach($tabletag->foreignkey as $fk){
            $fkattr = $this->getAttr($fk, array('fieldname','join'));
            if($fkattr['fieldname']===null){
                  $this->_compiler->doDefError ('foreignkey.fieldname.missing');
            }
            if($fkattr['join']=== null){
               $fkattr['join']='inner';
            }elseif(!in_array($fkattr['join'], array('inner','left','right'))){
               $this->_compiler->doDefError ('foreignkey.join.invalid',$fkattr['fieldname']);
            }

            if(!isset($fk->table)){
               $this->_compiler->doDefError ('foreignkey.table.missing', $fkattr['fieldname']);
            }

            $table= $this->_parseTable($fk->table[0], ($fkattr['join']!='inner'));

            if($fkattr['join']=='inner'){
               $this->_ijoins[]=array ('l'=> $infos['name'], 'r'=> $table['name'],
                     'lf'=>$fkattr['fieldname'], 'rf'=>$table['pk'][0]);
            }else{
               $this->_ojoins[]=array ('j'=>$fkattr['join'], 'l'=> $infos['name'], 'r'=> $table['name'],
                     'lf'=>$fkattr['fieldname'], 'rf'=>$table['pk'][0]);
            }
         }
      }
      $infos['fields'] = array ();
      $this->_tables[$infos['name']] = $infos;

      return $infos;
   }

    public function getAttr($tag, $requiredattr){
       $res=array();
       foreach($requiredattr as $attr){
          if(isset($tag[$attr]) && trim((string)$tag[$attr]) != '')
             $res[$attr]=(string)$tag[$attr];
          else
             $res[$attr]=null;
       }
       return $res;
    }

    /**
    * just a quick way to retriveve boolean values from a string.
    *  will accept yes, true, 1 as "true" values
    *  the rest will be considered as false values.
    * @return boolean true / false
    */
    public function getBool ($value) {
        return in_array (trim ($value), array ('true', '1', 'yes'));
    }

    public function getProperties () { return $this->_properties; }
    public function getTables(){  return $this->_tables;}
    public function getPrimaryTable(){  return $this->_primaryTable;}
    public function getMethods(){  return $this->_methods;}
    public function getOuterJoins(){  return $this->_ojoins;}
    public function getInnerJoins(){  return $this->_ijoins;}
}




//--------------------------------------------------------
/**
* objet comportant les donn�es d'une propri�t� d'un record DAO
*/

class jDAOProperty {
    /**
    * the name of the property of the object
    */
    public $name = '';

    /**
    * the name of the field in table
    */
    public $fieldName = '';

    /**
    * give the regular expression that needs to be matched against.
    * @var string
    */
    public $regExp = null;

    /**
    * says if the field is required.
    * @var boolean
    */
    public $required = false;

    /**
    * Is it a string ?
    * @var boolean
    */
    public $isString = true;

    /**
    * Says if it's a primary key.
    * @var boolean
    */
    public $isPK = false;

    /**
    * Says if it's a foreign key
    * @var boolean
    */
    public $isFK = false;

    public $datatype;

    public $table=null;
    public $updateMotif='%s';
    public $insertMotif='%s';
    public $selectMotif='%s';
    public $sequenceName='';

    /**
    * the maxlength of the key if given
    * @var int
    */
    public $maxlength = null;
    public $minlength = null;

    public $needQuotes = true;
    public $ofPrimaryTable = true;

    /**
    * constructor.
    <property name="nom simplifi�" field="nom du champs" table="alias de la table"
      datatype=""   required="true/false"
      minlength="" maxlength="" regexp="" sequence="nom de la sequence"
      updatemotif="" insertmotif="" selectmotif=""
     />
    */
    function __construct ($params, $def){
        $needed = array('name', 'fieldname', 'table', 'datatype', 'required', 'minlength',
        'maxlength', 'regexp', 'sequence', 'updatemotif', 'insertmotif', 'selectmotif');

        $params = $def->getAttr($params, $needed);

        if ($params['name']===null){
            $def->_compiler->doDefError('missing.attr', array('name', 'property'));
        }
        $this->name       = $params['name'];
        $this->fieldName  = $params['fieldname'] !==null ? $params['fieldname'] : $this->name;
        $this->table      = $params['table'] !==null ? $params['table'] : $def->getPrimaryTable();

        $tables = $def->getTables();

        if(!isset( $tables[$this->table])){
            $def->_compiler->doDefError('property.unknow.table', $this->name);
        }

        $this->required   = $def->getBool ($params['required']);
        $this->maxlength  = $params['maxlength'] !== null ? intval($params['maxlength']) : null;
        $this->minlength  = $params['minlength'] !== null ? intval($params['minlength']) : null;
        $this->regExp     = $params['regexp'];


        $this->isPK = in_array($this->fieldName, $tables[$this->table]['pk']);
        if(!$this->isPK){
           $this->isFK = isset($tables[$this->table]['fk'][$this->fieldName]);
        }

        if ($params['datatype']===null){
            $def->_compiler->doDefError('missing.attr', array('type', 'field'));
        }
        $params['datatype']=trim(strtolower($params['datatype']));
        $this->needsQuotes = in_array ($params['datatype'], array ('string', 'date', 'datetime', 'time'));

        if (!in_array ($params['datatype'], array ('autoincrement', 'bigautoincrement', 'int', 'datetime', 'time',
                                    'integer', 'varchar', 'string', 'varchardate', 'date', 'numeric', 'double', 'float'))){
           $def->_compiler->doDefError('wrong.attr', array($params['datatype'], $this->fieldName));
        }
        $this->datatype = strtolower($params['datatype']);

        if(($this->datatype == 'autoincrement' || $this->datatype == 'bigautoincrement')
           && $params['sequence'] !==null){
            $this->sequenceName = $params['sequence'];
        }

        // on ignore les attributs *motif sur les champs PK et FK
        if(!$this->isPK && !$this->isFK){
            $this->updateMotif= $params['updatemotif']!==null ? $params['updatemotif'] :'%s';
            $this->insertMotif= $params['insertmotif']!==null ? $params['insertmotif'] :'%s';
            $this->selectMotif= $params['selectmotif']!==null ? $params['selectmotif'] :'%s';
        }

        // pas de motif update et insert pour les champs des tables externes
        if($this->table != $def->getPrimaryTable()){
            $this->updateMotif = '';
            $this->insertMotif = '';
            $this->required = false;
            $this->ofPrimaryTable = false;
        }else{
            $this->ofPrimaryTable=true;
        }
    }
}




//--------------------------------------------------------
/**
* objet d�crivant une m�thode DAO
*/
class jDAOMethod {
   public $name;
   public $type;
   private $_conditions = null;
   private $_parameters   = array();
   private $_limit = null;
   private $_values = array();
   private $_def = null;
   private $_procstock=null;

   function __construct ($method, $def){
      $this->_def = $def;

      $params = $def->getAttr($method, array('name', 'type', 'call'));

      if ($params['name']===null){
         $def->_compiler->doDefError ('missing.attr', array('name', 'method'));
      }

      $this->name  = $params['name'];
      $this->type  = $params['type'] ? strtolower($params['type']) : 'select';

      if (isset ($method->parameter)){
         foreach ($method->parameter as $param){
            $attr = $param->attributes();

            if (!isset ($attr['name'])){
                  $this->_def->_compiler->doDefError('method.parameter.unknowname', array($this->name));
            }
            $this->_parameters[]=(string)$attr['name'];
         }
      }

      if($this->type == 'sql'){
         if($params['call'] === null){
            $def->_compiler->doDefError ('method.procstock.name.missing');
         }
         $this->_procstock=$params['call'];
         return;
      }

      if($this->type == 'php'){
         if (isset ($method->body)){
            $this->_body = (string)$method->body;
         }else{
            $def->_compiler->doDefError ('method.body.missing');
         }
         return;
      }

      if (isset ($method->conditions)){
         $this->_conditions = new jDAOConditions();
         $this->_parseConditions($method,false);
      }else{
         $this->_conditions = new jDAOConditions();
      }

      if($this->type == 'update'){
         if(isset($method->values) && isset($method->values[0]->value)){
            foreach ($method->values[0]->value as $val){
               $this->_addValue($val);
            }
         }else{
               $def->_compiler->doDefError('method.values.undefine',array($this->name));
         }
      }

      if (isset ($method->order) && isset($method->order[0]->orderitem)){
         foreach($method->order[0]->orderitem as $item){
            $this->_addOrder ($item);
         }
      }

      if (isset($method->limit)){
         if(isset($method->limit[1])){
               $def->_compiler->doDefError('tag.duplicate', array('limit', $this->name));
         }
         if($this->type == 'select' || $this->type == 'selectfirst'){
            $this->_addLimit($method->limit[0]);
         }else{
            $def->_compiler->doDefError('method.limit.forbidden', $this->name);
         }
      }
   }

   public function getConditions (){ return $this->_conditions;}
   public function getParameters (){ return $this->_parameters;}
   public function getLimit (){ return $this->_limit;}
   public function getValues (){ return $this->_values;}
   public function getProcStock (){ return $this->_procstock;}
   public function getBody (){ return $this->_body;}

   private function _parseConditions($node, $subcond=true){
      if (isset ($node->conditions)){
         if (isset ($node->conditions['logic'])){
            $kind = (string)$node->conditions['logic'];
         }else{
            $kind = 'AND';
         }

         if ($subcond){
            $this->_conditions->startGroup ($kind);
         }else{
            $this->_conditions->condition->glueOp =$kind;
         }

         foreach ($node->conditions as $conds){

            foreach($conds->children() as $op=>$cond){
                $this->_addCondition ($op,$cond);
            }
            $this->_parseConditions ($conds);
         }

         if ($subcond) {
               $this->_conditions->endGroup();
         }
      }
   }


    /*
      <eq         property="foo" value="" expr=""/>
      <noteq      property="foo" value="" expr=""/>
      <lt         property="foo" value="" expr=""/>
      <gt         property="foo" value="" expr=""/>
      <lteq       property="foo" value="" expr=""/>
      <gteq       property="foo" value="" expr=""/>
      <in         property="foo" value="" expr=""/>
      <notin      property="foo" value="" expr=""/>
      <between    property="foo" min="" max="" exprmin="" exprmax=""/>
      <notbetween property="foo" min="" max="" exprmin="" exprmax=""/>
      <isnull     property="foo"/>
      <notisnull  property="foo"/>
    */


   private $_op = array('eq'=>'=', 'neq'=>'<>', 'lt'=>'<', 'gt'=>'>', 'like'=>'LIKE',
       'isnull'=>'IS NULL', 'isnotnull'=>'IS NOT NULL');
      // 'between'=>'BETWEEN',  'notbetween'=>'NOT BETWEEN','in'=>'IN', 'notin'=>'NOT IN',

   private $_attrcond = array('property', 'value', 'expr'); //, 'min', 'max', 'exprmin', 'exprmax'

   private function _addCondition($op, $cond){

      $attr = $this->_def->getAttr($cond, $this->_attrcond);

      $field_id = ($attr['property']!==null? $attr['property']:'');

      if(!isset($this->_op[$op])){
         $this->_def->_compiler->doDefError('method.condition.unknown', array($this->name, $op));
      }

      $operator = $this->_op[$op];



      $props = $this->_def->getProperties();



      if (!isset ($props[$field_id])){
         $this->_def->_compiler->doDefError('method.property.unknown', array($this->name, $field_id));
      }

      if($this->type=='update'){
         if($props[$field_id]->table != $this->_def->getPrimaryTable()){
            $this->_def->_compiler->doDefError('method.property.forbidden', array($this->name, $field_id));
         }
      }

      if($attr['value']!==null && $attr['expr']!==null){
         $this->_def->_compiler->doDefError('method.condition.valueexpr.together', array($this->name, $op));
      }else if($attr['value']!==null){
         if($op == 'isnull' || $op =='isnotnull'){
            $this->_def->_compiler->doDefError('method.condition.valueexpr.notallowed', array($this->name, $op,$field_id));
         }
         $this->_conditions->addCondition ($field_id, $operator, $attr['value']);
      }else if($attr['expr']!==null){
         if($op == 'isnull' || $op =='isnotnull'){
            $this->_def->_compiler->doDefError('method.condition.valueexpr.notallowed', array($this->name, $op, $field_id));
         }
         $this->_conditions->addCondition ($field_id, $operator, $attr['expr'], true);
      }else{
          if($op != 'isnull' && $op !='isnotnull'){
              $this->_def->_compiler->doDefError('method.condition.valueexpr.missing', array($this->name, $op, $field_id));
          }
      }
   }

   private function _addOrder($order){
      $attr = $this->_def->getAttr($order, array('property','way'));

      $way  = ($attr['way'] !== null ? $attr['way']:'ASC');

      if ($attr['property'] != ''){
          $prop =$this->_def->getProperties();
         if(isset($prop[$attr['property']])){
               $this->_conditions->addItemOrder($attr['property'], $way);
         }else{
               $this->_def->_compiler->doDefError('method.orderitem.bad', array($attr['property'], $this->name));
         }
      }else{
         $this->_def->_compiler->doDefError('method.orderitem.property.missing', array($this->name));
      }
   }

   private function _addValue($attr){
      $attr = $this->_def->getAttr($attr, array('property','value','expr'));

      $prop = $attr['property'];
      $props =$this->_def->getProperties();

      if ($prop === null){
         $this->_def->_compiler->doDefError('method.values.property.unknow', array($this->name, $prop));
         return false;
      }

      if(!isset($props[$prop])){
         $this->_def->_compiler->doDefError('method.values.property.unknow', array($this->name, $prop));
         return false;
      }

      if($props[$prop]->table != $this->_def->getPrimaryTable()){
         $this->_def->_compiler->doDefError('method.values.property.bad', array($this->name,$prop ));
         return false;
      }
      if($props[$prop]->isPK){
         $this->_def->_compiler->doDefError('method.values.property.pkforbidden', array($this->name,$prop ));
         return false;
      }

      if($attr['value']!==null && $attr['expr']!==null){
         $this->_def->_compiler->doDefError('method.values.valueexpr', array($this->name, $prop));
      }else if($attr['value']!==null){
         $this->_values [$prop]= array( $attr['value'], false);
      }else if($attr['expr']!==null){
         $this->_values [$prop]= array( $attr['expr'], true);
      }

   }

   private function _addLimit($limit){
      $attr = $this->_def->getAttr($limit, array('offset','count'));

      extract($attr);

      if( $offset === null){
         $this->_def->_compiler->doDefError('missing.attr',array('offset','limit'));
      }
      if($count === null){
         $this->_def->_compiler->doDefError('missing.attr',array('count','limit'));
      }

      if(substr ($offset,0,1) == '$'){
         if(in_array (substr ($offset,1),$this->_parameters)){
            $offsetparam=true;
         }else{
            $this->_def->_compiler->doDefError('method.limit.parameter.unknow', array($this->name, $offset));
         }
      }else{
         if(is_numeric ($offset)){
            $offsetparam=false;
            $offset = intval ($offset);
         }else{
            $this->_def->_compiler->doDefError('method.limit.badvalue', array($this->name, $offset));
         }
      }

      if(substr ($count,0,1) == '$'){
         if(in_array (substr ($count,1),$this->_parameters)){
            $countparam=true;
         }else{
            $this->_def->_compiler->doDefError('method.limit.parameter.unknow', array($this->name, $count));
         }
      }else{
         if(is_numeric($count)){
            $countparam=false;
            $count=intval($count);
         }else{
            $this->_def->_compiler->doDefError('method.limit.badvalue', array($this->name, $count));
         }
      }
      $this->_limit= compact('offset', 'count', 'offsetparam','countparam');
   }
}
?>