<?php
namespace Radical\Utility\Net\Mail\Handler;

use Radical\Utility\Net\Mail\Message;
use PHPMailer\PHPMailer\PHPMailer as PM;

class PhpMailer implements IMailHandler {
	protected $mailers;

    protected function mailer($i = 0, Message $message = null){
    	$mailers = $this->mailers;
		if(is_callable($this->mailers)){
			$mailers = $this->mailers;
			$mailers = $mailers();
		}

        if($mailers instanceof PM) {
            return $mailers;
        }
        if(is_array($mailers)){
        	if(count($mailers) > $i){
        		return $mailers[$i];
			}else{
        		return null;
			}
		}else if($i != 0){
        	return null;
		}
        throw new \Exception('Not initialized correctly with an instance of PHPMailer');
    }

    private function _getPart($full, $idx){
        if(preg_match('`([^\\<]+) \\<([^\\>]+)\\>`', $full, $m)){
            return $m[$idx];
        }
        return null;
    }
    private function _getNamePart($full){
        $name = $this->_getPart($full, 1);
        if(!$name){
            return $full;
        }
        return '';
    }
    private function _getEmailPart($full){
        $name = $this->_getPart($full, 2);
        if(!$name){
            return $full;
        }
        return $name;
    }

	function __construct($mailer){
        $this->mailers = $mailer;
	}
	function send(Message $message){
        $body = $message->getBody();
        for($i=0;$i<3;$i++){
			$mail = $this->mailer($i, $message);
			if($mail == null){
				return false;
			}
			$mail = clone $mail;
        	$success = $this->_send($body, $message, $mail);
        	if($success){
        		return $success;
			}
		}
		return false;
	}
    function getLastError(){
        return $this->mailer()->ErrorInfo;
    }

	private function _send($body, Message $message, PM $mail)
	{
		$mail->clearAttachments();
		$mail->clearAddresses();
		$mail->From = $this->_getEmailPart($message->getFrom());
		$mail->FromName = $this->_getNamePart($message->getFrom());
		$mail->addAddress($this->_getEmailPart($message->getTo()), $this->_getNamePart($message->getTo()));     // Add a recipient
		$mail->CharSet = 'UTF-8';
		if($mail->SMTPSecure == 'ssl') {
            $mail->SMTPOptions = array('ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ));
        }

		if($message->getReplyTo()) {
			$mail->addReplyTo($this->_getEmailPart($message->getReplyTo()), $this->_getNamePart($message->getReplyTo()));
		}

		foreach($message->getAttachments() as $key => $file){
			if(is_numeric($key)){
				$mail->addAttachment($file);
			}else{
				$mail->addAttachment($file, $key, 'base64', PM::filenameToType($key));
			}
		}

		$mail->isHTML($message->getHtml());
		if($message->getHtml()) {                      // Set email format to HTML
			$mail->Body = $body;
			$mail->AltBody = $message->getAltBody();
		}else{
			$mail->Body = $body;
		}

		$mail->Subject = $message->getSubject();

		if($message->getHeaders()) {
			foreach ($message->getHeaders() as $header => $value) {
				$mail->addCustomHeader($header, $value);
			}
		}

		return $mail->send();
	}
}