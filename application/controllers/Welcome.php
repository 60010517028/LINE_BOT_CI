<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;

class Welcome extends CI_Controller {

	public function index()
	{ 

		$httpClient = new CurlHTTPClient($this->config->item('LINE_TOKEN'));
        $bot = new LINEBot($httpClient, array('channelSecret' => $this->config->item('LINE_CHANEL')));

		$content = file_get_contents('php://input');

		log_message('INFO',$content);

        //กำหนด signature สำหรับตรวจสอบความถูกต้องจาก Line
        $hash = hash_hmac('sha256', $content,$this->config->item('LINE_CHANEL'), true);
        $signature = base64_encode($hash);

        //แปลงข้อมูลท่ได้จาก Line เป้น Array
		$events = $bot->parseEventRequest($content, $signature);
		
        foreach ($events as $eventObj) {
			
			$replyToken = $eventObj->getReplyToken();
			
			$messageType = $eventObj->getMessageType();
			
			if($messageType == 'text'){
				$textReplyMessage = new TextMessageBuilder($eventObj->getText());
				$bot->replyMessage($replyToken,	$textReplyMessage);
			}
		}
		
	}
 
}
