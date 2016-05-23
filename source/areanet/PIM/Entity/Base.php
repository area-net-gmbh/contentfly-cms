<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */

class Base implements \JsonSerializable
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
     */
    protected $views;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * @PIM\Config(label="geändert von", hide=true)
     */
    protected $user;

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
        } else {
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
        } else {
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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime() {
        // update the modified time

        if(!$this->disableModifiedTime) {

            $this->setModified(new \DateTime());
        }
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
        return $this->toValueObject();
    }

    public function toValueObject($flatten = false, $level = 0)
    {

        //@todo: "Schlanke" Listenabfrage ohne Joins als Option!
        $result = new \stdClass();

        if($level > 2){
            $result->id = $this->getId();
            return $result;
        }

        foreach ($this as $property => $value) {
            if(!$flatten){
                $getter = 'get' . ucfirst($property);
                if (method_exists($this, $getter)) {

                    if ($this->$property instanceof \Datetime) {
                        $res = $this->$property->format('Y');
                        if ($this->$property->format('Y') == '-0001' || $this->$property->format('Y') == '0000') {
                            $result->$property = array(
                                'LOCAL_TIME' => null,
                                'LOCAL' => null,
                                'ISO8601' => null,
                                'IMESTAMP' => null
                            );
                        } else {
                            $result->$property = array(
                                'LOCAL_TIME' => $this->$property->format('d.m.Y H:i'),
                                'LOCAL' => $this->$property->format('d.m.Y'),
                                'ISO8601' => $this->$property->format(\DateTime::ISO8601),
                                'TIMESTAMP' => $this->$property->getTimestamp()
                            );
                        }
                    }
                    elseif($this->$property instanceof Base && $property != 'user') {
                        $getterName = 'get' . ucfirst($property);
                        $result->$property = $this->$getterName()->toValueObject($flatten, ($level + 1));
                    }elseif($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $data = array();
                        foreach ($this->$property as $object) {
                            $data[] = $object->toValueObject($flatten,  ($level + 1));
                        }

                        $result->$property = $data;
                    }else{
                        $result->$property = $this->$getter();
                    }
                }
            }else{
                $getter = 'get' . ucfirst($property);

                if (method_exists($this, $getter)) {
                    if ($this->$property instanceof \Doctrine\ORM\PersistentCollection) {
                        $result->$property = [];
                    }elseif($this->$property instanceof Base){
                        $result->$property = $this->$getter()->getId();
                    }else{
                        $result->$property = $this->$getter();

                    }

                }
            }

        }
        return $result;
    }
}