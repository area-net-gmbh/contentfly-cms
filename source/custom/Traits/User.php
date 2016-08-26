<?php
namespace Custom\Traits;

trait User{
    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=20, label="Name")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="E-Mail")
     */
    protected $email;

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


}