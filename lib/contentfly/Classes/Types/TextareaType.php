<?php
namespace Areanet\Contentfly\Classes\Types;
use Areanet\Contentfly\Classes\Api;
use Areanet\Contentfly\Classes\Config\Adapter;
use Areanet\Contentfly\Classes\Type;
use Areanet\Contentfly\Controller\ApiController;
use Areanet\Contentfly\Entity\Base;


class TextareaType extends Type
{
    public function getPriority()
    {
        return 10;
    }
    
    public function getAlias()
    {
        return 'textarea';
    }

    public function getAnnotationFile()
    {
        return 'Textarea';
    }

    public function doMatch($propertyAnnotations){
        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'])) {
            return true;
        }

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])) {
            return false;
        }

        $annotation = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

        return ($annotation->type == 'text');
    }

    public function processSchema($key, $defaultValue, $propertyAnnotations, $entityName)
    {
        $schema = parent::processSchema($key, $defaultValue, $propertyAnnotations, $entityName);

        if(isset($propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'])){

            $annotations = $propertyAnnotations['Areanet\\PIM\\Classes\\Annotations\\Textarea'];

            $schema['lines'] = $annotations->lines;
        }

        return $schema;
    }

    public function fromDatabase(Base $object, $entityName, $property, $flatten = false, $level = 0, $propertiesToLoad = array())
    {
        $getter = 'get'.ucfirst($property);

        $config = $this->app['schema'][ucfirst($entityName)]['properties'][$property];
        if(empty($config['encoded'])){
            return $object->$getter();
        }

        if(empty(Adapter::getConfig()->SECURITY_CIPHER_KEY)){
            throw new \Exception('Für die Verschlüsselung muss ein Wert für SECURITY_CIPHER_KEY gesetzt sein.');
        }

        $encryptedValue = $object->$getter();

        if(empty($encryptedValue)){
            return '';
        }

        return openssl_decrypt($encryptedValue, Adapter::getConfig()->SECURITY_CIPHER_METHOD, Adapter::getConfig()->SECURITY_CIPHER_KEY);

    }

    public function toDatabase(Api $api, Base $object, $property, $value, $entityName, $schema, $user, $data = null, $lang = null)
    {
        $setter = 'set'.ucfirst($property);

        if(empty($value)){
            $object->$setter('');
            return;
        }

        $config = $this->app['schema'][ucfirst($entityName)]['properties'][$property];
        if(empty($config['encoded'])){
            $object->$setter($value);
            return;
        }

        if(empty(Adapter::getConfig()->SECURITY_CIPHER_KEY)){
            throw new \Exception('Für die Verschlüsselung muss ein Wert für SECURITY_CIPHER_KEY gesetzt sein.');
        }

        $encryptedValue = openssl_encrypt($value, Adapter::getConfig()->SECURITY_CIPHER_METHOD, Adapter::getConfig()->SECURITY_CIPHER_KEY);

        $object->$setter($encryptedValue);

    }
}
