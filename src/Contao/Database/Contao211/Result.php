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
 * Class Database_Result.
 *
 * Provide methods to handle a database result.
 *
 * @copyright  Leo Feyer 2005-2013
 * @author     Leo Feyer <https://contao.org>
 * @package    Library
 */
abstract class Result
{
    /**
     * Current result.
     *
     * @var resource
     */
    protected $resResult;

    /**
     * Corresponding query string.
     *
     * @var string
     */
    protected $strQuery;

    /**
     * Current index.
     *
     * @var integer
     */
    private $intIndex = -1;

    /**
     * Current row index.
     *
     * @var integer
     */
    private $intRowIndex = -1;

    /**
     * End indicator.
     *
     * @var boolean
     */
    private $blnDone = false;

    /**
     * Remember modifications.
     *
     * @var boolean
     */
    private $blnModified = false;

    /**
     * Result cache array.
     *
     * @var array
     */
    protected $arrCache = array();

    /**
     * Validate the connection resource and store the query string.
     *
     * @param resource $resResult The database result.
     *
     * @param string   $strQuery  The query string..
     *
     * @throws \Exception If $resResult is not a valid resource.
     */
    public function __construct($resResult, $strQuery)
    {
        if (!is_resource($resResult) && !is_object($resResult)) {
            throw new \Exception('Invalid result resource');
        }

        $this->resResult = $resResult;
        $this->strQuery  = $strQuery;
    }

    /**
     * Automatically free the result.
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Set a particular field of the current row.
     *
     * @param mixed  $strKey   The field name.
     *
     * @param string $varValue The field value.
     *
     * @return void
     */
    public function __set($strKey, $varValue)
    {
        if ($this->intIndex < 0) {
            $this->first();
        }

        $this->blnModified = true;

        $this->arrCache[$this->intIndex][$strKey] = $varValue;
    }

    /**
     * Return an object property or a field of the current row.
     *
     * Supported parameters:
     *
     * * query:      the corresponding query string.
     * * numRows:    the number of rows of the current result.
     * * numFields:  the number of fields of the current result.
     * * isModified: true if the row has been modified.
     *
     * @param string $strKey The field name.
     *
     * @return mixed|null The field value or null.
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'query':
                return $this->strQuery;

            case 'numRows':
                return $this->num_rows();

            case 'numFields':
                return $this->num_fields();

            case 'isModified':
                return $this->blnModified;

            default:
                if ($this->intIndex < 0) {
                    $this->first();
                }
                if (isset($this->arrCache[$this->intIndex][$strKey])) {
                    return $this->arrCache[$this->intIndex][$strKey];
                }
                return null;
        }
    }

    /**
     * Fetch the current row as enumerated array.
     *
     * @return array|false The row as enumerated array or false if there is no row.
     */
    public function fetchRow()
    {
        if (!isset($this->arrCache[++$this->intIndex])) {
            if (($arrRow = $this->fetch_row()) == false) {
                --$this->intIndex;
                return false;
            }

            $this->arrCache[$this->intIndex] = $arrRow;
        }

        return array_values($this->arrCache[$this->intIndex]);
    }

    /**
     * Fetch the current row as associative array.
     *
     * @return array|false The row as associative array or false if there is no row.
     */
    public function fetchAssoc()
    {
        if (!isset($this->arrCache[++$this->intIndex])) {
            if (($arrRow = $this->fetch_assoc()) == false) {
                --$this->intIndex;
                return false;
            }

            $this->arrCache[$this->intIndex] = $arrRow;
        }

        return $this->arrCache[$this->intIndex];
    }

    /**
     * Fetch a particular field of each row of the result.
     *
     * @param string $strKey The field name.
     *
     * @return array An array of field values.
     */
    public function fetchEach($strKey)
    {
        $arrReturn = array();
        $this->fetchAllAssoc();

        foreach ($this->arrCache as $arrRow) {
            $arrReturn[] = $arrRow[$strKey];
        }

        return $arrReturn;
    }

    /**
     * Fetch all rows as associative array.
     *
     * @return array An array with all rows.
     */
    public function fetchAllAssoc()
    {
        do {
            $blnHasNext = $this->fetchAssoc();
        } while ($blnHasNext);

        return $this->arrCache;
    }

    /**
     * Get the column information and return it as array.
     *
     * @param integer $intOffset The field offset.
     *
     * @return array An array with the column information.
     */
    public function fetchField($intOffset = 0)
    {
        $arrFields = $this->fetch_field($intOffset);

        if (is_object($arrFields)) {
            $arrFields = get_object_vars($arrFields);
        }

        return $arrFields;
    }

    /**
     * Go to the first row of the current result.
     *
     * @return \Database\Result|boolean The result object or false if there is no first row.
     */
    public function first()
    {
        if (!$this->arrCache) {
            $this->arrCache[++$this->intRowIndex] = $this->fetchAssoc();
        }

        $this->intIndex = 0;
        return $this;
    }

    /**
     * Go to the next row of the current result.
     *
     * @return \Database\Result|boolean The result object or false if there is no next row.
     */
    public function next()
    {
        if ($this->blnDone) {
            return false;
        }

        if (!isset($this->arrCache[++$this->intIndex])) {
            // see #3762
            --$this->intIndex;

            if (($arrRow = $this->fetchAssoc()) == false) {
                $this->blnDone = true;
                return false;
            }

            $this->arrCache[$this->intIndex] = $arrRow;
            ++$this->intRowIndex;

            return $this;
        }

        return $this;
    }

    /**
     * Go to the previous row of the current result.
     *
     * @return \Database\Result|boolean The result object or false if there is no previous row.
     */
    public function prev()
    {
        if ($this->intIndex < 1) {
            return false;
        }

        --$this->intIndex;
        return $this;
    }

    /**
     * Go to the last row of the current result.
     *
     * @return \Database\Result|boolean The result object or false if there is no last row.
     */
    public function last()
    {
        if (!$this->blnDone) {
            $this->arrCache = $this->fetchAllAssoc();
        }

        $this->blnDone  = true;
        $this->intIndex = ($this->intRowIndex = count($this->arrCache) - 1);

        return $this;
    }

    /**
     * Return the current row as associative array.
     *
     * @param boolean $blnFetchArray If true, an enumerated array will be returned.
     *
     * @return array The row as array.
     */
    public function row($blnFetchArray = false)
    {
        if ($this->intIndex < 0) {
            $this->first();
        }

        return $blnFetchArray ? array_values($this->arrCache[$this->intIndex]) : $this->arrCache[$this->intIndex];
    }

    /**
     * Reset the current result.
     *
     * @return \Database\Result The result object.
     */
    public function reset()
    {
        $this->intIndex = -1;
        $this->blnDone  = false;
        return $this;
    }

    // @codingStandardsIgnoreStart - camel case method names.
    /**
     * Fetch the current row as enumerated array.
     *
     * @return array The row as array.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function fetch_row();

    /**
     * Fetch the current row as associative array.
     *
     * @return array The row as associative array.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function fetch_assoc();

    /**
     * Return the number of rows in the result set.
     *
     * @return integer The number of rows.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function num_rows();

    /**
     * Return the number of fields of the result set.
     *
     * @return integer The number of fields.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function num_fields();

    /**
     * Get the column information and return it as array.
     *
     * @param integer $intOffset The field offset.
     *
     * @return array An array with the column information.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    abstract protected function fetch_field($intOffset);
    // @codingStandardsIgnoreEnd - camel case method names.
}
