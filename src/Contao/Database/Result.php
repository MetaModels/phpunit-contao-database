<?php

/**
 * This file is part of MetaModels/phpunit-contao-database.
 *
 * (c) 2012-2015 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels
 * @subpackage Tests
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2012-2016 The MetaModels team.
 * @license    https://github.com/MetaModels/phpunit-contao-database/blob/master/LICENSE LGPL-3.0
 * @filesource
 */

namespace MetaModels\Test\Contao\Database;

use MetaModels\Test\Contao\Database;

/**
 * A database result.
 */
class Result extends \Contao\Database\Result
{
    /**
     * The database.
     *
     * @var Database
     */
    protected $database;

    /**
     * The query that was executed.
     *
     * @var FakeQuery
     */
    protected $query;

    /**
     * The result data.
     *
     * @var FakeResult
     */
    protected $result;

    /**
     * The current cursor.
     *
     * @var int
     */
    protected $cursor;

    /**
     * Create a Database\Result object.
     *
     * @param resource   $database  The connection resource.
     *
     * @param FakeResult $resResult The database result.
     *
     * @param string     $strQuery  The query string.
     */
    public function __construct($database, $resResult, $strQuery)
    {
        $this->database = $database;
        $this->result   = $resResult;
        $this->query    = $this->database->getQueryCollection()->findQuery($strQuery);
        $this->cursor   = 0;
    }

    // @codingStandardsIgnoreStart - We have methods here that are not in camel case.
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function fetch_row()
    {
        return $this->result->getRow($this->cursor);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function fetch_assoc()
    {
        return $this->result->getRow($this->cursor++);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function num_rows()
    {
        return $this->result->count();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function num_fields()
    {
        return count($this->result->getRow(0));
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function fetch_field($intOffset)
    {
        $row  = $this->result->getRow($this->cursor);
        $keys = array_keys($row);

        return $row[$keys[$intOffset]];
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException If $intIndex is out of bounds
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function data_seek($intIndex)
    {
        if ($intIndex < 0) {
            throw new \OutOfBoundsException('Invalid index ' . $intIndex . ' (must be >= 0)');
        }

        $intTotal = $this->num_rows();

        if ($intTotal <= 0) {
            // see #6319
            return;
        }

        if ($intIndex >= $intTotal) {
            throw new \OutOfBoundsException('Invalid index ' . $intIndex . '(only $intTotal rows in the result set)');
        }

        $this->cursor = $intIndex;
    }
    // @codingStandardsIgnoreEnd - methods that are not in camel case.

    /**
     * {@inheritdoc}
     */
    public function free()
    {
        // No op.
    }
}
