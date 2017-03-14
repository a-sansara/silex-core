<?php
/*
 * This file is part of the silex-core package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Silex;

use Silex\Application as BaseApplication;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;

/*!
 * @package     MetaTech\Silex
 * @class       Application
 * @extends     Silex\Application
 * @author      a-Sansara
 * @date        2017-03-12 21:46:43 CET
 */
class Application extends BaseApplication
{
    /*!
     * @@constrcutor
     * @public
     * @param       []  $values
     */
    public function __construct(array $values = array())
    {   
        parent::__construct();
        foreach ($values as $k => $v) {
            $this[$k] = $v;
        }
        $this->setProviders();
        $this->setServices();
        $this->setGlobals();
        $this->routingDefinition();
    }

    /*!
     * @method      setProviders
     * @protected
     */
    protected function setProviders()
    {
        $this->register(new ConfigServiceProvider('config'), [
            'config.dir'    => $this['path'].'/config/', 
            'config.format' => '%key%.yml'
        ]);
        $this->register(new SessionServiceProvider());
        $this->register(new ServiceControllerServiceProvider());
    }

    /*!
     * @method      setServices
     * @protected
     */
    protected function setServices()
    {

    }

    /*!
     * @method      setGlobals
     * @protected
     */
    protected function setGlobals()
    {
        $this['debug'] = boolval($this['config']['main']['env']['debug']);
    }

    /*!
     * @method      routingDefinition
     * @protected
     */
    protected function routingDefinition()
    {

    }
}
