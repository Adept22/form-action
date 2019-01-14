<?php

define('SITE_NAME', 'www.mysite.ru');
define('NAME_FROM', 'My Site');
define('EMAIL_FROM', 'info@mysite.ru');

require_once $_SERVER['DOCUMENT_ROOT'] . '/path/to/PHPMailer.php';

function clearData(&$data) {
	foreach ($data as $key => $value) {
	     if(!is_array($value)) {
	          $data[$key] = htmlspecialchars(stripslashes(trim($value)));
	     } else {
	          $result = clearData($value);
	          if($result) return false;
	     }
	}
	return true;
}

function buildList($data, $wrapper = 'ol') {
     $html = "<{$wrapper} style='margin-top: 0;'>";

     foreach ($data as $value) {
          if(!empty($value['value'])) {
			$html .= "<li>";

			if(isset($value['name']) && !empty($value['name'])) $html .= $value['name'] . ": ";

			if(is_array($value['value'])) {
				$html .= "<{$wrapper} style='margin-top: 0;'>";
				foreach($value['value'] as $val)
		               if(!empty($val)) $html .= "<li>". $val . "</li>";
				$html .= "</{$wrapper}>";
	          } else {
	               $html .= $value['value'];
			}

			$html .= "</li>";
          }

          if(isset($value['children'])) {
			$html .= "<li>";

               if(isset($value['name']) && !empty($value['name'])) $html .= $value['name'] . ":";
               $html .= buildList($value['children'], $wrapper);

			$html .= "</li>";
          }
     }

     $html .= "</{$wrapper}>";

     return $html;
}

function sendMail($to = array(), $subject, $message, $attachments = array()) {
	$mail = new PHPMailer\PHPMailer\PHPMailer();
     $mail->CharSet = 'UTF-8';
     $mail->From = EMAIL_FROM;
     $mail->FromName = NAME_FROM;

	if(!empty($to)) {
		foreach($to as $item) {
			$mail->AddAddress($item['email'], $item['name']);
		}
	}

     $mail->IsHTML(true);
     $mail->Subject = $subject;
     $mail->Body = $message;

	if(!empty($attachments)) {
		foreach($attachments as $attachment) {
			$mail->addAttachment($attachment['path'], $attachment['name']);
		}
	}

     if (!$mail->Send()) {
          return false;
     }

	return true;
}

header('Content-Type: application/json');

if(isset($_POST['form']) && !empty($_POST['form'])) {
     $data = $_POST['form'];

     clearData($data['fields']);

     $message = "<!DOCTYPE html><head><title>Поступила заявка с сайта " . SITE_NAME . "</title></head><body>";
     $message .= "<h1>Поступила заявка с сайта " . SITE_NAME . "</h1>";
     $message .= "<p>Заголовок формы: " . $data['name'] . "</p>";
     $message .= "<p>ID формы: " . $data['id'] . "</p>";

     $message .= buildList($data['fields']);

     $message .= "</body></html>";

	$file = array();

     if(isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
		$file[] = array(
			'path' => $_FILES['file']['tmp_name'],
			'name' => $_FILES['file']['name']
		);
	}

     sendMail(array(array('email' => EMAIL_FROM, 'name' => NAME_FROM)), 'Поступила заявка с сайта ' . SITE_NAME, $message, $file);

     echo json_encode(array('status' => true, 'data' => $data));
} else {
	echo json_encode(array('status' => false, 'data' => $data));
}
