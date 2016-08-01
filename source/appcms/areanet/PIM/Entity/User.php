<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_user")
 * @PIM\Config(label="Benutzer")
 */
class User extends Base implements \JsonSerializable
{

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=20, label="Admin")
     */
    protected $isAdmin;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @PIM\Config(showInList=30, label="Benutzer")
     */
    protected $alias;

    /**
     * @ORM\Column(type="string", length=100)
     * @PIM\Config(label="Passwort")
     * @PIM\Password()
     */
    protected $pass;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=10, label="Aktiv")
     */
    protected $isActive;

    /**
     * @ORM\Column(type="string", length=100)
     * @PIM\Config(hide=true)
     */
    protected $salt;

    public function __construct()
    {
        parent::__construct();

        $token = bin2hex(openssl_random_pseudo_bytes(32));
        $this->setSalt($token);
        $this->isActive = true;
    }

    /**
     * @return mixed
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @param mixed $isAdmin
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;
    }





    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param mixed $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * @return mixed
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @param mixed $pass
     */
    public function setPass($pass)
    {
        $this->pass = hash("sha256", $pass.$this->salt);
    }

    /**
     * @return boolean
     */
    public function isPass($pass)
    {
        return (hash("sha256", $pass.$this->salt) == $this->pass);
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }




    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return array(
            'alias' => $this->alias,
            'isActive' => $this->isActive,
            'isAdmin' => $this->isAdmin,
            'id' => $this->id
        );
    }
}