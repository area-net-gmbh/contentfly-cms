<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_group")
 * @PIM\Config(label="Gruppe", labelProperty="name", tabs="{'permissions': 'Berechtigungen'}")
 */
class Group extends Base
{
    use \Custom\Traits\Group;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @PIM\Config(showInList=30, label="Name")
     */
    protected $name;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(showInList=40, label="Token-Timeout (in min)")
     */
    protected $tokenTimeout = 30;

    /**
     * @ORM\OneToMany(targetEntity="Areanet\PIM\Entity\Permission", mappedBy="group", cascade={"remove"})
     * @PIM\Config(tab="permissions", label="Berechtigungen")
     * @PIM\Permissions()
     */
    protected $permissions;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @param mixed $permissions
     */
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return mixed
     */
    public function getTokenTimeout()
    {
        return $this->tokenTimeout;
    }

    /**
     * @param mixed $tokenTimeout
     */
    public function setTokenTimeout($tokenTimeout)
    {
        $this->tokenTimeout = $tokenTimeout;
    }
    
      



}