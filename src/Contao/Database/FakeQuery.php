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

/**
 * A fake DB query.
 */
class FakeQuery
{
    /**
     * The SQL code.
     *
     * @var string
     */
    protected $sql;

    /**
     * The parameters (if any).
     *
     * @var array
     */
    protected $parameters;

    /**
     * Set the result.
     *
     * @var FakeResult
     */
    protected $result;

    /**
     * The error message.
     *
     * @var string
     */
    protected $error;

    /**
     * Local cached copy of the compiled query.
     *
     * @var string
     */
    protected $compiledQuery;

    /**
     * Create a new instance.
     *
     * @param null $sql Optional SQL query.
     */
    public function __construct($sql = null)
    {
        if ($sql) {
            $this->sql = $sql;
        }
    }

    /**
     * Set parameters.
     *
     * @param mixed $param1 The first parameter.
     *
     * @param mixed $_      One or many parameters.
     *
     * @return FakeQuery
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.CamelCaseParameterName)
     */
    public function with($param1, $_ = null)
    {
        $this->parameters = func_get_args();

        return $this;
    }

    /**
     * Set the result.
     *
     * @param FakeResult $result The result object.
     *
     * @return FakeQuery
     */
    public function will(FakeResult $result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Create and set a fake result.
     *
     * @return FakeResult
     */
    public function result()
    {
        if (!$this->result) {
            $this->will(new FakeResult());
        }

        return $this->result;
    }

    /**
     * Mark the query to fail with an error.
     *
     * @param string $error The error message.
     *
     * @return FakeQuery
     */
    public function willFailWith($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Retrieve the error message.
     *
     * @return string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Escape a string.
     *
     * @param string $strString The unescaped string.
     *
     * @return string The escaped string
     */
    protected function stringEscape($strString)
    {
        return '\'' . str_replace('\'', '\\\'', $strString) . '\'';
    }

    /**
     * Escape the parameters and return them.
     *
     * @return array
     */
    protected function escapeParameters()
    {
        $parameters = $this->parameters;
        foreach ($parameters as $k => $v) {
            switch (gettype($v)) {
                case 'string':
                    $parameters[$k] = $this->stringEscape($v);
                    break;

                case 'boolean':
                    $parameters[$k] = ($v === true) ? 1 : 0;
                    break;

                case 'object':
                    $parameters[$k] = $this->stringEscape(serialize($v));
                    break;

                case 'array':
                    $parameters[$k] = $this->stringEscape(serialize($v));
                    break;

                default:
                    $parameters[$k] = ($v === null) ? 'NULL' : $v;
                    break;
            }
        }

        return $parameters;
    }

    /**
     * Compile the query into a string.
     *
     * @return void
     *
     * @throws \Exception When the arguments do not match the query.
     */
    protected function compileQuery()
    {
        $query = $this->sql;

        // Auto-generate the SET/VALUES subpart
        if (strncasecmp($query, 'INSERT', 6) === 0 || strncasecmp($query, 'UPDATE', 6) === 0) {
            $query = str_replace('%s', '%p', $query);
        }

        // Replace wildcards
        $chunks = preg_split("/('[^']*')/", $query, -1, (PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY));

        foreach ($chunks as $k => $v) {
            if (substr($v, 0, 1) == "'") {
                continue;
            }

            $chunks[$k] = str_replace('?', '%s', $v);
        }

        $query = implode('', $chunks);


        $query = preg_replace('/(?<!%)%([^bcdufosxX%])/', '%%$1', $query);

        // Replace wildcards
        // @codingStandardsIgnoreStart
        if (($this->compiledQuery = @vsprintf($query, $this->escapeParameters())) == false) {
        // @codingStandardsIgnoreEnd
            throw new \Exception('Too few arguments to build the query string');
        }
    }

    /**
     * Check if the query matches against the query and parameters.
     *
     * @param string $sqlQuery The query to match against.
     *
     * @return bool
     */
    public function matches($sqlQuery)
    {
        if (empty($this->compiledQuery)) {
            $this->compileQuery();
        }

        return $sqlQuery === $this->compiledQuery;
    }
}
