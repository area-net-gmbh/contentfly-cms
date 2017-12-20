<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;
use Silex\Application;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_user")
 * @PIM\Config(label="Benutzer", labelProperty="alias")
 */
class User extends Base
{

    use \Custom\Traits\User;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=20, label="Admin")
     */
    protected $isAdmin;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\Group")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @PIM\Config(showInList=80, label="Gruppe", isFilterable=true)
     */
    protected $group;

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
    protected $isActive = true;

    /**
     * @ORM\Column(type="string", length=100)
     * @PIM\Config(hide=true)
     */
    protected $salt;

    /**
     * @ORM\Column(type="string", length=100)
     * @PIM\Config(label="Login-Manager", tab="settings", readonly=true)
     */
    protected $loginManager;

    protected $tempData;

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
     * @return mixed
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getLoginManager()
    {
        return $this->loginManager;
    }

    /**
     * @param mixed $loginManager
     */
    public function setLoginManager($loginManager)
    {
        $this->loginManager = $loginManager;
    }

    /**
     * @return mixed
     */
    public function getTempData()
    {
        return $this->tempData;
    }

    /**
     * @param mixed $tempData
     */
    public function setTempData($tempData)
    {
        $this->tempData = $tempData;
    }





    public function toValueObject(Application $app = null, $entityName = null, $flatten = false, $propertiesToLoad = array(), $level = 0)
    {

        $data = parent::toValueObject($app, $entityName, $flatten, $propertiesToLoad , $level);

        unset($data->salt);
        unset($data->pass);
        unset($data->user);
        unset($data->created);
        unset($data->modified);
        unset($data->userCreated);

        foreach($data as $key => $value){
            if($value === null){
                unset($data->$key);
            }
        }

        return $data;
    }
}
