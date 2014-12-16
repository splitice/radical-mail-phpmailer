<?php
namespace Radical\Utility\Net\Mail\Handler;

use Html2Text\Html2Text;
use Radical\Utility\Net\Mail\Message;

class PhpMailer implements IMailHandler {
	private $mailer;

    private function mailer(){
        if($this->mailer instanceof \PHPMailer) {
            return $this->mailer;
        }
        if(is_callable($this->mailer)){
            $mailer = $this->mailer;
            return $mailer();
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
        return $name;
    }
    private function _getEmailPart($full){
        $name = $this->_getPart($full, 2);
        if(!$name){
            return $full;
        }
        return $name;
    }

	function __construct($mailer){
        $this->mailer = $mailer;
	}
	function send(Message $message){
        $body = $message->getBody();
        $mail = $this->mailer();
        $mail->From = $this->_getEmailPart($message->getFrom());
        $mail->FromName = $this->_getNamePart($message->getFrom());
        $mail->addAddress($this->_getEmailPart($message->getTo()), $this->_getNamePart($message->getTo()));     // Add a recipient

        if($message->getReplyTo()) {
            $mail->addReplyTo($this->_getEmailPart($message->getReplyTo()), $this->_getNamePart($message->getReplyTo()));
        }

        if($message->getHtml()) {
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Body = $body;
            $h2t = new Html2Text($body);
            $mail->AltBody = $h2t->get_text();
        }else{
            $mail->Body = $mail->AltBody = $body;
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