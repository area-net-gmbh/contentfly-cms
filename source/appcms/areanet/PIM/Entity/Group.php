<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_group")
 * @PIM\Config(label="Gruppe", tabs="{'permissions': 'Berechtigungen'}")
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
     * @ORM\OneToMany(targetEntity="Areanet\PIM\Entity\Permission", mappedBy="group")
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



    



}