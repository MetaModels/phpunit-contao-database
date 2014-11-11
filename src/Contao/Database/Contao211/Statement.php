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
 * Class Database_Statement.
 *
 * Provide methods to execute a database query.
 *
 * @copyright  Leo Feyer 2005-2013
 * @author     Leo Feyer <https://contao.org>
 * @package    Library
 */
abstract class Statement
{

    /**
     * Connection ID.
     *
     * @var resource
     */
    protected $resConnection;

    /**
     * Current result.
     *
     * @var resource
     */
    protected $resResult;

    /**
     * Current query string.
     *
     * @var string
     */
    protected $strQuery;

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
    protected static $arrCache = array();

    /**
     * Validate the connection resource and store the query string.
     *
     * @param resource $resConnection        The connection resource.
     * @param boolean  $blnDisableAutocommit Optionally disable autocommitting.
     *
     * @throws \Exception If $resConnection is not a valid resource.
     */
    public function __construct($resConnection, $blnDisableAutocommit = false)
    {
        if (!is_resource($resConnection) && !is_object($resConnection)) {
            throw new \Exception('Invalid connection resource');
        }

        $this->resConnection        = $resConnection;
        $this->blnDisableAutocommit = $blnDisableAutocommit;
    }

    /**
     * Return an object property.
     *
     * Supported parameters:
     *
     * * query:        the query string
     * * error:        the last error message
     * * affectedRows: the number of affected rows
     * * insertId:     the last insert ID
     *
     * @param string $strKey The property name.
     *
     * @return mixed|null The property value or null.
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'query':
                return $this->strQuery;

            case 'error':
                return $this->get_error();

            case 'affectedRows':
                return $this->affected_rows();

            case 'insertId':
                return $this->insert_id();

            default:
                return null;
        }
    }

    /**
     * Prepare a query string so the following functions can handle it.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Statement The statement object.
     *
     * @throws \Exception If $strQuery is empty.
     */
    public function prepare($strQuery)
    {
        if (!strlen($strQuery)) {
            throw new \Exception('Empty query string');
        }

        $this->resResult = null;
        $this->strQuery  = $this->prepare_query($strQuery);

        // Auto-generate the SET/VALUES subpart
        if (strncasecmp($this->strQuery, 'INSERT', 6) === 0 || strncasecmp($this->strQuery, 'UPDATE', 6) === 0) {
            $this->strQuery = str_replace('%s', '%p', $this->strQuery);
        }

        // Replace wildcards
        $arrChunks = preg_split("/('[^']*')/", $this->strQuery, -1, (PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));

        foreach ($arrChunks as $k => $v) {
            if (substr($v, 0, 1) == "'") {
                continue;
            }

            $arrChunks[$k] = str_replace('?', '%s', $v);
        }

        $this->strQuery = trim(implode('', $arrChunks));
        return $this;
    }

    /**
     * Autogenerate the SET/VALUES subpart of a query from an associative array.
     *
     * Usage:
     *
     *     $set = array(
     *         'firstname' => 'Leo',
     *         'lastname'  => 'Feyer'
     *     );
     *     $stmt->prepare("UPDATE tl_member %s")->set($set);
     *
     * @param array $arrParams The associative array.
     *
     * @return \Database\Statement The statement object.
     */
    public function set($arrParams)
    {
        $arrParams = $this->escapeParams($arrParams);

        // INSERT
        if (strncasecmp($this->strQuery, 'INSERT', 6) === 0) {
            $strQuery = sprintf(
                '(%s) VALUES (%s)',
                implode(', ', array_keys($arrParams)),
                str_replace('%', '%%', implode(', ', array_values($arrParams)))
            );
        } elseif (strncasecmp($this->strQuery, 'UPDATE', 6) === 0) {
            // UPDATE
            $arrSet = array();

            foreach ($arrParams as $k => $v) {
                $arrSet[] = $k . '=' . $v;
            }

            $strQuery = 'SET ' . str_replace('%', '%%', implode(', ', $arrSet));
        }

        $this->strQuery = str_replace('%p', $strQuery, $this->strQuery);
        return $this;
    }

    /**
     * Handle limit and offset.
     *
     * @param integer $intRows   The maximum number of rows.
     * @param integer $intOffset The number of rows to skip.
     *
     * @return \Database\Statement The statement object.
     */
    public function limit($intRows, $intOffset = 0)
    {
        if ($intRows <= 0) {
            $intRows = 30;
        }

        if ($intOffset < 0) {
            $intOffset = 0;
        }

        $this->limit_query($intRows, $intOffset);
        return $this;
    }

    /**
     * Execute the query and return the result object.
     *
     * @return \Database\Result The result object.
     */
    public function execute()
    {
        $arrParams = func_get_args();

        if (is_array($arrParams[0])) {
            $arrParams = array_values($arrParams[0]);
        }

        $this->replaceWildcards($arrParams);
        $strKey = md5($this->strQuery);

        // Try to load the result from cache
        if (isset(self::$arrCache[$strKey]) && !self::$arrCache[$strKey]->isModified) {
            return self::$arrCache[$strKey]->reset();
        }

        $objResult = $this->query();

        // Cache the result objects
        if ($objResult instanceof Result) {
            self::$arrCache[$strKey] = $objResult;
        }

        return $objResult;
    }

    /**
     * Bypass the cache and always execute the query.
     *
     * @return \Database\Result The result object.
     *
     * @deprecated Use Database\Statement::execute() instead.
     */
    public function executeUncached()
    {
        $arrParams = func_get_args();

        if (is_array($arrParams[0])) {
            $arrParams = array_values($arrParams[0]);
        }

        $this->replaceWildcards($arrParams);
        return $this->query();
    }

    /**
     * Directly send a query string to the database.
     *
     * @param string $strQuery The query string.
     *
     * @return \Database\Result|\Database\Statement The result object or the statement object if there is no result set.
     *
     * @throws \Exception If the query cannot be executed.
     */
    public function query($strQuery = '')
    {
        if (!empty($strQuery)) {
            $this->strQuery = $strQuery;
        }

        // Make sure there is a query string
        if ($this->strQuery == '') {
            throw new \Exception('Empty query string');
        }

        // Execute the query
        if (($this->resResult = $this->execute_query()) == false) {
            throw new \Exception(sprintf('Query error: %s (%s)', $this->error, $this->strQuery));
        }

        // No result set available
        if (!is_resource($this->resResult) && !is_object($this->resResult)) {
            $this->debugQuery();
            return $this;
        }

        // Instantiate a result object
        $objResult = $this->createResult($this->resResult, $this->strQuery);
        $this->debugQuery($objResult);

        return $objResult;
    }

    /**
     * Replace the wildcards in the query string.
     *
     * @param array $arrParams The values array.
     *
     * @return void
     *
     * @throws \Exception If $arrValues has too few values to replace the wildcards in the query string.
     */
    protected function replaceWildcards($arrParams)
    {
        $arrParams      = $this->escapeParams($arrParams);
        $this->strQuery = preg_replace('/(?<!%)%([^bcdufosxX%])/', '%%$1', $this->strQuery);

        // Replace wildcards
        // @codingStandardsIgnoreStart
        if (($this->strQuery = @vsprintf($this->strQuery, $arrParams)) == false) {
        // @codingStandardsIgnoreEnd
            throw new \Exception('Too few arguments to build the query string');
        }
    }

    /**
     * Escape the values and serialize objects and arrays.
     *
     * @param array $arrParams The values array.
     *
     * @return array The array with the escaped values.
     */
    protected function escapeParams($arrParams)
    {
        foreach ($arrParams as $k => $v) {
            switch (gettype($v)) {
                case 'string':
                    $arrParams[$k] = $this->string_escape($v);
                    break;

                case 'boolean':
                    $arrParams[$k] = ($v === true) ? 1 : 0;
                    break;

                case 'object':
                    $arrParams[$k] = $this->string_escape(serialize($v));
                    break;

                case 'array':
                    $arrParams[$k] = $this->string_escape(serialize($v));
                    break;

                default:
                    $arrParams[$k] = ($v === null) ? 'NULL' : $v;
                    break;
            }
        }

        return $arrParams;
    }

    /**
     * Debug a query.
     *
     * @param \Database\Result $objResult An optional result object.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function debugQuery($objResult = null)
    {
        // No op.
    }

    /**
     * Explain the current query.
     *
     * @return string The explanation string.
     */
    public function explain()
    {
        return $this->explain_query();
    }

    // @codingStandardsIgnoreStart - methods that are not in camel case.
    /**
     * Prepare a query string and return it.
     *
     * @param string $strQuery The query string.
     *
     * @return string The modified query string.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function prepare_query($strQuery);

    /**
     * Escape a string.
     *
     * @param string $strString The unescaped string.
     *
     * @return string The escaped string.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function string_escape($strString);

    /**
     * Add limit and offset to the query string.
     *
     * @param integer $intRows   The maximum number of rows.
     *
     * @param integer $intOffset The number of rows to skip.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function limit_query($intRows, $intOffset);

    /**
     * Execute the query.
     *
     * @return resource The result resource.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function execute_query();

    /**
     * Return the last error message.
     *
     * @return string The error message.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function get_error();

    /**
     * Return the last insert ID.
     *
     * @return integer The last insert ID.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function affected_rows();

    /**
     * Return the last insert ID.
     *
     * @return integer The last insert ID.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function insert_id();

    /**
     * Explain the current query.
     *
     * @return array The information array.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function explain_query();
    // @codingStandardsIgnoreEnd - methods that are not in camel case.

    /**
     * Create a Database\Result object.
     *
     * @param resource $resResult The database result.
     *
     * @param string   $strQuery  The query string.
     *
     * @return \Database\Result The result object.
     */
    abstract protected function createResult($resResult, $strQuery);
}
