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
class Statement extends \Contao\Database\Statement
{
    /**
     * The database.
     *
     * @var Database
     */
    protected $database;

    /**
     * {@inheritdoc}
     */
    public function __construct($database, $blnDisableAutocommit = false)
    {
        $this->database             = $database;
        $this->blnDisableAutocommit = $blnDisableAutocommit;
    }

    // @codingStandardsIgnoreStart - We have methods here that are not in camel case.
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function prepare_query($strQuery)
    {
        return $strQuery;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function string_escape($strString)
    {
        return '\'' . str_replace('\'', '\\\'', $strString) . '\'';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function limit_query($intRows, $intOffset)
    {
        if (strncasecmp($this->strQuery, 'SELECT', 6) === 0) {
            $this->strQuery .= ' LIMIT ' . $intOffset . ',' . $intRows;
        } else {
            $this->strQuery .= ' LIMIT ' . $intRows;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function execute_query()
    {
        $query = $this->database->getQueryCollection()->findQuery($this->strQuery);

        if ($query) {
            return $query->result();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function get_error()
    {
        $query = $this->database->getQueryCollection()->findQuery($this->strQuery);

        if ($query) {
            return $query->error();
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function affected_rows()
    {
        throw new \RuntimeException('currently unsupported in test suite.');
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function insert_id()
    {
        $query = $this->database->getQueryCollection()->findQuery($this->strQuery);

        if ($query) {
            return $query->result()->getRow($query->result()->count());
        }

        return null;
    }

    /**
     * Explain the current query.
     *
     * @return array The information array.
     *
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    protected function explain_query()
    {
        return array();
    }
    // @codingStandardsIgnoreEnd - methods that are not in camel case.

    /**
     * Create a Database\Result object.
     *
     * @param FakeResult $resResult The database result.
     *
     * @param string     $strQuery  The query string.
     *
     * @return \Database\Result The result object
     */
    protected function createResult($resResult, $strQuery)
    {
        return new Result($this->database, $resResult, $strQuery);
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
        // No op. We have to override to silence the debugging.
    }
}
