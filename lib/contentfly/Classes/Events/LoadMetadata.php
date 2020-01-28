<?php
namespace Areanet\PIM\Classes\Events;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

class LoadMetadata
{
    public function loadClassMetadata(\Doctrine\ORM\Event\LoadClassMetadataEventArgs $eventArgs)
    {
        $em             = $eventArgs->getEntityManager();
        $classMetadata  = $eventArgs->getClassMetadata();
        $className      = $classMetadata->getName();

        if(!in_array('Areanet\PIM\Entity\BaseTree', $classMetadata->parentClasses) && !in_array('Areanet\PIM\Entity\BaseI18nTree', $classMetadata->parentClasses)){
            $cmBuilder      = new ClassMetadataBuilder($classMetadata);
            $cmBuilder->addIndex(array('modified',), 'modified_index');

            $em->getMetadataFactory()->setMetadataFor($className, $classMetadata);
        }

    }
}