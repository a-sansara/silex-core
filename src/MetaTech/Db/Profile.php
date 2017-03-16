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

/*!
 * Db Profile
 *
 * @package     MetaTech\Db
 * @class       Profile
 * @author      a-Sansara
 * @date        2014-02-13 12:30:12 CET
 */
class Profile
{
    /*! @public @var $config */
    private $config;

    /*!
     * @constructor
     * @public
     * @param       [assoc]     $config
     */
    public function __construct(array $config = [])
    {
        if (is_array($config) && !empty($config)) {
            $this->config = $config;
        } else {
            throw new \Exception("$config must be associative array");
        }
    }
    /*!
     * @method      getName
     * @public
     * @return      str
     */
    public function getName()
    {
        return !isset($this->config['name']) ? $this->config['dbname'] : $this->config['name'];
    }

    /*!
     * @method      getUser
     * @public
     * @return      str
     */
    public function getUser()
    {
        return $this->config['user'];
    }

    /*!
     * @method      getPassword
     * @public
     * @return      str
     */
    public function getPassword()
    {
        return $this->config['password'];
    }

    /*!
     * @method      getCharset
     * @public
     * @return      str
     */
    public function getCharset()
    {
        return $this->config['charset'];
    }

    /*!
     * @method      getDsn
     * @public
     * @return      str
     */
    public function getDsn()
    {
        return 'mysql:host='.$this->config['host'].';port=3306;dbname='.$this->config['dbname'];
    }
}
