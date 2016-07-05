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
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @PIM\Config(readonly=true, hide=true, label="ID")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @PIM\Config(hide=true)
     */
    protected $created;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @PIM\Config(hide=true, label="geändert am")
     */
    protected $modified;

    /**
     * @ORM\Column(type="boolean")
     * @PIM\Config(hide=true)
     */
    protected $isDeleted;


    /**
     * @ORM\Column(type="integer", options={"default" = 0}, nullable=true, nullable=true)
     * @PIM\Config(hide=true, label="Gelesen")
     *
     */
    protected $views;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @PIM\Config(label="geändert von", hide=true)
     */
    protected $user;

    /**
     * @ORM\Column(type="boolean")
     * @PIM\Config(hide=true)
     */
    protected $isIntern = 0;

    /**
     * @PIM\Config(hide=true)
     */
    protected $disableModifiedTime = false;

    
    public function __construct()
    {
        $this->created   = new \DateTime();
        $this->modified  = new \DateTime();
        $this->isDeleted = false;
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
        } else if($created != null) {
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
        } else if($modified != null) {
            $this->modified = new \Datetime($modified);
        }
    }

    /**
     * @return mixed
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * @param mixed $isDeleted
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
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