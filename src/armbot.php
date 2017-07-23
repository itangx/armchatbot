<?php
require_once __DIR__ . '/../vendor/autoload.php';

use LINE\LINEBot\MessageBuilder\TextMessageBuilder ;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder ;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ConfirmTemplateBuilder ;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder ;

//connect database
$servername = "54.187.59.174";
$username = "itangx";
$password = "password";
$dbname = "LineChatBot";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

mysqli_set_charset($conn,"utf8");

$token = 'QFW5zx4qTkWfWsQKNlaOf5lDCgFTNt+wKV8rw5P/8UlQxbOqNarlInIwuoEcNqgwiJhZTHen75QixKLah1ttM+Ms6snrxNSPcYV+284HLUEEbflnJuN5xHBCsvsOjaqXyoCW3lHu8uWgMwzL5pgPjAdB04t89/1O/w1cDnyilFU=' ;
$secret = '255befc1f82d6539c481e5f593e92517' ;

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($token);
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $secret]);

$body = file_get_contents('php://input');
$events = json_decode($body, true);

foreach ($events['events'] as $event) {
	$msg = $event['message']['text'] ;

	switch ($msg) {
    case 'เริ่ม':

        $MessageBuilder = new TemplateMessageBuilder('ทดสอบ', new ConfirmTemplateBuilder('คุณเคยสมัครแล้วหรือยัง', 
		[ new MessageTemplateActionBuilder('เคยสมัครแล้ว', 'เคยสมัครแล้ว') , new MessageTemplateActionBuilder('ยังไม่เคยสมัคร', 'ยังไม่เคยสมัคร') ]) );
        $bot->replyMessage( $event['replyToken'] , $MessageBuilder);  
        break;

    case 'เคยสมัครแล้ว':

    	$sql = "SELECT * FROM user_table where lineID = '".$event['source']['userId']."'";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
	    	while($row = $result->fetch_assoc()) {
	    		$MessageBuilder = new TextMessageBuilder('ยินดีต้อนรับคุณ '.$row['userName']) ;
	    	}
		} else {
			$MessageBuilder = new TextMessageBuilder('มึงไม่เนียน มากรอกใบสมัครเลย') ;
			goto 'ยังไม่เคยสมัคร' ;
		}
        $bot->replyMessage( $event['replyToken'] , $MessageBuilder);  
        break;

    case 'ยังไม่เคยสมัคร':

    	$MessageBuilder = new TemplateMessageBuilder('ทดสอบ', new ConfirmTemplateBuilder('กรุณาระบุว่าเป็นอาจารย์ / นิสิต ', 
		[ new MessageTemplateActionBuilder('อาจารย์', 'อาจารย์') , new MessageTemplateActionBuilder('นิสิต', 'นิสิต') ]) );
		$bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

        break;

    case 'อาจารย์':

    	$MessageBuilder = new TextMessageBuilder('กรุณาระบุรหัสอาจารย์') ;
    	$bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

    	$sql = "INSERT INTO log (log_LineUserId, log_LastMsg, log_Session) VALUES ('".$event['source']['userId']."', 'กรุณาระบุรหัสอาจารย์', 'regis')";
    	$conn->query($sql) ;
        break;

    case 'นิสิต':
	
    	$MessageBuilder = new TextMessageBuilder('กรุณาระบุรหัสนิสิต') ;
    	$bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

    	$sql = "INSERT INTO log (log_LineUserId, log_LastMsg, log_Session) VALUES ('".$event['source']['userId']."', 'กรุณาระบุรหัสนิสิต', 'regis')";
    	$conn->query($sql) ;
        break;

    default:
    	$sql = "SELECT * FROM log where log_id = (SELECT MAX(log_Id) FROM log where log_LineUserId = '".$event['source']['userId']."')";
    	$result = $conn->query($sql) ;
    	if ($result->num_rows > 0) {
    		while($row = $result->fetch_assoc()) { 
    			if($row["log_Session"] == 'regis'){

					switch ($row["log_LastMsg"]) {
					case 'กรุณาระบุรหัสอาจารย์' :
						$MessageBuilder = new TemplateMessageBuilder('ทดสอบ', new ConfirmTemplateBuilder('ยืนยันรหัส '.$msg, 
						[ new MessageTemplateActionBuilder('ใช่', 'ใช่') , new MessageTemplateActionBuilder('ไม่ใช่', 'ไม่ใช่') ]) );
						$response = $bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

						$sql = "UPDATE log SET log_LastMsg='ยืนยันรหัส', log_Session='regis', userMsg='".$msg."' where log_Id = '".$row["log_Id"]."'";
    					$conn->query($sql) ;
						break;

					case 'ยืนยันรหัส' :
						if($msg == 'ใช่') {
							$sql = "INSERT INTO user_table (userID, lineID, userTypeNo) VALUES ('".$row["userMsg"]."', '".$event['source']['userId']."', 1)";
    						$conn->query($sql) ;

    						$MessageBuilder = new TextMessageBuilder('กรุณาระบุุชื่ออาจารย์') ;
    						$bot->replyMessage( $event['replyToken'] , $MessageBuilder);    

    						$sql = "UPDATE log SET log_LastMsg = 'กรุณาระบุุชื่ออาจารย์', log_Session ='regis', userMsg = '".$msg."' where log_Id = '".$row["log_Id"]."'";
    						$conn->query($sql) ;
						} else {
							$MessageBuilder = new TextMessageBuilder('กรุณาระบุรหัสอาจารย์') ;
    						$bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

    						$sql = "UPDATE log SET log_LastMsg = 'กรุณาระบุรหัสอาจารย์', log_Session ='regis', userMsg = '".$msg."' where log_Id = '".$row["log_Id"]."'";
    						$conn->query($sql) ;
						}
						break;

					case 'กรุณาระบุุชื่ออาจารย์' :
						$MessageBuilder = new TemplateMessageBuilder('ทดสอบ', new ConfirmTemplateBuilder('ยืนยันชื่อ '.$msg, 
						[ new MessageTemplateActionBuilder('ใช่', 'ใช่') , new MessageTemplateActionBuilder('ไม่ใช่', 'ไม่ใช่') ]) );
						$response = $bot->replyMessage( $event['replyToken'] , $MessageBuilder);  

						$sql = "UPDATE log SET log_LastMsg='ยืนยันชื่อ', log_Session='regis', userMsg='".$msg."' where log_Id = '".$row["log_Id"]."'";
    					$conn->query($sql) ;
						break;

					case 'ยืนยันชื่อ' :
						if($msg == 'ใช่') {
							$sql = "UPDATE user_table SET userName='".$row["userMsg"]."', where lineID = '".$row["log_LineUserId"]."'";
    						$conn->query($sql) ;

    						$MessageBuilder = new TextMessageBuilder('เสร็จสิ้นการลงทะเบียน ขอบคุณครับ') ;
    						$bot->replyMessage( $event['replyToken'] , $MessageBuilder);    

    						$sql = "UPDATE log SET log_LastMsg = 'ผ่าน', log_Session ='regis', userMsg = '".$msg."' where log_Id = '".$row["log_Id"]."'";
    						$conn->query($sql) ;
						} else {
							$MessageBuilder = new TextMessageBuilder('กรุณาระบุุชื่ออาจารย์') ;
    						$bot->replyMessage( $event['replyToken'] , $MessageBuilder);    

    						$sql = "UPDATE log SET log_LastMsg = 'กรุณาระบุุชื่ออาจารย์', log_Session ='regis', userMsg = '".$msg."' where log_Id = '".$row["log_Id"]."'";
    						$conn->query($sql) ;
						}
						break;

					default:
			        	break;
					}

				} else {
					//do anythings
				}
    		}
    	} else {
    		$MessageBuilder = new TextMessageBuilder('world') ;
    		$bot->replyMessage( $event['replyToken'] , $MessageBuilder);  
    	}
    	break;
	}
	
}

$conn->close() ;

?>