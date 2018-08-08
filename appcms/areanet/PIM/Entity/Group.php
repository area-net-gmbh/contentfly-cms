<?php
namespace Areanet\PIM\Entity;

use Areanet\PIM\Classes\Helper;
use Areanet\PIM\Classes\I18nPermission;
use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_group")
 * @PIM\Config(label="Gruppe", labelProperty="name", tabs="{'permissions': 'Berechtigungen', 'i18n': 'Sprachen'}")
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
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Sprachen", tab="i18n")
     * @PIM\I18nPermissions()
     */
    protected $languages;

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

    /**
     * @return mixed
     */
    public function getLanguages()
    {
        if(!$this->languages){
            return null;
        }
        return is_string($this->languages) ? json_decode($this->languages, true) : $this->languages;
    }

    /**
     * @param mixed $languages
     */
    public function setLanguages($languages)
    {
        if($languages){
            $this->languages = !is_string($languages) ? json_encode($languages) : $languages;
        }

    }

    public function langIsWritable($lang){
        if(!($langPermissions = $this->getLanguages())){
            return true;
        }

        if(empty($langPermissions[$lang])){
            return true;
        }

        return false;
    }

    public function langIsTranslatable($lang){
        if(!($langPermissions = $this->getLanguages())){
            return true;
        }

        if(empty($langPermissions[$lang])){
            return true;
        }

        return $langPermissions[$lang] == I18nPermission::IS_TRANSLATABALE;
    }

    public function langisOnlyReadable($lang){
        return !$this->langIsTranslatable($lang) && !$this->langIsWritable($lang);
    }

}