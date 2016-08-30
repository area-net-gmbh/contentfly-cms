<?php
namespace Custom\Classes\Repository;

use Doctrine\ORM\EntityRepository;

class ProduktRepository extends EntityRepository{

    public function findByTest(){
        return array('foo' => 'bar');
    }
}
