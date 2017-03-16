<?php
/*
 * This file is part of the silex-core package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Db;

use MetaTech\Db\PdoConnector;
use MetaTech\Db\Profile;

/*!
 * Little Db utility to improve db interractions
 *
 * @package     Mtc\Db
 * @class       PdoWrapper
 * @author      a-Sansara
 * @date        2015-02-13 22:40:12 CET
 */
class PdoWrapper
{
    /*! @protected @var Monolog\Handler\StreamHandler $logger */
    protected $logger;
    /*! @protected @var MetaTech\Db\Profile $profile */
    protected $profile;
    /*! @protected @var $bypasslog */
    protected $bypasslog;

    /*!
     * @constructor
     * @public
     * @param       MetaTech\Db\Profile               $profile
     * @param       Monolog\Handler\StreamHandler     $logger
     */
    public function __construct(Profile $profile, $logger = null)
    {
        $this->profile = $profile;
        $this->logger = $logger;
        $this->switchDb($profile);
    }

    /*!
     * Return the PDO connection object
     * 
     * @method      getPdoConnection
     * @public
     * @return      PDO
     */
    public function getPdoConnection()
    {
        return PdoConnector::getInstance()->conn();
    }

    /*!
     * @method      switchDb
     * @public
     * @param       Mtc\Core\Db\Profile     $profile
     * @return      PDO
     */
    public function switchDb(Profile $profile = null, $recreate=false)
    {
        if (is_null($profile)) {
            $profile = $this->profile;
        }
        return PdoConnector::getInstance()->switchDb($profile, $recreate);
    }

    /*!
     * @method      getLogger
     * @public
     * @return      Monolog\Handler\StreamHandler
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /*!
     * @method      log
     * @private
     * @param       str     $query
     * @param       []      $data
     * @param       bool    $start
     */
    private function log($query, $data, $start=true, $forceLog=false)
    {
        if ($this->logger != null) {
            $minisql   = substr($query, 0, 35);
            $bypasslog = strpos(substr($query, 0, 35), 'SELECT')!==false;
            if (!$this->bypasslog || $forceLog) {
                $this->bypasslog = $bypasslog;
                if ($start) {
                    if (!$bypasslog || $forceLog) {
                        $this->logger->addDebug(" => ".str_pad("QUERY", 8, " ", STR_PAD_LEFT).'    '.preg_replace('/[ ]{2,}/', ' ', $query));
                        if( !empty($data)) $this->logger->addDebug(str_pad("PARAMS", 12, " ", STR_PAD_LEFT), $data);
                    }
                }
                else {
                    $this->logger->addDebug(" <= $query", $data);
                }
            }
            elseif (!$start) $this->bypasslog = false;
        }
    }

    /*!
     * execute a query and get Result Statement for the specified $data
     * 
     * @method      exec
     * @public
     * @param       str             $query
     * @param       []              $data
     * @param       int             $fetch
     * @return      PdoStatement
     */
    public function exec($query, $data = array(), $fetch = null, $forceLog=false)
    {
        $this->switchDb(null, true);
        $this->log($query, $data, true, $forceLog);
        if ($fetch == null) {
            $fetch = \PDO::FETCH_OBJ;
        }
        $stmt = $this->getPdoConnection()->prepare($query);
        if (is_array($data)) {
            foreach ($data as $cl => $f) {
                if (!is_null($f)) {
                    @$stmt->bindParam(':'.$cl, $data[$cl], ($cl == 'queryIndex' || $cl == 'queryLimit' ? \PDO::PARAM_INT : \PDO::PARAM_STR)); // don't use $f, cause value pass by reference
                }
                else {
                    $stmt->bindValue(':'.$cl, null, \PDO::PARAM_INT /* prefer to \PDO::PARAM_NULL for compat*/);
                }
            }
        }
        try {
            $stmt->execute();
            if ($fetch !== false) {
                $stmt->setFetchMode($fetch);
            }
            $rowCount     = $stmt!=null ? $stmt->rowCount() : 0;
            $lastInsertId = $this->getLastInsertId();
            
            $this->log(str_pad("RS", 8, " ", STR_PAD_LEFT), compact('rowCount', 'lastInsertId'), false, $forceLog);
        }
        catch(\Exception $e) {
            if (!is_null($this->logger)) {
                $this->bypasslog = false;
                $this->logger->addError($e->getMessage());
                foreach (preg_split('/#/', $e->getTraceAsString()) as $error) {
                    if (!empty($error)) {
                        $this->logger->addDebug("#$error");
                    }
                }
            }
            throw $e;
        }
        return $stmt;
    }

    /*!
     * get last insert id in db
     * 
     * @method      getLastInsertId
     * @public
     * @return      int
     */
    public function getLastInsertId()
    {
        return $this->getPdoConnection()->lastInsertId();
    }

    /*!
     * persist $data in table $table
     * 
     * @method      persist
     * @public
     * @param       str     $table
     * @param       []      $data
     * @param       bool    $updateOnDuplicate
     * @return      PdoStatement
     */
    public function persist($table, $data, $updateOnDuplicate = true)
    {
        if (isset($data['id']) && is_null($data['id'])) {
            unset($data['id']);
            $updateOnDuplicate = false;
        }
        $argnames  = array_keys($data);
        $updateDef = '';
        if ($updateOnDuplicate) {
            foreach ($argnames as $field) {
                $updateDef .= ($updateDef == '' ? '' : ',')." `$field` = VALUES(`$field`)";
            }
            $updateDef = "ON DUPLICATE KEY UPDATE $updateDef";
        }
        return $this->exec(
            "INSERT INTO $table (`".implode('`, `', $argnames).'`) VALUES (:'.implode(', :', $argnames).") $updateDef",
            $data
        );
    }

    /*!
     * get autoincrement
     * 
     * @method      nextIncrement
     * @public
     * @param       str     $table
     * @return      int
     */
    public function nextIncrement($table)
    {
        $data = $this->exec('SHOW TABLE STATUS WHERE `Name`= :table', compact('table'))->fetch();
        return $data != false ? $data->Auto_increment : null;
    }

    /*!
     * @method      encodeJsonBase64
     * @public
     * @static
     * @param       mixed $data
     * @return      str
     */
    public static function encodeJsonBase64($data)
    {
        return base64_encode(json_encode($data));
    }

    /*!
     * @method      decodeJsonBase64
     * @public
     * @static
     * @param       str         $data
     * @param       bool        $onlyb64
     * @return      stdclass
     */
    public static function decodeJsonBase64($data, $onlyb64 = false)
    {
        return $onlyb64 ? base64_decode($data) : json_decode(base64_decode($data));
    }

}
