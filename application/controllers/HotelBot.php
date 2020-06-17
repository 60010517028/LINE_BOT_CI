<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use LINE\LINEBot;
use LINE\LINEBot\HTTPClient;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\Event;
use LINE\LINEBot\Event\BaseEvent;
use LINE\LINEBot\Event\MessageEvent;
use LINE\LINEBot\Event\AccountLinkEvent;
use LINE\LINEBot\Event\MemberJoinEvent; 
use LINE\LINEBot\MessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\ImageMessageBuilder;
use LINE\LINEBot\MessageBuilder\LocationMessageBuilder;
use LINE\LINEBot\MessageBuilder\AudioMessageBuilder;
use LINE\LINEBot\MessageBuilder\VideoMessageBuilder;
use LINE\LINEBot\ImagemapActionBuilder;
use LINE\LINEBot\ImagemapActionBuilder\AreaBuilder;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapMessageActionBuilder ;
use LINE\LINEBot\ImagemapActionBuilder\ImagemapUriActionBuilder;
use LINE\LINEBot\MessageBuilder\Imagemap\BaseSizeBuilder;
use LINE\LINEBot\MessageBuilder\ImagemapMessageBuilder;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\DatetimePickerTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\PostbackTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ImageCarouselColumnTemplateBuilder;
use LINE\LINEBot\QuickReplyBuilder;
use LINE\LINEBot\QuickReplyBuilder\QuickReplyMessageBuilder;
use LINE\LINEBot\QuickReplyBuilder\ButtonBuilder\QuickReplyButtonBuilder;
use LINE\LINEBot\TemplateActionBuilder\CameraRollTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\CameraTemplateActionBuilder;
use LINE\LINEBot\TemplateActionBuilder\LocationTemplateActionBuilder;
use LINE\LINEBot\RichMenuBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuSizeBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBuilder;
use LINE\LINEBot\RichMenuBuilder\RichMenuAreaBoundsBuilder;
use LINE\LINEBot\Constant\Flex\ComponentIconSize;
use LINE\LINEBot\Constant\Flex\ComponentImageSize;
use LINE\LINEBot\Constant\Flex\ComponentImageAspectRatio;
use LINE\LINEBot\Constant\Flex\ComponentImageAspectMode;
use LINE\LINEBot\Constant\Flex\ComponentFontSize;
use LINE\LINEBot\Constant\Flex\ComponentFontWeight;
use LINE\LINEBot\Constant\Flex\ComponentMargin;
use LINE\LINEBot\Constant\Flex\ComponentSpacing;
use LINE\LINEBot\Constant\Flex\ComponentButtonStyle;
use LINE\LINEBot\Constant\Flex\ComponentButtonHeight;
use LINE\LINEBot\Constant\Flex\ComponentSpaceSize;
use LINE\LINEBot\Constant\Flex\ComponentGravity;
use LINE\LINEBot\Constant\Flex\BubleContainerSize;
use LINE\LINEBot\MessageBuilder\FlexMessageBuilder;
use LINE\LINEBot\MessageBuilder\Flex\BubbleStylesBuilder;
use LINE\LINEBot\MessageBuilder\Flex\BlockStyleBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\BubbleContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ContainerBuilder\CarouselContainerBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\BoxComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ButtonComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\IconComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\ImageComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\SpacerComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\FillerComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\SeparatorComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\TextComponentBuilder;
use LINE\LINEBot\MessageBuilder\Flex\ComponentBuilder\SpanComponentBuilder;

class HotelBot extends CI_Controller {

    private $replyToken;
    private $UID;
    private $eventObj;
    private $timestamp;
    private $bot;

    private $member;

    public function __construct(){

		parent::__construct();

		$this->load->model('Hotel_model');

	}

	public function index()
	{

		$httpClient = new CurlHTTPClient($this->config->item('HOTEL_LINE_TOKEN'));
        $this->bot = new LINEBot($httpClient, array('channelSecret' => $this->config->item('HOTEL_CHANEL_SECRET')));

        $content = file_get_contents('php://input');
        
        if($content=="") exit;

		log_message('INFO',$content);

        //กำหนด signature สำหรับตรวจสอบความถูกต้องจาก Line
        $hash = hash_hmac('sha256', $content,$this->config->item('HOTEL_CHANEL_SECRET'), true);
        $signature = base64_encode($hash);

        //แปลงข้อมูลท่ได้จาก Line เป้น Array
        $events = $this->bot->parseEventRequest($content, $signature);     

        foreach ($events as $eventObj) {
        
           $this->eventObj = $eventObj;
           $this->timestamp = $this->eventObj->getTimestamp();
           $this->UID = $this->eventObj->getUserId();
           $this->replyToken = $this->eventObj->getReplyToken();			
           $inputType = $this->eventObj->getType();
           
           $this->log(
               array(
                   'LOG_UID' => $this->UID,
                   'LOG_TYPE' => $inputType,
                   'LOG_DETAIL' => $content,
                   'LOG_DATE' => date('Y-m-d H:i:s')
               )
            );

			if($inputType == 'message'){

                $messageType = $this->eventObj->getMessageType();

                if($messageType == 'text'){

                    $message = trim($this->eventObj->getText());
                    $param_arg = explode(':',$message);

                    switch($param_arg[0]):
                        case 'รายละเอียดห้องพัก':
                            $this->room_type();
                        break;

                        case 'ซื้อสินค้าออนไลน์':
                            $this->shopping();
                        break;
                        case 'โปรโมชั่น':
                            $this->promotion();
                        break;
                        case 'ติดต่อเรา':
                            $this->contact();
                        break;
                        case 'ตั้งค่าบริการ':
                            $this->setting_service();
                        break;
                        case 'แจ้งปัญหา':
                            $this->report();
                        break;
                        case 'location':
                            $this->location_reply();
                        break;
                        case 'รายเดือน':
                            $this->pay_month();
                        break;
                    endswitch;

                }else if($messageType == 'location'){
                    $this->location();
                }

            }else if($inputType == 'follow'){
                $this->follow();
            }else if($inputType == 'unfollow'){
                $this->unfollow();
            }else if($inputType == 'postback'){
				$this->postback();
			}
            
            
		}
		
    }

    private  function postback(){
    	$textReplyMessage = new TextMessageBuilder('AAAA');
    	$this->bot->replyMessage($this->replyToken,$textReplyMessage);
	}

    private  function room_type(){

		$container = array();
		$bubble1 = new  BubbleContainerBuilder(
			NULL,
			NULL,
			NULL,
			// new ImageComponentBuilder("https://www.img.in.th/images/6b0986941e14162a2bb47b67640ca472.png",NULL,NULL,NULL,NULL,"full","20:13","cover"),
			new BoxComponentBuilder(
				"horizontal",
				array(
					new TextComponentBuilder("ห้องพัก",NULL,NULL, NULL,NULL,NULL,true)
				)
			),
			new BoxComponentBuilder(
				"horizontal",
				array(
					new ButtonComponentBuilder(
						new PostbackTemplateActionBuilder(
							'จองห้องพัก', // ข้อความแสดงในปุ่ม
							http_build_query(array(
								'action'=>'register',
								'UID'=> $this->userId
							)), // ข้อมูลที่จะส่งไปใน webhook ผ่าน postback event
							'จองห้องพัก'  // ข้อความที่จะแสดงฝั่งผู้ใช้ เมื่อคลิกเลือก
						),
						NULL,NULL,NULL,'link'
					)
				)
			)
		);

		$container[] = $bubble1;
		$container[] = $bubble1;

		$flexMessage  = new CarouselContainerBuilder($container);

		// log_message('INFO','BOSS--OK'.json_encode($bubble1));
		$flexMessage = new FlexMessageBuilder("ห้องพัก",$flexMessage);
		$this->bot->replyMessage($this->replyToken,$flexMessage);
	}

    private function follow(){

        $date = date('Y-m-d H:i:s');

        if($this->get_member()){

            $this->Hotel_model->update('_MEMBER',
            array(
                'FOLLOW_STATUS' => 'Y',
                'FOLLOW_DATE'   =>  $date
                ),
             array(
                  'UID' => $this->UID
                  )
            );

        }else{

            $this->Hotel_model->insert('_MEMBER',
            array(
                'UID' => $this->UID,
                'FOLLOW_STATUS' => 'Y',
                'FOLLOW_DATE'   =>  $date
                )
            );
        }
    

        $textReplyMessage = new TextMessageBuilder('follow now.'.  $date);
        $this->bot->replyMessage($this->replyToken, $textReplyMessage);

    }

    private function unfollow(){

        $date = date('Y-m-d H:i:s');

        if($this->get_member()){

            $this->Hotel_model->update('_MEMBER',
                array(
                    'FOLLOW_STATUS' => 'N',
                    'FOLLOW_DATE'   => $date
                    ),
                array(
                    'UID' => $this->UID
                    )
                );
            
        }else{
            
            $this->Hotel_model->insert('_MEMBER',
            array(
                'UID' => $this->UID,
                'FOLLOW_STATUS' => 'N',
                'FOLLOW_DATE'   =>  $date
                )
            );

        }
    }

    private function report(){
        $textReplyMessage = new TextMessageBuilder('ระบุปัญหาที่คุณกำลังเผชิญ');
        $this->bot->replyMessage($this->replyToken,$textReplyMessage);

    }

    private function setting_service(){
        
        $imageMapUrl = "https://ktdev.site/LINE_Hotel_CI/assets/images/setting.jpg?_ignored=";

        $textReplyMessage = new ImagemapMessageBuilder(
            $imageMapUrl,
            'ตั้งค่าบริการ',
            new BaseSizeBuilder(619,1200),
            array(
                //เปลี่ยนหมายเลข
                new ImagemapUriActionBuilder(
                    'http://truemoveh.truecorp.co.th/package/postpaid',
                    new AreaBuilder(12,124,564,479)
                ),
                new ImagemapMessageActionBuilder(
                    'บริการแจ้งเตือนยอดใช้บริการ',
                    new AreaBuilder(618,128,564,479)
                )
            )
        );
        $this->bot->replyMessage($this->replyToken,$textReplyMessage);
    }

    private function pay_month(){
        $container = array();
        $bubble1 = new  BubbleContainerBuilder(
                        NULL,
                        NULL,
                        NULL,
                        // new ImageComponentBuilder("https://www.img.in.th/images/6b0986941e14162a2bb47b67640ca472.png",NULL,NULL,NULL,NULL,"full","20:13","cover"),
                        new BoxComponentBuilder(
                            "horizontal",
                            array(
                                new TextComponentBuilder("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",NULL,NULL,NULL,NULL,NULL,true)
                            )
                        ),
                        new BoxComponentBuilder(
                            "horizontal",
                            array(
                                new ButtonComponentBuilder(
                                    new UriTemplateActionBuilder("GO","http://niik.in"),
                                    NULL,NULL,NULL,"primary"
                                )
                            )
                        )
        );
       
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;
        $container[] = $bubble1;

        $flexMessage  = new CarouselContainerBuilder($container);

        // log_message('INFO','BOSS--OK'.json_encode($bubble1));
        $flexMessage = new FlexMessageBuilder("เลือกแพคเก็จ",$flexMessage);
        $this->bot->replyMessage($this->replyToken,$flexMessage);

    }

    private function promotion(){
 
        $imageMapUrl = 'https://ktdev.site/LINE_Hotel_CI/assets/images/promotion-new1.jpg?_ignored=';
        $textReplyMessage = new ImagemapMessageBuilder(
            $imageMapUrl,
            'โปรโมชั่นสินค้า',
            new BaseSizeBuilder(1800,1200),
            array(
                //ย้ายค่ายมาทรู
                new ImagemapUriActionBuilder(
                    'http://truemoveh.truecorp.co.th/move_to_true/postpaid',
                    new AreaBuilder(12, 130, 561, 307)
                ),
                //ซื้อแพ๊กเสริม
             new ImagemapUriActionBuilder(
                    'https://www.truemoney.com/mobile-topup/promotion-packagenet/',
                    new AreaBuilder(615, 144, 561, 307)
                ),
                   //โปรอุปกรณ์สื่อสาร
                new ImagemapUriActionBuilder(
                    'https://truemoveh.truecorp.co.th/device',
                    new AreaBuilder(15, 495, 561, 307)
                ),
               
                //สมัครบริการอื่นๆของทรู
                new ImagemapMessageActionBuilder(
                    'สมัครบริการ',
                    new AreaBuilder(615,492, 561, 307)
                ),
               // ตรวจสอบข้อมูลการใช้งาน
                new ImagemapMessageActionBuilder(
                    'ตรวจสอบข้อมูลการใช้งาน',
                    new AreaBuilder(15,954, 561, 307)
                ),                
                //สอบถามค่าบริการ
                new ImagemapMessageActionBuilder(
                    'สอบถามค่าบริการ',
                    new AreaBuilder(615,954, 561, 307)
                )
                ,
                 //แจ้งปัญหา
                  new ImagemapMessageActionBuilder(
                    'แจ้งปัญหา',
                    new AreaBuilder(15,1314, 561, 307)
                ),
                //เปลี่ยนแพ็กเกจ
                new ImagemapMessageActionBuilder(
                    'เปลี่ยนแพ็กเกจ',
                    new AreaBuilder(648,1320, 561, 307)
                ),
               // ตั้งค่าบริการ
                new ImagemapMessageActionBuilder(
                    'ตั้งค่าบริการ',
                    new AreaBuilder(15,1662, 1149, 124)
                ) 
                
            )
        );

        $this->bot->replyMessage($this->replyToken,$textReplyMessage);


    }


    private function contact(){

        $textReplyMessage = "";

        $imageMapUrl = 'https://ktdev.site/LINE_BOT/rich.png?_ignored=';
        $textReplyMessage = new ImagemapMessageBuilder(
            $imageMapUrl,
            'โปรโมชั่นสินค้า',
            new BaseSizeBuilder(810,1200),
            array(
                new ImagemapMessageActionBuilder(
                    'IMAGE-1',
                    new AreaBuilder(0,0,600,405)
                ),
                new ImagemapMessageActionBuilder(
                    'IMAGE-2',
                    new AreaBuilder(600,0,600,405)
                ),
                new ImagemapUriActionBuilder(
                    'https://tededkaichon.com/',
                    new AreaBuilder(0,405,600,405)
                ),
                new ImagemapUriActionBuilder(
                    'https://tededkaichon.com/',
                    new AreaBuilder(600,405,600,405)
                )
            )
        );

        $this->bot->replyMessage($this->replyToken,$textReplyMessage);

    }

    //ประเภท ตำแหน่ง
    private function location(){

        $title = $this->eventObj->getTitle();
        $address = $this->eventObj->getAddress();

        if(is_null($title)){
            $title = 'ตำแหน่ง';
        }
        if(is_null($address)){
            $address = "ที่อยู่";
        }

        $textReplymessage = new LocationMessageBuilder(
            $title,
            $address,
            $this->eventObj->getLatitude(),
            $this->eventObj->getLongitude()
        );

        $this->bot->replyMessage($this->replyToken,$textReplymessage);

    }

    //ส่งกลับตำแหน่ง
    private function location_reply(){

        $title = 'WYNNSOFT SOLUTION CO,LTD.';
        $address ='120/34-35 Moo 24 Mueang Khon Kaen District, Khon Kaen 40000';

        $textReplymessage = new LocationMessageBuilder(
            $title,
            $address,
            16.487450,
            102.835103
        );

        $this->bot->replyMessage($this->replyToken,$textReplymessage);

    }

    
    private function member($work){
           
        switch($work):
            case 'confirm':
                $textReplyMessage = new TextMessageBuilder('confirm');
                $this->bot->replyMessage($this->replyToken, $textReplyMessage);
            break;

            case 'upgrade':
                $textReplyMessage = new TextMessageBuilder('upgrade');
                $this->bot->replyMessage($this->replyToken, $textReplyMessage);
            break;

        endswitch;

    }

    private function movie(){

        $movieResult = $this->Hotel_model->getURLList('MOVIE');
        $movieweblist = "## เว็บดูหนังออนไลน์ ## "."\n";
        foreach ($movieResult as $mov) {
            $ADS = "";
            if($mov->URL_ADS  > 0){
                $ADS = " (".$mov->URL_ADS." คลิป) ";
            }
            $movieweblist .= $mov->URL_LINK.$ADS."\n";
            $movieweblist .= "🎬"."\n";
        }

        $textReplyMessage = new TextMessageBuilder($movieweblist);
        $this->bot->replyMessage($this->replyToken, $textReplyMessage);

    }

    private function shopping(){

        $shopResult = $this->Hotel_model->getURLList('SHOP');
        $shopweblist = "## เว็บดูซื้อสินค้าออนไลน์ ## "."\n";
        foreach ($shopResult as $shop) {
            
            $shopweblist .= $shop->URL_LINK."\n";
            $shopweblist .= "🛒"."\n";
        }

        $textReplyMessage = new TextMessageBuilder($shopweblist);
        $this->bot->replyMessage($this->replyToken, $textReplyMessage);

    }

    private function program($work){

        switch($work):
            case 'live':
                $textReplyMessage = new TextMessageBuilder('รายการถ่ายทอดสด');
                $this->bot->replyMessage($this->replyToken, $textReplyMessage);
            break;

        endswitch;

    }
 
    private function get_member(){

        if($this->member){
            return $this->member;
        }else{
            $this->member = $this->Hotel_model->get_member($this->UID);
            return $this->member;
        }
    }

    private function log($data){
        $this->Hotel_model->insert_log($data);
    }
 
}
