<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="firma_ansprechpartner")
 * @PIM\Config(label="Ansprechpartner", hide=true)
 */
class Ansprechpartner extends Base
{

    /**
     * @ORM\ManyToOne(targetEntity="Custom\Entity\Firma")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @PIM\Config(showInList=20, label="Firma", isFilterable=true)
     */
    protected $firma;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=40, label="Vorname")
     */
    protected $vorname;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=60, label="Nachname")
     */
    protected $nachname;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=80, label="Position")
     */
    protected $position;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(showInList=100, label="E-Mail")
     */
    protected $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @PIM\Config(label="Telefon")
     */
    protected $telefon;

    /**
     * @return mixed
     */
    public function getFirma()
    {
        return $this->firma;
    }

    /**
     * @param mixed $firma
     */
    public function setFirma($firma)
    {
        $this->firma = $firma;
    }


    /**
     * @return mixed
     */
    public function getVorname()
    {
        return $this->vorname;
    }

    /**
     * @param mixed $vorname
     */
    public function setVorname($vorname)
    {
        $this->vorname = $vorname;
    }

    /**
     * @return mixed
     */
    public function getNachname()
    {
        return $this->nachname;
    }

    /**
     * @param mixed $nachname
     */
    public function setNachname($nachname)
    {
        $this->nachname = $nachname;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getTelefon()
    {
        return $this->telefon;
    }

    /**
     * @param mixed $telefon
     */
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;
    }



    

}