<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;
use Areanet\PIM\Classes\Annotations as PIM;

/**
 * @ORM\MappedSuperclass
 */
class BaseI18n extends Base
{
    /**
     * @ORM\Column(type=APPCMS_ID_TYPE)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @PIM\Config(readonly=true, showInList=APP_CMS_SHOW_ID_IN_LIST, label="ID", tab="settings")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=2, options={"default" = APP_CMS_MAIN_LANG})
     * @ORM\Id
     * @PIM\Config(label="Sprache")
     */
    protected $lang = APP_CMS_MAIN_LANG;

    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param mixed $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }




}