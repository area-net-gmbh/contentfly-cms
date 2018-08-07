<?php
namespace Areanet\PIM\Classes\Types;
use Areanet\PIM\Classes\Api;
use Areanet\PIM\Classes\Config\Adapter;
use Areanet\PIM\Classes\Type;
use Areanet\PIM\Controller\ApiController;
use Areanet\PIM\Entity\Base;


class StringType extends Type
{
    public function getAlias()
    {
        return 'string';
    }

    public function getAnnotationFile()
    {
        return null;
    }
    
    public function doMatch($propertyAnnotations){

        if(!isset($propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'])) {
            return false;
        }

        $annotation = $propertyAnnotations['Doctrine\\ORM\\Mapping\\Column'];

        return ($annotation->type == 'string');
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
