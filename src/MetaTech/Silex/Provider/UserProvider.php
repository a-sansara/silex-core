<?php
namespace MetaTech\Silex\Provider;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use MetaTech\Db\PdoWrapper;

/*!
 * desc
 * 
 * @package         MetaTech\Silex\Provider
 * @class           UserProvider
 * @implements      Symfony\Component\Security\Core\User\UserInterface
 * @author          a-Sansara
 * @date            2016-02-08 18:29:06 CET
 */
class UserProvider implements UserProviderInterface
{
    /*! @private @var MetaTech\Db\PdoWrapper $pdo */
    private $pdo;
    /*! @private @var str $table */
    private $table;

    /*!
     * @constructor
     * @public
     * @param       MetaTech\Db\PdoWrapper  $pdo
     */
    public function __construct(PdoWrapper $pdo, $table='`users`')
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    /*!
     * @method      loadUser
     * @private
     * @param       str     $login
     * @return      Symfony\Component\Security\Core\User\User
     */
    private function loadUser($login)
    {
        $username  = strtolower($login);
        $stmt      = $this->pdo->exec('SELECT * FROM ' . $this->table . ' WHERE username = :username', compact('username'));
        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }
        return $user;
    }

    /*!
     * @method      getUserNameById
     * @public
     * @param       int     $id
     * @return      Symfony\Component\Security\Core\User\User
     */
    public function getUserNameById($id)
    {
        $stmt      = $this->pdo->exec('SELECT name FROM ' . $this->table . ' WHERE id = :id', compact('id'));
        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('Userid "%s" does not exist.', $id));
        }
        return $user;
    }

    /*!
     * @method      loadUserPrograms
     * @public
     * @return      Symfony\Component\Security\Core\User\User
     */
    private function loadUserByRole($role)
    {
        return $this->pdo->exec('SELECT * FROM ' . $this->table . ' WHERE roles LIKE :role', compact('role'))->fetchAll();
    }

    /*!
     * @method      loadProgramKeys
     * @public
     * @return      Symfony\Component\Security\Core\User\User
     */
    public function loadProgramKeys()
    {
        $keys = [];
        $rows = $this->loadUserPrograms();
        $rows = array_merge($rows, $this->loadUserPrograms('INSURER'));
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $keys[] = $row->key;
            } 
        }
        return $keys;
    }

    /*!
     * @method      loadUserByUsername
     * @public
     * @param       str     $username
     * @return      Symfony\Component\Security\Core\User\User
     */
    public function loadUserByUsername($username)
    {
        $user = $this->loadUser($username);
        $u    = new User($user->username, $user->password, explode(',', $user->roles), true, true, true, true);
        $u->labelName = $user->name;
        $u->key = $user->key;
        return $u;
    }

    /*!
     * @method      getUserKey
     * @public
     * @param       str     $username
     * @return      Symfony\Component\Security\Core\User\User
     */
    public function getUserKey($username)
    {
        $user = $this->loadUser($username);
        return $user->key;
    }

    /*!
     * @method      getIdUser
     * @public
     * @param       str     $username
     * @return      int|null
     */
    public function getIdUser($username)
    {
        $user = $this->loadUser($username);
        return isset($user->id) ? $user->id : null;
    }

    /*!
     * @method      refreshUser
     * @public
     * @param       Symfony\Component\Security\Core\User\UserInterface  $user
     * @return      Symfony\Component\Security\Core\User\User
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    /*!
     * @method      supportsClass
     * @public
     * @param       str     $class
     * @return      bool
     */
    public function supportsClass($class) {
        return $class === User::class;
    }

}
