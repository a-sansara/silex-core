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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use MetaTech\PwsAuth\Authenticator;
use MetaTech\PwsAuth\Token;

/*!
 * @package     MetaTech\Silex\Ws
 * @class       Authentication
 * @author      a-Sansara
 * @date        2017-03-12 16:04:40 CET
 */
class Authentication
{
    /*! @protected @®ar Symfony\Component\HttpFoundation\Session\Session $session */ 
    protected $session;
    /*! @protected @®ar MetaTech\PwsAuth\Authenticator $authenticator */
    protected $authenticator;

    /*!
     * @constructor
     * @public
     * @param       Symfony\Component\HttpFoundation\Session\Session    $session
     * @param       MetaTech\PwsAuth\Authenticator                      $authenticator
     */
    public function __construct(Session $session, Authenticator $authenticator)
    {
        $this->session = $session;
        $this->authenticator = $authenticator;
    }

    /*!
     * @method      isAllowedRoute
     * @public
     * @param       str     $route
     * @return      bool
     */
    public function isAllowedRoute($route)
    {
        $allowed = false;
        $p       = '/ws/public/';
        if (in_array($route, ['/ws/auth']) || substr($route, 0, strlen($p)) == $p) {
            $allowed = true;
        }
        return $allowed;
    }

    /*!
     * @method      sessionInvalidate
     * @public
     */
    public function sessionInvalidate()
    {
        $this->session->invalidate(1);
        $this->session->save();
    }

    /*!
     * @method      checkUser
     * @public
     * @param      str      $login
     * @param      str      $password
     * @param      str      $key
     * @return      bool
     */
    public function checkUser($login, $password, $key)
    {
        // implements on subclass
        return false;
    }

    /*!
     * @method      auth
     * @param       Symfony\Component\HttpFoundation\Request    $request
     * @public
     */
    public function auth(Request $request)
    {
        $this->sessionInvalidate();
        $done  = false;
        $msg   = 'authentication require';
        $token = $this->authenticator->getToken();
        if ($this->authenticator->isValid($token)) {
            $login    = $request->get('login');
            $password = $request->get('password');
            if ($done = $this->authenticator->check($token, $login)) {
                if ($this->checkUser($login, $password, $token->getIdent())) {
                    $sid  = $this->onSuccess($token, $login);
                    $msg  = "authentication sucessful ! logged as $login";
                    $data = compact('sid');
                }
            }
        }
        return new JsonResponse(compact('done', 'msg', 'data'), $done ? 200 : 401);
    }

    /*!
     * @method      onsuccess
     * @public
     * @param       MetaTech\PwsAuth\Token  $token
     * @param       str                     $login
     */
    public function onsuccess(Token $token, $login)
    {
        $this->session->start();
        $sid  = $this->session->getId();
        $user = new \stdclass();
        $user->key   = $token->getIdent();
        $user->login = $login;
        $this->session->set('user', $user);
        $this->session->save();
        return $sid;
    }

    /*!
     * @method       check
     * @public
     * @param       Symfony\Component\HttpFoundation\Request    $request
     * @return      void | Symfony\Component\HttpFoundation\JsonResponse
     */
    public function check(Request $request)
    {
        if (!$this->isAllowedRoute($request->getPathInfo())) {
            $this->sessionInvalidate();
            $done  = false;
            $msg   = "authentication require";
            try {
                $token = $this->authenticator->getToken();
                
                if ($this->authenticator->isValid($token)) {
                    $sid = $this->authenticator->getSessionId($token);
                    $this->session->setId($sid);
                    $this->session->start();
                    $user = $this->session->get('user');
                    // done : lets controller takes hand
                    if (!is_null($user) && $user->key == $token->getIdent()) {
                        return;
                    }
                    else {
                        $this->sessionInvalidate();
                    }
                }
            }
            catch(\Exception $e) {
                $done = false;
                $msg  = $e->getMessage();
            }
            return new JsonResponse(compact('done', 'msg'), 401);
        }
    }
}
