<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;


class FileType extends Type
{
    public function getAlias()
    {
        return 'file';
    }

    public function getAnnotationFile()
    {
        return null;
    }

    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'])) {
            return false;
        }

        $annotations = $propertyAnnotations['Doctrine\\ORM\\Mapping\\ManyToOne'];

        return $annotations->targetEntity == 'Areanet\PIM\Entity\File';
    }


    public function processSchema($key, $defaultValue, $propertyAnnotations){
        $schema             = parent::processSchema($key, $defaultValue, $propertyAnnotations);
        $schema['multipe']  = false;
        $schema['accept']   = '*';
        $schema['dbtype']   = 'integer';

        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'])){
            $annotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Config'];

            if($annotations->accept){
                $schema['accept'] = $annotations->accept;
            }
        }

        return $schema;
    }

    public function toDatabase(ApiController $controller, Base $object, $property, $value, $entityName, $schema, $user)
    {
        $setter = 'set'.ucfirst($property);
        $getter = 'get'.ucfirst($property);

        if(empty($value)){
            $object->$setter(null);
            return;
        }

        if(is_array($value)){
            if(empty($value["id"])) return;

            $value = $value["id"];
        }

        $objectToJoin = $this->em->getRepository('Areanet\PIM\Entity\File')->find($value);

        if(!$objectToJoin) {
            return;
        }
        $object->$setter($objectToJoin);

    }
}