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
        * @version        0.04
        * @license        GNU GPL version 3.0 (GPL v3); http://www.gnu.org/licenses/gpl.html
        * @link           https://github.com/Tinram/MySQL-PDO.git
    */


    public function __construct() {
        echo '<p style="#c00">Warning: this is a static class.</p>';
    }


    /**
        * Simple method for prepared statement SELECTs.
        *
        * @param   PDO $oConnection, database connection
        * @param   string $sQuery, SQL query, usually with placeholders
        * @param   array $aParamValues, parameter key-value pairs e.g. [':user_id' => $user_id]
        *           else null if no parameters used in query
        *           multiple instances of a parameter are bound from a single key
        * @param   bool $bFetchAll, true: fetch complete resultset; false: fetch just one row
        * @param   bool $aPlaceholders, false: skip binding of parameters if query has none
        *
        * @return  array [ 'results' => array | false, 'numrows' => integer ]
    */

    public static function select(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null, bool $bFetchAll = true, $aPlaceholders = true): array
    {
        self::checkArgs(__METHOD__, func_get_args());

        if (empty($aParamValues) && $aPlaceholders)
        {
            die(__METHOD__ . '(): $aParamValues array to bind is empty!<br>(' . __FILE__ . ')');
        }

        $iNumRows = 0;

        try
        {
            $oStmt = $oConnection->prepare($sQuery);

            if ($aPlaceholders) # provides option to skip if query has no placeholders
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
        * @param   array $aParamValues, key-value pairs e.g. [':user_id' => $user_id], else null
        * @param   string $sAction, for aliases
        *
        * @return  array
    */

    public static function update(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null, string $sAction = 'update'): array
    {
        self::checkArgs(__METHOD__, func_get_args());

        if (empty($aParamValues))
        {
            die(__METHOD__ . '(): $aParamValues array to bind is empty!<br>(' . __FILE__ . ')');
        }

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
        * Alias of self::update() method for insert().
    */

    public static function insert(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null): array
    {
        return self::update($oConnection, $sQuery, $aParamValues, 'insert');
    }


    /**
        * Alias of self::update() method for delete().
    */

    public static function delete(PDO &$oConnection = null, string $sQuery = '', array $aParamValues = null): array
    {
        return self::update($oConnection, $sQuery, $aParamValues, 'delete');
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
                die('Unrecognised PDO datatype in ' . __METHOD__ . '()');
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

    private static function checkArgs(string $sMethodName = '', array $aArgs = []): void
    {
        if (is_null($aArgs[0]))
        {
            die($sMethodName . '(): $oConnection parameter is empty!<br>(' . __FILE__ . ')');
        }
        else if (empty($aArgs[1]))
        {
            die($sMethodName . '(): $sQuery SQL string is empty!<br>(' . __FILE__ . ')');
        }
    }

}
