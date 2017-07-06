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

    public function send($fieldTitle, $fieldText, $additionalData)
    {
        set_time_limit(0);

        $getterTitle    = "get".ucfirst($fieldTitle);
        $getterText     = "get".ucfirst($fieldText);

        $title          = $this->pushObject->$getterTitle();
        $text           = $this->pushObject->$getterText();

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

        $this->sendIos($iosTokens, $title, $text, $additionalData);
        $this->sendAndroid($androidTokens, $title, $text, $additionalData);

        $this->pushObject->setCount($count);
        $this->em->persist($this->pushObject);
        $this->em->flush();

    }

    protected function sendIos($tokens, $title, $text, $additionalData = array())
    {
        if(!count($tokens)){
            return;
        }

        $push = new \ApnsPHP_Push(
            Config\Adapter::getConfig()->PUSH_APPLE_SANDBOX ? \ApnsPHP_Abstract::ENVIRONMENT_SANDBOX : \ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
            ROOT_DIR.'/../custom/'.Config\Adapter::getConfig()->PUSH_APPLE_CERT
        );

        $logger = new NoLogger();
        $push->setLogger($logger);

        $push->setProviderCertificatePassphrase(Config\Adapter::getConfig()->PUSH_APPLE_PASS);
        $push->setRootCertificationAuthority(ROOT_DIR.'/../custom/entrust_root_certification_authority.pem');
        $push->connect();

        foreach($tokens as $token){
            $message = new \ApnsPHP_Message($token);
            $message->setText($title.' '.$text);
            $message->setBadge(0);
            foreach($additionalData as $key => $value){
                $message->setCustomProperty($key, $value);
            }

            $push->add($message);
        }

        $push->send();
        $push->disconnect();

    }

    protected function sendAndroid($tokens, $title, $text, $additionalData = array())
    {
        if(!count($tokens)){
            return;
        }

        $androidTokensChunked = array_chunk($tokens, 900);

        foreach($androidTokensChunked as $androidTokens) {
            $msg = array(
                'body'      => $text,
                'title'     => $title
            );

            $fields = array(
                'registration_ids'  => $androidTokens,
                'notification'      => $msg,
                'data'              => $additionalData
            );



            $headers = array(
                'Authorization: key=' . Config\Adapter::getConfig()->PUSH_GOOGLE_KEY,
                'Content-Type: application/json'
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);
            $json   = json_decode($result);
            if($json) {
                $count = count($json->results);
                for ($i = 0; $i < $count; $i++) {
                    if (isset($json->results[$i]->error)) {
                        $pushToken = $this->em->getRepository('Areanet\PIM\Entity\PushToken')->findOneBy(array('token' => $androidTokens[$i]));
                        if ($pushToken) {
                            $this->em->remove($pushToken);
                        }
                    }
                }

            }

            curl_close($ch);

        }

        $this->em->flush();
    }
}