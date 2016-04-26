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

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=0, label="Versteckt")
     */
    protected $isHidden;

    /**
     * @ORM\Column(name="model_name", type="string")
     * @PIM\Config(showInList=20,label="Objekt")
     */
    protected $modelName;

    /**
     * @ORM\Column(name="model_id", type="integer", nullable=false)
     * @PIM\Config(showInList=30, label="Objekt-ID")
     */
    protected $modelId;


    /**
     * @ORM\Column(type="string", length=100, nullable=false)
     * @PIM\Config(showInList=50, label="Aktion", type="select", options="Geändert, Gelöscht, Erstellt" )
     */
    protected $mode;



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
}
?>