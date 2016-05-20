<?php
namespace Custom\Entity;

use Areanet\PIM\Entity\Base;
use Areanet\PIM\Classes\Annotations as PIM;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="produkt_webinformationen")
 * @PIM\Config(hide=true, label="Webinformationen")
 */
class ProduktWebinformationen extends Base
{
    /**
     * @ORM\Column(type="text", nullable=true)
     * @PIM\Config(type="rte", label="Teaser-Text")
     */
    protected $teaserText;

    /**
     * @ORM\ManyToMany(targetEntity="Custom\Entity\USPText")
     * @ORM\JoinTable(name="produktwebinformaion_usptexte", joinColumns={@ORM\JoinColumn(onDelete="CASCADE")})
     * @PIM\Config(label="USP-Texte")
     */
    protected $uspTexte;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=80, label="Banner 'Neu' anzeigen")
     */
    protected $bannerNeu = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @PIM\Config(showInList=80, label="Banner 'FSC' anzeigen")
     */
    protected $bannerFsc = false;

    /**
     * @return mixed
     */
    public function getTeaserText()
    {
        return $this->teaserText;
    }

    /**
     * @param mixed $teaserText
     */
    public function setTeaserText($teaserText)
    {
        $this->teaserText = $teaserText;
    }

    /**
     * @return mixed
     */
    public function getUspTexte()
    {
        return $this->uspTexte;
    }

    /**
     * @param mixed $uspTexte
     */
    public function setUspTexte($uspTexte)
    {
        $this->uspTexte = $uspTexte;
    }

    /**
     * @return mixed
     */
    public function getBannerNeu()
    {
        return $this->bannerNeu;
    }

    /**
     * @param mixed $bannerNeu
     */
    public function setBannerNeu($bannerNeu)
    {
        $this->bannerNeu = $bannerNeu;
    }

    /**
     * @return mixed
     */
    public function getBannerFsc()
    {
        return $this->bannerFsc;
    }

    /**
     * @param mixed $bannerFsc
     */
    public function setBannerFsc($bannerFsc)
    {
        $this->bannerFsc = $bannerFsc;
    }



    


}