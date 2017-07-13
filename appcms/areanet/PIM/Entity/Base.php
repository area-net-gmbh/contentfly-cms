<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */

class Base extends Serializable
{
    /**
     * @ORM\Column(type=APPCMS_ID_TYPE)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy=APPCMS_ID_STRATEGY)
     * @PIM\Config(readonly=true, hide=true, label="ID")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @PIM\Config(hide=true, label="erstellt am")
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @PIM\Config(hide=true, label="geändert am")
     */
    protected $modified;


    /**
     * @ORM\Column(type="integer", options={"default" = 0}, nullable=true)
     * @PIM\Config(hide=true, label="Gelesen")
     *
     */
    protected $views;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     * @PIM\Config(label="geändert von", hide=true)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\User")
     * @ORM\JoinColumn(name="usercreated_id", referencedColumnName="id", onDelete="SET NULL")
     * @PIM\Config(showInList = 1000, label="Besitzer", tab="settings")
     */
    protected $userCreated;
    
    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     * @PIM\Config(hide=true)
     */
    protected $isIntern = 0;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Virtualjoin(targetEntity="Areanet\PIM\Entity\User")
     * @PIM\Config(label="Benutzer", tab="settings")
     */
    protected $users;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Virtualjoin(targetEntity="Areanet\PIM\Entity\Group")
     * @PIM\Config(label="Gruppen", tab="settings")
     */
    protected $groups;

    /**
     * @PIM\Config(hide=true)
     */
    protected $disableModifiedTime = false;

    
    public function __construct()
    {
        $this->created   = new \DateTime();
        $this->modified  = new \DateTime();
    }


    public function doDisableModifiedTime($disable){
        $this->disableModifiedTime = $disable;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        if($created instanceof \Datetime) {
            $this->created = $created;
        } else if($created !== null) {
            $this->created = new \Datetime($created);
        }
    }

    /**
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @param \DateTime $modified
     */
    public function setModified($modified)
    {
        if($modified instanceof \Datetime) {
            $this->modified = $modified;
        } else if($modified !== null) {
            $this->modified = new \Datetime($modified);
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUserCreated()
    {
        return $this->userCreated;
    }

    /**
     * @param mixed $userCreated
     */
    public function setUserCreated($userCreated)
    {
        $this->userCreated = $userCreated;
    }

    
    /**
     * @return mixed
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param mixed $views
     */
    public function setViews($views)
    {
        $this->views = $views;
    }

    /**
     * @return mixed
     */
    public function getDisableModifiedTime()
    {
        return $this->disableModifiedTime;
    }

    /**
     * @param mixed $disableModifiedTime
     */
    public function setDisableModifiedTime($disableModifiedTime)
    {
        $this->disableModifiedTime = $disableModifiedTime;
    }

    /**
     * @return mixed
     */
    public function getIsIntern()
    {
        return $this->isIntern;
    }

    /**
     * @param mixed $isIntern
     */
    public function setIsIntern($isIntern)
    {
        $this->isIntern = $isIntern;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        $ids = explode(',', $this->users);

        $data = array();
        foreach($ids as $id){
            if(empty($id)) continue;

            $data[] = array(
                'id' => $id
            );
        }

        return $data;
    }

    /**
     * @return boolean
     */
    public function hasUserId($id)
    {
        $ids = explode(',', $this->users);
        
        return in_array($id, $ids);
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return mixed
     */
    public function getGroups()
    {
        $ids = explode(',', $this->groups);

        $data = array();
        foreach($ids as $id){
            if(empty($id)) continue;

            $data[] = array(
                'id' => $id
            );
        }

        return $data;
    }

    /**
     * @return boolean
     */
    public function hasGroupId($id)
    {
        $ids = explode(',', $this->groups);

        return in_array($id, $ids);
    }

    /**
     * @param mixed $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }
    
    

    

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime() {
        // update the modified time

        if(!$this->disableModifiedTime) {

            $this->setModified(new \DateTime());
        }
    }

}
