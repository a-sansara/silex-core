<?php
/*
 * This file is part of the silex-core package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Silex\Ctrl;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/*!
 * @package     MetaTech\silex\Ctrl
 * @class       Base
 * @abstract
 * @implements  Silex\Api\ControllerProviderInterface
 * @author      a-Sansara
 * @date        2017-03-12 15:34:26 CET
 */
abstract class Base implements ControllerProviderInterface
{
    const PRIORITY = Application::EARLY_EVENT;
    const NS       = 'ctrl.';

    /*!
     * @constrcutor
     * @param       Silex\Application   $silex
     */
    public function __construct(Application $app = null)
    {

    }

    public function ns()
    {
        return static::NS . (new \ReflectionClass(static::class))->getShortName();
    }

    /*!
     * @method      init
     * @public
     * @param       Silex\Application   $app
     */
    public function before(Request $request, Application $app)
    {

    }

    /*!
     * @method      connect
     * @public
     * @param       Silex\Application   $app
     * @return      Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $collection = $app['controllers_factory'];
        $ctrl       = $this;
        $collection->before(function(Request $request, Application $app) use ($ctrl) {
            return $ctrl->before($request, $app);
        }, static::PRIORITY);
        //~ var_dump($collection);
        return $this->routing($collection);
    }
}
