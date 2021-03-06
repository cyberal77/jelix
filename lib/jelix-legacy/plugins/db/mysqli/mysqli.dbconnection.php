<?php
/**
 * @package    jelix
 * @subpackage db_driver
 *
 * @author     Gérald Croes, Laurent Jouanneau
 * @contributor Laurent Jouanneau
 * @contributor Sylvain de Vathaire, Julien Issler
 * @contributor Florian Lonqueu-Brochard
 *
 * @copyright  2001-2005 CopixTeam, 2005-2012 Laurent Jouanneau
 * @copyright  2009 Julien Issler
 * @copyright  2012 Florian Lonqueu-Brochard
 *
 * @see      http://www.jelix.org
 * @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
 */
require_once __DIR__.'/mysqli.dbresultset.php';

/**
 * @package    jelix
 * @subpackage db_driver
 */
class mysqliDbConnection extends jDbConnection
{
    protected $_charsets = array('UTF-8' => 'utf8', 'ISO-8859-1' => 'latin1');
    private $_usesMysqlnd;

    public function __construct($profile)
    {
        // à cause du @, on est obligé de tester l'existence de mysql, sinon en cas d'absence
        // on a droit à un arret sans erreur
        if (!function_exists('mysqli_connect')) {
            throw new jException('jelix~db.error.nofunction', 'mysql');
        }
        parent::__construct($profile);

        $this->dbms = 'mysql';
    }

    /**
     * enclose the field name.
     *
     * @param string $fieldName the field name
     *
     * @return string the enclosed field name
     *
     * @since 1.1.1
     */
    public function encloseName($fieldName)
    {
        return '`'.$fieldName.'`';
    }

    /**
     * begin a transaction.
     */
    public function beginTransaction()
    {
        $this->_connection->begin_transaction();
        $this->_autoCommitNotify(false);
    }

    /**
     * Commit since the last begin.
     */
    public function commit()
    {
        $this->_connection->commit();
        $this->_autoCommitNotify(true);
    }

    /**
     * Rollback since the last begin.
     */
    public function rollback()
    {
        $this->_connection->rollback();
        $this->_autoCommitNotify(true);
    }

    /**
     * @param mixed $query
     */
    public function prepare($query)
    {
        list($newQuery, $parameterNames) = $this->findParameters($query, '?');
        $res = $this->_connection->prepare($newQuery);
        if ($res) {
            $rs = new mysqliDbResultSet(null, $res, $parameterNames);
        } else {
            throw new jException('jelix~db.error.query.bad', $this->_connection->error.'('.$query.')');
        }

        return $rs;
    }

    public function errorInfo()
    {
        return array('HY000', $this->_connection->errno, $this->_connection->error);
    }

    public function errorCode()
    {
        return $this->_connection->errno;
    }

    protected function _connect()
    {
        $host = ($this->profile['persistent']) ? 'p:'.$this->profile['host'] : $this->profile['host'];
        if (isset($this->profile['ssl']) && $this->profile['ssl']) {
            $cnx = mysqli_init();
            if (!$cnx) {
                throw new jException('jelix~db.error.connection', $this->profile['host']);
            }
            mysqli_ssl_set(
                $cnx,
                (isset($this->profile['ssl_key_pem']) ? $this->profile['ssl_key_pem'] : null),
                (isset($this->profile['ssl_cert_pem']) ? $this->profile['ssl_cert_pem'] : null),
                (isset($this->profile['ssl_cacert_pem']) ? $this->profile['ssl_cacert_pem'] : null),
                null,
                null
            );
            if (!mysqli_real_connect(
                $cnx,
                $host,
                $this->profile['user'],
                $this->profile['password'],
                $this->profile['database']
            )) {
                throw new jException('jelix~db.error.connection', $this->profile['host']);
            }
        } else {
            $cnx = @new mysqli($host, $this->profile['user'], $this->profile['password'], $this->profile['database']);
        }
        if ($cnx->connect_errno) {
            throw new jException('jelix~db.error.connection', $this->profile['host']);
        }

        if ($this->profile['force_encoding'] == true
              && isset($this->_charsets[jApp::config()->charset])) {
            $cnx->set_charset($this->_charsets[jApp::config()->charset]);
        }

        return $cnx;
    }

    protected function _disconnect()
    {
        return $this->_connection->close();
    }

    protected function _doQuery($query)
    {
        if ($qI = $this->_connection->query($query)) {
            return new mysqliDbResultSet($qI);
        }

        throw new jException('jelix~db.error.query.bad', $this->_connection->error.'('.$query.')');
    }

    protected function _doExec($query)
    {
        if ($qI = $this->_connection->query($query)) {
            return $this->_connection->affected_rows;
        }

        throw new jException('jelix~db.error.query.bad', $this->_connection->error.'('.$query.')');
    }

    protected function _doLimitQuery($queryString, $offset, $number)
    {
        $queryString .= ' LIMIT '.$offset.','.$number;
        $this->lastQuery = $queryString;

        return $this->_doQuery($queryString);
    }

    public function lastInsertId($fromSequence = '')
    {
        return $this->_connection->insert_id;
    }

    /**
     * tell mysql to be autocommit or not.
     *
     * @param bool $state the state of the autocommit value
     */
    protected function _autoCommitNotify($state)
    {
        $this->_connection->autocommit($state);
    }

    /**
     * @param mixed $text
     * @param mixed $binary
     *
     * @return string escaped text or binary string
     */
    protected function _quote($text, $binary)
    {
        return $this->_connection->real_escape_string($text);
    }

    /**
     * @param int $id the attribut id
     *
     * @return string the attribute value
     *
     * @see PDO::getAttribute()
     */
    public function getAttribute($id)
    {
        switch ($id) {
            case self::ATTR_CLIENT_VERSION:
                return $this->_connection->get_client_info();
            case self::ATTR_SERVER_VERSION:
                return $this->_connection->server_info;

                break;
            case self::ATTR_SERVER_INFO:
                return $this->_connection->host_info;
        }

        return '';
    }

    /**
     * @param int    $id    the attribut id
     * @param string $value the attribute value
     *
     * @see PDO::setAttribute()
     */
    public function setAttribute($id, $value)
    {
    }

    /**
     * Execute several sql queries.
     *
     * @param mixed $queries
     */
    public function execMulti($queries)
    {
        $query_res = $this->_connection->multi_query($queries);
        while ($this->_connection->more_results()) {
            $this->_connection->next_result();
            if ($discard = $this->_connection->store_result()) {
                $discard->free();
            }
        }

        return $query_res;
    }
}
