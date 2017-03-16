<?php
/*
 * This file is part of the silex-core package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Core;

/*!
 * Singleton Pattern
 *
 * @package     Mtc\Core
 * @class       Singleton
 * @author      a-Sansara
 * @date        2014-11-05 23:45:12 CET
 */
class Singleton
{
    /*! @protected @static @var $_instance the class instance */
    protected static $_instance;

    /*!
     * @constructor
     * @protected
     */
    protected function __construct()
    {
    }

    /*!
     * @method      __clone
     * @protected
     */
    protected function __clone()
    {
    }

    /*!
     * get the class instance
     *
     * @method      getInstance
     * @public
     * @static
     * @return      Singleton
     */
    public static function getInstance()
    {
        if (!(static::$_instance instanceof static)) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }
}
