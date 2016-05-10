<?php
namespace Areanet\PIM\Classes;

use \Areanet\PIM\Classes\Config;
use Areanet\PIM\Entity\Base;
use Doctrine\ORM\EntityManager;

// @todo: Push-Modul optimieren, siehe Wüstenrot

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

        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', ROOT_DIR.'/custom/'.Config\Adapter::getConfig()->PUSH_APPLE_CERT);
        stream_context_set_option($streamContext, 'ssl', 'passphrase', Config\Adapter::getConfig()->PUSH_APPLE_PASS);

        $apns = stream_socket_client('ssl://' . Config\Adapter::getConfig()->PUSH_APPLE_HOST, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);

        $payload['aps'] = array('alert' => $title.' '.$text, 'badge' => 0, 'object' => $objectId);
        $payload        = json_encode($payload);

        foreach($tokens as $token){
            $apnsMessage = chr(0) . pack('n', 32) . pack('H*', $token) . pack('n', strlen($payload)) . $payload;
            fwrite($apns, $apnsMessage);
        }

        fclose($apns);
    }

    protected function sendAndroid($tokens, $title, $text, $objectId)
    {
        if(!count($tokens)){
            return;
        }

        $msg = array
        (
            'message' 	=> $text,
            'title'		=> $title,
            'object'    => $objectId,
            'vibrate'	=> 1,
            'sound'		=> 1
        );
        $fields = array
        (
            'registration_ids' 	=> $tokens,
            'data'			    => $msg
        );

        $headers = array
        (
            'Authorization: key=' . Config\Adapter::getConfig()->PUSH_GOOGLE_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
    }
}