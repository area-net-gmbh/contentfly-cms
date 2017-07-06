<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_log")
 * @PIM\Config(readonly=true, label="Protokollierung")
 */
class Log extends Base
{

    const DELETED   = 'DEL';
    const INSERTED  = 'INS';
    const UPDATED   = 'UPT';

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=0, label="Versteckt")
     */
    protected $isHidden;

    /**
     * @ORM\Column(name="model_name", type="string")
     * @PIM\Config(showInList=20,label="Objekt", isFilterable=true)
     */
    protected $modelName;

    /**
     * @ORM\Column(name="model_id", type=APPCMS_ID_TYPE, nullable=false)
     * @PIM\Config(showInList=30, label="Objekt-ID")
     */
    protected $modelId;

    /**
     * @ORM\Column(name="model_label", type="string", nullable=true)
     * @PIM\Config(showInList=40, label="Objekt-Titel")
     */
    protected $modelLabel;

    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @PIM\Config(showInList=50, label="Aktion")
     * @PIM\Select(options="Geändert, Gelöscht, Erstellt")
     */
    protected $mode;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(label="Data")
     */
    protected $data;



    public function getModelName()
    {
        return $this->modelName;
    }

    public function setModelName($modelName)
    {
        $this->modelName = $modelName;
    }

    public function getModelId()
    {
        return $this->modelId;
    }

    public function setModelId($modelId)
    {
        $this->modelId = $modelId;
    }


    public function getMode()
    {
        return $this->mode;
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
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
    public function getModelLabel()
    {
        return $this->modelLabel;
    }

    /**
     * @param mixed $modelLabel
     */
    public function setModelLabel($modelLabel)
    {
        $this->modelLabel = $modelLabel;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    
}
