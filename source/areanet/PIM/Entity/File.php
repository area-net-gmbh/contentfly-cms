<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_file", uniqueConstraints={@ORM\UniqueConstraint(name="file_unique", columns={"name", "folder_id", "isDeleted"})})
 * @PIM\Config(label="Dateien", labelProperty="name", sortBy="name", sortOrder="ASC", tabs="{'tags': 'Tags'}")
 */

class File extends Base
{

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(label="Name", readonly=true, showInList=30)
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="Areanet\PIM\Entity\Folder")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @PIM\Config(showInList=80, label="Ordner", isFilterable=true)
     */
    protected $folder;


    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Alias-Name", showInList=40)
     */
    protected $alias;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Titel", showInList=50)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Alt-Text")
     */
    protected $altText;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="Beschreibung")
     * @PIM\Textarea()
     */
    protected $description;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(label="Dateityp", readonly=true, showInList=60)
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(hide=true)
     */
    protected $hash;

    /**
     * @ORM\Column(type="integer")
     * @PIM\Config(label="Dateigröße", readonly=true, showInList=70)
     */
    protected $size;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(hide=true, label="Versteckt")
     */
    protected $isHidden;

    /**
     * @ORM\ManyToMany(targetEntity="Areanet\PIM\Entity\Tag")
     * @ORM\JoinTable(name="pim_file_tags", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="Tags", tab="tags", isFilterable=true)
     */
    protected $tags;

    /**
     * @return mixed
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @param mixed $folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }


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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }



    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return mixed
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }

    /**
     * @param mixed $isHidden
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;
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
    public function getAltText()
    {
        return $this->altText;
    }

    /**
     * @param mixed $altText
     */
    public function setAltText($altText)
    {
        $this->altText = $altText;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param mixed $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }






}