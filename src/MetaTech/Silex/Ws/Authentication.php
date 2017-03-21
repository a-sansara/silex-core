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
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
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
    /*! @protected @var Symfony\Component\HttpFoundation\Session\Session $session */ 
    protected $session;
    /*! @protected @var MetaTech\PwsAuth\Authenticator $authenticator */
    protected $authenticator;
    /*! @protected @var Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $passEncoder */
    protected $passEncoder;

    /*!
     * @constructor
     * @public
     * @param       Symfony\Component\HttpFoundation\Session\Session                    $session
     * @param       MetaTech\PwsAuth\Authenticator                                      $authenticator
     * @param       Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface    $passEncoder
     */
    public function __construct(Session $session, Authenticator $authenticator, PasswordEncoderInterface $passEncoder = null)
    {
        $this->session       = $session;
        $this->authenticator = $authenticator;
        $this->passEncoder   = $passEncoder;
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
     * @param      str                                                                  $login
     * @param      str                                                                  $password
     * @param      str                                                                  $key
     * @param      Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface     $passEncoder
     * @return     bool
     */
    public function checkUser($login, $password, $key, PasswordEncoderInterface $passEncoder = null)
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
        $done          = false;
        $msg           = 'authentication require';
        $token         = $this->authenticator->getToken();
        $login         = $request->get('login');
        $responseToken = $this->authenticator->generateResponseHeader($token);
        $headers       = $this->getResponseHeaders([], $responseToken);
        if ($this->authenticator->isValid($token)) {
            $password = $request->get('password');
            if ($this->authenticator->check($token, $login)) {
                try {
                    if ($done = $this->checkUser($login, $password, $token->getIdent(), $this->passEncoder)) {
                        $sid  = $this->onSuccess($token, $login);
                        $msg  = "authentication sucessful ! logged as $login";
                        $data = compact('sid');
                    }
                }
                catch(\Exception $e) {
                    $msg = 'invalid user or password';
                }
            }
        }
        if (!$done) {
            sleep(3);
        }
        return new JsonResponse(compact('done', 'msg', 'data'), $done ? 200 : 401, $headers);
    }

    /*!
     * @method      getResponseHeaders
     * @private
     * @param       [assoc]     $headers
     * @return      [assoc]
     */
    private function getResponseHeaders($headers=[], $tokenResponse=null)
    {
        if (!empty($tokenResponse) || !empty($tokenResponse = $this->session->get('pwsauth.response'))) {
            $headers['Pws-Response'] = $tokenResponse;
        }
        return $headers;
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
            $done    = false;
            $msg     = "authentication require";
            $headers = [];
            try {
                $token         = $this->authenticator->getToken();
                $tokenResponse = $this->authenticator->generateResponseHeader($token);
                $headers       = $this->getResponseHeaders($headers, $tokenResponse);
                if ($this->authenticator->isValid($token)) {
                    if (!empty($sid = $this->authenticator->getSessionId($token))) {
                        $this->sessionInvalidate();
                        $this->session->setId($sid);
                        $this->session->start();
                        $user = $this->session->get('user');
                        // done : lets controller takes hand
                        if (!is_null($user) && $user->key == $token->getIdent()) {
                            $this->session->set('pwsauth.response', $tokenResponse);
                            return;
                        }
                        else {
                            $this->sessionInvalidate();
                        }
                    }
                }
            }
            catch(\Exception $e) {
                $done = false;
                $msg  = $e->getMessage();
            }
            return new JsonResponse(compact('done', 'msg', 'data'), 401, $headers);
        }
    }
}
