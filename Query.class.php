<?php


declare(strict_types = 1);


final class Query
{
    /**
        * PDO query class to process prepared statement parameterised queries and reduce repetitive inline code.
        *
        * Allows flexible SQL statements and multiple database connections.
        *
        * Coded to PHP 7.1
        *
        * @author         Martin Latter <copysense.co.uk>
        * @copyright      Martin Latter, 27/11/2017
        * @version        0.08
        * @license        GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
        * @link           https://github.com/Tinram/MySQL-PDO.git
    */


    private static $sEOL = (PHP_SAPI === 'cli') ? PHP_EOL : '<br>';


    public function __construct()
    {
        echo __METHOD__ . '() ** WARNING ** this is a static class.' . self::$sEOL;
    }


    /**
        * Simple method for prepared statement SELECTs.
        *
        * @param   PDO $oConnection, database connection
        * @param   string $sQuery, SQL query, usually with parameter-placeholders
        * @param   array $aParamValues, parameter key-value pairs e.g. [':user_id' => $user_id]
        *           else null if no parameters used in query
        *           multiple instances of a parameter are bound from a single key
        * @param   bool $bFetchAll, true: fetch complete resultset; false: fetch just one row
        * @param   bool $bPlaceholders, false: skip binding of parameters if SQL query has none
        *
        * @return  array [ 'results' => array | false, 'numrows' => integer ]
    */

    public static function select(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null, bool $bFetchAll = true, bool $bPlaceholders = true): array
    {
        $bParamError = false;

        if (is_null($oConnection))
        {
            echo __METHOD__ . '(): $oConnection parameter is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (empty($sQuery))
        {
            echo __METHOD__ . '(): $sQuery SQL string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (stripos($sQuery, 'SELECT ') === false)
        {
            echo __METHOD__ . '(): SQL may be wrong - calling select method, but no SELECT keyword found in $sQuery.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
        }
        else if (empty($aParamValues) && $bPlaceholders)
        {
            echo __METHOD__ . '(): $aParamValues array to bind is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if ($bPlaceholders)
        {
            foreach ($aParamValues as $sParameter => $v)
            {
                if (strpos($sQuery, $sParameter) === false)
                {
                    $bParamError = true;
                    break;
                }
            }
        }

        if ($bParamError)
        {
            echo __METHOD__ . '(): bound parameter array values and SQL mismatch.' . self::$sEOL . '(' . __FILE__ . ')' . self::$sEOL;
        }

        $iNumRows = 0;

        try
        {
            $oStmt = $oConnection->prepare($sQuery);

            if ($bPlaceholders) # provides option to skip if query has no placeholders
            {
                foreach ($aParamValues as $param => &$value)
                {
                    $iPDOType = self::getPDOType($value);
                    $oStmt->bindParam($param, $value);
                }
            }

            $oStmt->execute();

            $iNumRows = $oStmt->rowCount();

            if ($bFetchAll)
            {
                $aResults = $oStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            else
            {
                $aResults = $oStmt->fetch(PDO::FETCH_ASSOC);
            }

            $oStmt->closeCursor();

            return [ 'results' => $aResults, 'numrows' => $iNumRows ];
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }


    /**
        * Simple main method shared between UPDATE, INSERT, and DELETE.
        *
        * @param   PDO $oConnection, database connection
        * @param   string $sQuery, SQL query with placeholders
        * @param   array $aParamValues, key-value pairs e.g. [':user_id' => $user_id]
        * @param   string $sAction, for aliases
        *
        * @return  array
    */

    public static function main(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null, string $sAction = ''): array
    {
        $sAction = explode('::', $sAction)[1];

        self::checkArgs($sAction, func_get_args());

        $iNumUpdates = 0;
        $bUpdate = false;
        $sError = null;

        try
        {
            $oStmt = $oConnection->prepare($sQuery);

            foreach ($aParamValues as $param => &$value)
            {
                $iPDOType = self::getPDOType($value);
                $oStmt->bindParam($param, $value, $iPDOType);
            }

            if ($oStmt->execute())
            {
                $iNumUpdates = $oStmt->rowCount();

                if ($iNumUpdates > 0)
                {
                    $bUpdate = true;
                }

                if ($sAction === 'insert')
                {
                    $iLastInsertID = $oConnection->lastInsertId();
                }
            }
            else
            {
                $sError = $oStmt->error;
            }

            $oStmt->closeCursor();

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
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }


    /**
        * Method for INSERT queries.
    */

    public static function insert(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null): array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__);
    }


    /**
        * Method for UPDATE queries.
    */

    public static function update(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null): array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__);
    }


    /**
        * Method for DELETE queries.
    */

    public static function delete(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null): array
    {
        return self::main($oConnection, $sQuery, $aParamValues, __METHOD__);
    }


    /**
        * Helper method to allocate PDO types for binding variables.
        *
        * @param   mixed $value
        * @return  integer, PDO constant
    */

    private static function getPDOType($value): int
    {
        $sVarType = gettype($value);
        $iPDOType = 0;

        switch ($sVarType)
        {
            case 'string':
            case 'float':
            case 'double':
                $iPDOType = PDO::PARAM_STR;
            break;

            case 'integer':
                $iPDOType = PDO::PARAM_INT;
            break;

            case 'boolean':
                $iPDOType = PDO::PARAM_BOOL;
            break;

            case 'NULL':
                $iPDOType = PDO::PARAM_NULL;
            break;

            default:
                die('Unrecognised PDO datatype in ' . __METHOD__ . '()' . self::$sEOL);
        }

        return $iPDOType;
    }


    /**
        * Helper method for erroneous arguments.
        *
        * @param   string $sMethodName, identifier of which method error occurred
        * @param   array $aArgs, arguments passed to invoked method
        * @return  void
    */

    private static function checkArgs(string $sMethodName = '', array $aArgs = []): void    /* remove :void for PHP 7.0 */
    {
        if (is_null($aArgs[0]))
        {
            echo $sMethodName . '(): $oConnection parameter is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (empty($aArgs[1]))
        {
            echo $sMethodName . '(): $sQuery SQL string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (empty($aArgs[2]))
        {
            echo $sMethodName . '(): $aParamValues array to bind is empty! (' . __FILE__ . ')' . self::$sEOL;
        }
        else if (empty($aArgs[3]))
        {
            echo $sMethodName . '(): $sAction string is empty! (' . __FILE__ . ')' . self::$sEOL;
        }

        $bParamError = false;

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
            if (strpos($aArgs[1], $sParameter) === false)
            {
                $bParamError = true;
                break;
            }
        }

        if ($bParamError)
        {
            echo __CLASS__ . '::' . $sMethodName . '(): bound parameter array values and SQL mismatch. (' . __FILE__ . ')' . self::$sEOL;
        }
    }
}
