<?php
namespace Areanet\PIM\Classes;

use Areanet\PIM\Classes\ApnsPHP\Log\NoLogger;
use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Entity\Base;
use Doctrine\ORM\EntityManager;

class Push{

    /** @var Base */
    protected $pushObject;

    /** @var EntityManager $em */
    protected $em;

    public function __construct(EntityManager $em, Base $pushObject)
    {
        $this->pushObject = $pushObject;
        $this->em         = $em;
    }

    public function send($fieldTitle, $fieldText, $fieldObject)
    {
        set_time_limit(0);

        $getterTitle    = "get".ucfirst($fieldTitle);
        $getterText     = "get".ucfirst($fieldText);
        $getterObject   = "get".ucfirst($fieldObject);

        $title          = $this->pushObject->$getterTitle();
        $text           = $this->pushObject->$getterText();
        $objectId       = $this->pushObject->$getterObject();

        $objects = $this->em->getRepository('Areanet\PIM\Entity\PushToken')->findAll();

        $androidTokens = array();
        $iosTokens     = array();
        $count         = 0;

        foreach($objects as $object){
            if($object->getPlatform() == 'android') {
                $androidTokens[] = $object->getToken();
                $count++;
            }elseif($object->getPlatform() == 'ios') {
                $iosTokens[] = $object->getToken();
                $count++;
            }
        }

        $this->sendIos($iosTokens, $title, $text, $objectId);
        $this->sendAndroid($androidTokens, $title, $text, $objectId);

        $this->pushObject->setCount($count);
        $this->em->persist($this->pushObject);
        $this->em->flush();

    }

    protected function sendIos($tokens, $title, $text, $objectId)
    {
        if(!count($tokens)){
            return;
        }

        $push = new \ApnsPHP_Push(
            Config\Adapter::getConfig()->PUSH_APPLE_SANDBOX ? \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX : \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
            ROOT_DIR.'/custom/'.Config\Adapter::getConfig()->PUSH_APPLE_CERT
        );

        $logger = new NoLogger();
        $push->setLogger($logger);

        $push->setProviderCertificatePassphrase(Config\Adapter::getConfig()->PUSH_APPLE_PASS);
        $push->setRootCertificationAuthority(ROOT_DIR.'/data/entrust_root_certification_authority.pem');
        $push->connect();

        foreach($tokens as $token){
            $message = new \ApnsPHP_Message($token);
            $message->setText($title.' '.$text);
            $message->setBadge(0);
            $message->setCustomProperty('object', $objectId);

            $push->add($message);
        }

        $push->send();
        $push->disconnect();
        
    }

    protected function sendAndroid($tokens, $title, $text, $objectId)
    {
        if(!count($tokens)){
            return;
        }

        $androidTokensChunked = array_chunk($tokens, 900);

        foreach($androidTokensChunked as $androidTokens) {
            $msg = array(
                'message'   => $text,
                'title'     => $title,
                'object'    => $objectId,
                'vibrate'    => 1,
                'sound'     => 1
            );

            $fields = array(
                'registration_ids'  => $androidTokens,
                'data'              => $msg
            );

            $headers = array(
                'Authorization: key=' . Config\Adapter::getConfig()->PUSH_GOOGLE_KEY,
                'Content-Type: application/json'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);
            $json   = json_decode($result);
            if($json) {
                for ($i = 0; $i < count($json->results); $i++) {
                    if (isset($json->results[$i]->error)) {
                        $pushToken = $this->em->getRepository('Areanet\PIM\Entity\PushToken')->findOneBy(array('token' => $androidTokens[$i]));
                        if ($pushToken) {
                            $this->em->remove($pushToken);
                        }
                    }
                }
                $this->em->flush();
            }

            curl_close($ch);

        }
    }
}