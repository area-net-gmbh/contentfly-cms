<?php
namespace Areanet\PIM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Created by PhpStorm.
 * User: ms
 * Date: 27.01.16
 * Time: 12:04
 */

/**
 * @ORM\Entity
 * @ORM\Table(name="pim_push_token")
 */
class PushToken extends Base
{

    /**
     * @ORM\Column(type="string", length=200, unique=true)
     */
    protected $token;

    /**
    * @ORM\Column(type="string", length=10, unique=false)
    */
    protected $platform;



    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param mixed $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }



}