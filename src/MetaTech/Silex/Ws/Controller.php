<?php
/*
 * This file is part of the silex-core package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Silex\Ws;

use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use MetaTech\Silex\Ctrl\Base;
use MetaTech\Silex\Ws\Authentication;

/*!
 * @package     MetaTech\Silex\Ws
 * @class       Controller
 * @extends     MetaTech\Core\Ctrl\Base
 * @author      a-Sansara
 * @date        2017-03-12 15:39:30 CET
 */
class Controller extends Base
{
    /*! @protected @var MetaTech\Core\Ws\Authentication $handler */
    protected $handler;
    /*! @protected @var Symfony\Component\HttpFoundation\Session\Session $session */
    protected $session;

    /*!
     * @constrcutor
     * @public
     * @param       Silex\Application   $app
     */
    public function __construct(Application $app = null)
    {
        $this->session = $app['session'];
        $this->handler = new Authentication($this->session, $app['ws.authenticator']);
    }

    /*!
     * @method      response
     * @public
     * @param       bool    $done
     * @param       str     $msg
     * @param       []      $data
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function response($done = false, $msg = "fail", $data = null)
    {
        if (is_null($data)) {
            unset($data);
        }
        $response = new JsonResponse(compact('done', 'msg', 'data'), 200);
        return $response;
    }

    /*!
     * @method      before
     * @public
     * @param       Symfony\Component\HttpFoundation\Request    $request
     * @param       Silex\Application                           $app
     * @return      
     */
    public function before(Request $request, Application $app)
    {
        return $this->handler->check($request);
    }

    /*!
     * @method      auth
     * @public
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function auth(Request $request)
    {
        return $this->handler->auth($request);
    }

    /*!
     * Authentication handler already check that user is authenticate.
     * This is just the response
     *
     * @method      isAuthenticate
     * @public
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function isAuthenticate()
    {
        $done = true;
        $user = $this->session->get('user');
        $msg  = 'logged as '.$user->login;
        return $this->response($done, $msg);
    }

    /*!
     * @method      logout
     * @public
     * @return      Symfony\Component\HttpFoundation\JsonResponse
     */
    public function logout()
    {
        $this->handler->sessionInvalidate();
        $sessid = $this->session->getId();
        $done   = true;
        $msg    = 'session logout';
        return $this->response($done, $msg);
    }

    /*!
     * @method      routing
     * @public
     * @param       Silex\ControllerCollection   $collection
     * @return      Silex\ControllerCollection
     */
    public function routing(ControllerCollection $collection) : ControllerCollection
    {
        $_ = $this->ns();

        $collection->match('/auth'         , "$_:auth");
        $collection->match('/logout'       , "$_:logout");
        $collection->match('/isauth'       , "$_:isAuthenticate");

        return $collection;
    }
}
