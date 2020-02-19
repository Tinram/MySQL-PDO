<?php

declare(strict_types=1);


final class Query
{
    /**
        * MySQLi query class to process prepared statement parameterised queries and reduce repetitive inline code.
        *
        * Allows flexible SQL statements and multiple database connections.
        *
        * Coded to PHP 7.2
        *
        * @author         Martin Latter
        * @copyright      Martin Latter, 27/11/2017
        * @version        0.12b
        * @license        GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
        * @link           https://github.com/Tinram/MySQL-PDO.git
    */


    /** @const EXTENDED_DEBUG, toggle statement object dump - useful for connection issues */
    const EXTENDED_DEBUG = false;

    /** @var string $sEOL, EOL type */
    private static $sEOL = (PHP_SAPI === 'cli') ? PHP_EOL : '<br>';


    public function __construct()
    {
        echo __METHOD__ . '() ** WARNING ** this is a static class.' . self::$sEOL;
    }


    /**
        * Simple method for prepared statement SELECTs.
        *
        * @param   mysqli $oConnection, database connection
        * @param   string $sQuery, SQL query, usually with parameter-placeholders
        * @param   array<mixed> $aParamValues, [$user_id, ...]
        *           else null if no parameters used in query
        * @param   bool $bFetchAll, true: fetch complete resultset; false: fetch just one row
        * @param   bool $bPlaceholders, false: skip binding of parameters if SQL query has none
        * @param   bool $bDebug, toggle query debugging information
        *
        * @return  array<mixed>|null [ 'results' => array | false, 'numrows' => integer ]
    */

    public static function select(mysqli &$oConnection = null, string $sQuery = '', array $aParamValues, bool $bFetchAll = true, bool $bPlaceholders = true, bool $bDebug = false): ?array
    {
        $aParamErrors = [];
        $aResults = [];

        if (is_null($oConnection))
        {
            echo __METHOD__ . '(): $oConnection parameter is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($sQuery === '')
        {
            echo __METHOD__ . '(): $sQuery SQL string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (stripos($sQuery, 'SELECT ') === false)
        {
            echo __METHOD__ . '(): SQL may be wrong - calling select method, but no SELECT keyword found in $sQuery.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($bPlaceholders && (count($aParamValues) === 0))
        {
            echo __METHOD__ . '(): $aParamValues array to bind is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($bPlaceholders)
        {
            foreach ($aParamValues as $sParameter => $v)
            {
                $iPH = preg_match_all('/\?/', $sQuery, $aM);

                if (sizeof($aM[0]) !== sizeof($aParamValues))
                {
                    $aParamErrors[] = join(',', $aM[0]) . ' | ' . join(',', $aParamValues);
                }
            }
        }

        if (count($aParamErrors) !== 0)
        {
            echo __METHOD__ . '(): bound parameter array values and SQL mismatch.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
            echo 'erroneous parameters: ' . join(self::$sEOL, $aParamErrors) . self::$sEOL . self::$sEOL;
        }

        if ($bDebug || self::EXTENDED_DEBUG)
        {
            echo __METHOD__ . '(DEBUG)' . self::$sEOL;
            echo self::$sEOL . $sQuery . self::$sEOL . self::$sEOL;
            print_r($aParamValues);
            echo self::$sEOL;
        }

        $oStmt = $oConnection->stmt_init();
        $oStmt->prepare($sQuery);

        if ($bPlaceholders)
        {
            $aBindParams = [];
            $aBindParams[0] = '';

            foreach ($aParamValues as $param => $v)
            {
                $aBindParams[0] .= self::getMySQLiType($v);
                $aBindParams[] = &$aParamValues[$param];
            }

            call_user_func_array( [$oStmt, 'bind_param'], $aBindParams ); # obj, fn()
        }

        $oStmt->execute();

        if (self::EXTENDED_DEBUG)
        {
            var_dump($oStmt);
        }

        $oResults = $oStmt->get_result();
        $iNumRows = $oResults->num_rows;

        if ($bFetchAll)
        {
            $aResults = $oResults->fetch_all(MYSQLI_ASSOC);
        }
        else
        {
            $aResults = $oResults->fetch_assoc();
        }

        $oStmt->free_result();
        $oStmt->close();

        return [ 'results' => $aResults, 'numrows' => $iNumRows ];
    }


    /**
        * Simple main method shared between UPDATE, INSERT, and DELETE.
        *
        * @param   mysqli $oConnection, database connection
        * @param   string $sQuery, SQL query with placeholders
        * @param   array<mixed> $aParamValues, [$user_id, ...]
        * @param   string $sAction, for aliases
        * @param   bool $bDebug, toggle query debugging information
        *
        * @return  array<mixed>|null
    */

    public static function main(mysqli &$oConnection = null, string $sQuery = '', array $aParamValues, string $sAction = '', bool $bDebug = false): ?array
    {
        $sAction = explode('::', $sAction)[1];

        self::checkArgs($sAction, func_get_args());

        $iNumUpdates = 0;
        $bUpdate = false;
        $sError = null;

        $oStmt = $oConnection->stmt_init();
        $oStmt->prepare($sQuery);

        $aBindParams = [];
        $aBindParams[0] = '';

        foreach ($aParamValues as $param => $v)
        {
             $aBindParams[0] .= self::getMySQLiType($v);
             $aBindParams[] = &$aParamValues[$param];
        }

        call_user_func_array( [$oStmt, 'bind_param'], $aBindParams );

        if ($oStmt->execute())
        {
            $iNumUpdates = $oStmt->affected_rows;

            if ($iNumUpdates > 0)
            {
                $bUpdate = true;
            }

            if ($sAction === 'insert')
            {
                $iLastInsertID = $oStmt->insert_id;
            }
        }
        else
        {
            $sError = $oStmt->error;
        }

        $oStmt->close();

        if ($sAction === 'update')
        {
            return [ 'update' => $bUpdate, 'numupdates' => $iNumUpdates, 'error' => $sError ];
        }
        else if ($sAction === 'insert')
        {
            return [ 'insert' => $bUpdate, 'numinserts' => $iNumUpdates, 'insertid' => $iLastInsertID, 'error' => $sError ];
        }
        else if ($sAction === 'delete')
        {
            return [ 'delete' => $bUpdate, 'numdeletes' => $iNumUpdates, 'error' => $sError ];
        }
    }


    /**
        * Method for INSERT queries.
        *
        * @param   mysqli $oConnection, database connection
        * @param   string $sQuery, SQL query with placeholders
        * @param   array<mixed> $aParamValues, parameter values
        * @param   bool $bDebug, toggle query debugging information
        *
        * @return  array<mixed>|null
    */

    public static function insert(mysqli &$oConnection = null, string $sQuery = '', array $aParamValues, bool $bDebug = false): ?array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__, $bDebug);
    }


    /**
        * Method for UPDATE queries.
        *
        * @param   mysqli $oConnection, database connection
        * @param   string $sQuery, SQL query with placeholders
        * @param   array<mixed> $aParamValues, parameter values
        * @param   bool $bDebug, toggle query debugging information
        *
        * @return  array<mixed>|null
    */

    public static function update(mysqli &$oConnection = null, string $sQuery = '', array $aParamValues, bool $bDebug = false): ?array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__, $bDebug);
    }


    /**
        * Method for DELETE queries.
        *
        * @param   mysqli $oConnection, database connection
        * @param   string $sQuery, SQL query with placeholders
        * @param   array<mixed> $aParamValues, parameter values
        * @param   bool $bDebug, toggle query debugging information
        *
        * @return array<mixed>|null
    */

    public static function delete(mysqli &$oConnection = null, string $sQuery = '', array $aParamValues, bool $bDebug = false): ?array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__, $bDebug);
    }


    /**
        * Helper method to allocate data-types for binding variables.
        *
        * @param   mixed $value
        *
        * @return  string, mysqli type
    */

    private static function getMySQLiType($value): string
    {
        $sVarType = gettype($value);
        $sMType = '';

        switch ($sVarType)
        {
            case 'string':
                $sMType = 's';
            break;

            case 'integer':
                $sMType = 'i';
            break;

            case 'float':
            case 'double':
                $sMType = 'd';
            break;

            case 'binary':
                $sMType = 'b';
            break;

            default:
                die('Unrecognised mysqli data-type in ' . __METHOD__ . '()' . self::$sEOL);
        }

        return $sMType;
    }


    /**
        * Helper method for erroneous arguments.
        *
        * @param   string $sMethodName, identifier of which method error occurred
        * @param   array<mixed> $aArgs, arguments passed to invoked method
        *
        * @return  void
    */

    private static function checkArgs(string $sMethodName = '', array $aArgs = []): void
    {
        if (is_null($aArgs[0]))
        {
            echo __CLASS__ . '::' . $sMethodName . '(): $oConnection parameter is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($aArgs[1] === '')
        {
            echo __CLASS__ . '::' . $sMethodName . '(): $sQuery SQL string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (count($aArgs[2]) === 0)
        {
            echo __CLASS__ . '::' . $sMethodName . '(): $aParamValues array to bind is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($aArgs[3] === '')
        {
            echo __CLASS__ . '::' . $sMethodName . '(): $sAction string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }

        $aParamErrors = [];

        switch ($sMethodName)
        {
            case 'insert':
                if (stripos($aArgs[1], 'INSERT ') === false)
                {
                    echo __CLASS__ . '::' . $sMethodName . '(): SQL may be wrong - calling insert method, but no INSERT keyword found in $sQuery.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
                }
            break;

            case 'update':
                if (stripos($aArgs[1], 'UPDATE ') === false)
                {
                    echo __CLASS__ . '::' . $sMethodName . '(): SQL may be wrong - calling update method, but no UPDATE keyword found in $sQuery.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
                }
            break;

            case 'delete':
                if (stripos($aArgs[1], 'DELETE ') === false)
                {
                    echo __CLASS__ . '::' . $sMethodName . '(): SQL may be wrong - calling delete method, but no DELETE keyword found in $sQuery.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
                }
            break;

            default:
                echo __CLASS__ . '::' . $sMethodName . '(): unrecognised action!';
        }

        foreach ($aArgs[2] as $sParameter => $v)
        {
            $iPH = preg_match_all('/\?/', $aArgs[1], $aM);

            if (sizeof($aM[0]) !== sizeof($aArgs[2]))
            {
                $aParamErrors[] = join(',', $aM[0]) . ' | ' . join(',', $aArgs[2]);
            }
        }

        if (count($aParamErrors) !== 0)
        {
            echo __CLASS__ . '::' . $sMethodName . '(): bound parameter number and SQL mismatch.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
            echo 'erroneous parameters: ' . join(self::$sEOL, $aParamErrors) . self::$sEOL . self::$sEOL;
        }

        if ($aArgs[4] === true)
        {
            echo __CLASS__ . '::' . $sMethodName . '(DEBUG)' . self::$sEOL;
            echo self::$sEOL . $aArgs[1] . self::$sEOL . self::$sEOL;
            print_r($aArgs[2]);
            echo self::$sEOL;
        }
    }
}
