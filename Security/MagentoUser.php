<?php

namespace Liip\MagentoBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class MagentoUser implements UserInterface
{
    protected $id;
    protected $email;
    protected $firstname;
    protected $lastname;
    protected $roles = array();

    public function __construct($id, $email, $firstname, $lastname)
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
    }

    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {
    }

    public function equals(UserInterface $user)
    {
        return $user->getUsername() === $this->getUsername();
    }

    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * Returns the email address of the Magento user.
     *
     * @return string email
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * Password is not stored.
     *
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function hasRole($role)
    {
        return in_array((string) $role, $this->getRoles());
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    public function __sleep()
    {
        return array('id', 'email', 'firstname', 'lastname', 'roles');
    }
}
