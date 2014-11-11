<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Test\Contao\Database\Contao211;

/**
 * Class Database.
 *
 * Provide methods to handle database communication.
 *
 * @copyright  Leo Feyer 2005-2013.
 * @author     Leo Feyer <https://contao.org>
 * @package    Library
 */
abstract class Database
{
    /**
     * Object instances (Singleton).
     *
     * @var array
     */
    protected static $arrInstances = array();

    /**
     * Connection configuration.
     *
     * @var array
     */
    protected $arrConfig = array();

    /**
     * Connection ID.
     *
     * @var resource
     */
    protected $resConnection;

    /**
     * Disable autocommit.
     *
     * @var boolean
     */
    protected $blnDisableAutocommit = false;

    /**
     * Cache array.
     *
     * @var array
     */
    protected $arrCache = array();

    /**
     * Prevent cloning of the object (Singleton).
     *
     * @return void
     */
    final private function __clone()
    {
    }

    /**
     * Return an object property.
     *
     * @param string $strKey The property name.
     *
     * @return string|null The property value.
     */
    public function __get($strKey)
    {
        if ($strKey == 'error') {
            return $this->get_error();
        }

        return null;
    }

    /**
     * Prepare a query and return a Database\Statement object.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Statement The Database\Statement object.
     */
    public function prepare($strQuery)
    {
        $objStatement = $this->createStatement($this->resConnection, $this->blnDisableAutocommit);
        return $objStatement->prepare($strQuery);
    }

    /**
     * Execute a query and return a Database\Result object.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Result The Database\Result object.
     */
    public function execute($strQuery)
    {
        return $this->prepare($strQuery)->execute();
    }

    /**
     * Execute a query and do not cache the result.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Result The Database\Result object.
     *
     * @deprecated Use \Database::execute() instead.
     */
    public function executeUncached($strQuery)
    {
        return $this->prepare($strQuery)->executeUncached();
    }

    /**
     * Execute a raw query and return a Database\Result object.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Result The Database\Result object.
     */
    public function query($strQuery)
    {
        $objStatement = $this->createStatement($this->resConnection, $this->blnDisableAutocommit);
        return $objStatement->query($strQuery);
    }

    /**
     * Auto-generate a FIND_IN_SET() statement.
     *
     * @param string  $strKey     The field name.
     * @param mixed   $varSet     The set to find the key in.
     * @param boolean $blnIsField If true, the set will not be quoted.
     *
     * @return string The FIND_IN_SET() statement
     */
    public function findInSet($strKey, $varSet, $blnIsField = false)
    {
        if (is_array($varSet)) {
            $varSet = implode(',', $varSet);
        }

        return $this->find_in_set($strKey, $varSet, $blnIsField);
    }

    /**
     * Return all tables of a database as array.
     *
     * @param string  $strDatabase The database name.
     * @param boolean $blnNoCache  If true, the cache will be bypassed.
     *
     * @return array An array of table names.
     */
    public function listTables($strDatabase = null, $blnNoCache = false)
    {
        if ($strDatabase === null) {
            $strDatabase = $this->arrConfig['dbDatabase'];
        }

        if (!$blnNoCache && isset($this->arrCache[$strDatabase])) {
            return $this->arrCache[$strDatabase];
        }

        $arrReturn = array();
        $arrTables = $this->query(sprintf($this->strListTables, $strDatabase))->fetchAllAssoc();

        foreach ($arrTables as $arrTable) {
            $arrReturn[] = current($arrTable);
        }

        $this->arrCache[$strDatabase] = $arrReturn;
        return $this->arrCache[$strDatabase];
    }

    /**
     * Determine if a particular database table exists.
     *
     * @param string  $strTable    The table name.
     * @param string  $strDatabase The optional database name.
     * @param boolean $blnNoCache  If true, the cache will be bypassed.
     *
     * @return boolean True if the table exists.
     */
    public function tableExists($strTable, $strDatabase = null, $blnNoCache = false)
    {
        return in_array($strTable, $this->listTables($strDatabase, $blnNoCache));
    }

    /**
     * Return all columns of a particular table as array.
     *
     * @param string  $strTable   The table name.
     * @param boolean $blnNoCache If true, the cache will be bypassed.
     *
     * @return array An array of column names.
     */
    public function listFields($strTable, $blnNoCache = false)
    {
        if (!$blnNoCache && isset($this->arrCache[$strTable])) {
            return $this->arrCache[$strTable];
        }

        $this->arrCache[$strTable] = $this->list_fields($strTable);
        return $this->arrCache[$strTable];
    }

    /**
     * Determine if a particular column exists.
     *
     * @param string  $strField   The field name.
     * @param string  $strTable   The table name.
     * @param boolean $blnNoCache If true, the cache will be bypassed.
     *
     * @return boolean True if the field exists.
     */
    public function fieldExists($strField, $strTable, $blnNoCache = false)
    {
        foreach ($this->listFields($strTable, $blnNoCache) as $arrField) {
            if ($arrField['name'] == $strField) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the field names of a particular table as array.
     *
     * @param string  $strTable   The table name.
     * @param boolean $blnNoCache If true, the cache will be bypassed.
     *
     * @return array An array of field names.
     */
    public function getFieldNames($strTable, $blnNoCache = false)
    {
        $arrNames  = array();
        $arrFields = $this->listFields($strTable, $blnNoCache);

        foreach ($arrFields as $arrField) {
            $arrNames[] = $arrField['name'];
        }

        return $arrNames;
    }

    /**
     * Change the current database.
     *
     * @param string $strDatabase The name of the target database.
     *
     * @return boolean True if the database was changed successfully.
     */
    public function setDatabase($strDatabase)
    {
        return $this->set_database($strDatabase);
    }

    /**
     * Begin a transaction.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->begin_transaction();
    }

    /**
     * Commit a transaction.
     *
     * @return void
     */
    public function commitTransaction()
    {
        $this->commit_transaction();
    }

    /**
     * Rollback a transaction.
     *
     * @return void
     */
    public function rollbackTransaction()
    {
        $this->rollback_transaction();
    }

    /**
     * Lock one or more tables.
     *
     * @param array $arrTables An array of table names to be locked.
     *
     * @return void
     */
    public function lockTables($arrTables)
    {
        $this->lock_tables($arrTables);
    }

    /**
     * Unlock all tables.
     *
     * @return void
     */
    public function unlockTables()
    {
        $this->unlock_tables();
    }

    /**
     * Return the table size in bytes.
     *
     * @param string $strTable The table name.
     *
     * @return integer The table size in bytes.
     */
    public function getSizeOf($strTable)
    {
        return $this->get_size_of($strTable);
    }

    /**
     * Return the next autoincrement ID of a table.
     *
     * @param string $strTable The table name.
     *
     * @return integer The autoincrement ID.
     */
    public function getNextId($strTable)
    {
        return $this->get_next_id($strTable);
    }

    /**
     * Connect to the database server and select the database.
     *
     * @return void
     */
    abstract protected function connect();

    /**
     * Disconnect from the database.
     *
     * @return void
     */
    abstract protected function disconnect();

    // @codingStandardsIgnoreStart - camel case.
    /**
     * Return the last error message.
     *
     * @return string The error message
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function get_error();

    /**
     * Auto-generate a FIND_IN_SET() statement.
     *
     * @param string  $strKey     The field name.
     * @param mixed   $strSet     The set to find the key in.
     * @param boolean $blnIsField If true, the set will not be quoted.
     *
     * @return string The FIND_IN_SET() statement.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function find_in_set($strKey, $strSet, $blnIsField = false);

    /**
     * Begin a transaction.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function begin_transaction();

    /**
     * Commit a transaction.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function commit_transaction();

    /**
     * Rollback a transaction.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function rollback_transaction();

    /**
     * Return a standardized array with the field information.
     *
     * Keys:
     * * name:       field name (e.g. my_field)
     * * type:       field type (e.g. "int" or "number")
     * * length:     field length (e.g. 20)
     * * precision:  precision of a float number (e.g. 5)
     * * null:       NULL or NOT NULL
     * * default:    default value (e.g. "default_value")
     * * attributes: attributes (e.g. "unsigned")
     * * index:      PRIMARY, UNIQUE or INDEX
     * * extra:      extra information (e.g. auto_increment)
     *
     * @param string $strTable The table name.
     *
     * @return array An array with the field information.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function list_fields($strTable);

    /**
     * Change the current database.
     *
     * @param string $strDatabase The name of the target database.
     *
     * @return boolean True if the database was changed successfully.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function set_database($strDatabase);

    /**
     * Lock one or more tables.
     *
     * @param array $arrTables An array of table names.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function lock_tables($arrTables);

    /**
     * Unlock all tables.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function unlock_tables();

    /**
     * Return the table size in bytes.
     *
     * @param string $strTable The table name.
     *
     * @return integer The table size in bytes.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function get_size_of($strTable);

    /**
     * Return the next autoincrement ID of a table.
     *
     * @param string $strTable The table name.
     *
     * @return integer The autoincrement ID.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function get_next_id($strTable);
    // @codingStandardsIgnoreEnd - camel case.

    /**
     * Create a Database\Statement object.
     *
     * @param resource $resConnection        The connection ID.
     * @param boolean  $blnDisableAutocommit If true, autocommitting will be disabled.
     *
     * @return \Database\Statement The Database\Statement object.
     */
    abstract protected function createStatement($resConnection, $blnDisableAutocommit);
}
