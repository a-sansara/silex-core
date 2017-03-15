<?php
namespace MetaTech\Db;

use PDO;
use MetaTech\Core\Singleton;

/*!
 * @package     MetaTech\Db
 * @class       PdoConnector
 * @extends     MetaTech\Core\Singleton
 * @author      a-Sansara
 * @date        2015-02-13 16:36:12 CET
 */
class PdoConnector extends Singleton
{
    /*! @protected @var [] $conn */
    protected $conn = array();
    /*! @protected @var string $currentProfile */
    protected $currentProfile;

    /*!
     * @private
     * @param       MetaTech\Db\Profile     $profile
     * @param       bool                    $recreate
     * @return \PDO
     */
    private function getPdo(Profile $profile, $recreate=false)
    {
        $name = $profile->getName();
        if ($recreate || !isset($this->conn[$name]) || $this->conn[$name] == null) {
            $this->setPdo($profile);
        }
        return $this->conn[$name];
    }

    /*!
     * @method      setCurrentProfile
     * @private
     * @param       str     $name
     */
    private function setCurrentProfile($name)
    {
        $this->currentProfile = $name;
    }

    /*!
     * @method      setPdo
     * @private
     * @param       MetaTech\Db\Profile     $profile
     */
    private function setPdo(Profile $profile)
    {
        $pdo = new PDO($profile->getDsn(), $profile->getUser(), $profile->getPassword());
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("SET NAMES '".$profile->getCharset()."'");
        $this->conn[$profile->getName()] = $pdo;
    }

    /*!
     * @method      switchDb
     * @public
     * @param       MetaTech\Db\Profile     $profile
     * @param       bool                    $recreate
     */
    public function switchDb(Profile $profile, $recreate=false)
    {
        $this->currentProfile = $profile->getName();
        $this->getPdo($profile, $recreate);
    }

    /*!
     * @method      conn
     * @public
     * @return      PDO
     */
    public function conn()
    {
        return $this->conn[$this->currentProfile];
    }
}
