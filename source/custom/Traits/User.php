<?php
namespace Custom\Traits;

trait User{

    /**
     * @ORM\Column(type="string")
     * @PIM\Config(showInList=40, label="Firma")
     */
    protected $company;

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }


}