<?php
require_once("./src/utils/utils.php");

class Mail {
    private $from;
    private $to;
    private $subject;
    private $htmlBody;
    private $attachments = array();

    private $mailBody;
    private $headers;
    private $boundary;
    private $attachmentIds = array();

    function __construct($from, $to, $subject, $htmlBody) {
        if ($from == "" || $to == "" || $subject == "" || $htmlBody == "") {
            throw new Exception("Required parameters missing");
        }

        $this->from     = $from;
        $this->to       = $to;
        $this->subject  = $subject;
        $this->htmlBody = $htmlBody;
    }

    public function addAttachment($attachment) {

        $str_attachmentId = (string)rand(1000, 9999) . "_" . $attachment;
        array_push($this->attachments, $attachment);
        array_push($this->attachmentIds, $str_attachmentId);

    }

    public function send() {

        $this->createHeaders();
        $this->createMailBody();

        mail($this->to, $this->subject, $this->mailBody, $this->headers);
    }

    public function createEmlFile() {
        $this->createHeaders();
        $this->createMailBody();

        $str_otherHeaders  = "From: <" . $this->from . ">\r\n";
        $str_otherHeaders .= "To: <" . $this->to . ">\r\n";
        $str_otherHeaders .= "Date: " . date("D, d M Y H:i:s O") . "\r\n";

        //$str_otherHeaders .= "Content-Transfer-Encoding: base64\r\n\r\n";
        //$str_otherHeaders .= "Subject: =?UTF-8?B?" . base64_encode($this->subject) . "'?='\r\n";
        $str_otherHeaders .= "Subject: " . $this->subject . "\r\n";
        
        $str_otherHeaders =  str_replace( "From: <" . $this->from . ">\r\n", $str_otherHeaders, $this->headers);
        
        $str_mail_content = $str_otherHeaders . $this->mailBody;

        create_text_file("message.eml", $str_mail_content);
    }

    private function setBoundary() {
        $str_random_number = (string)rand(1000, 99999);
        $this->boundary = md5( date("Ymdis") .  $str_random_number  );
    }

    private function createHeaders() {
        
        $this->setBoundary();
        
        $str_headers =  "MIME-Version: 1.0\r\n";
        $str_headers .= "X-Mailer: PHP-Script\r\n";
        //$str_headers .= "Return-Path: <" . $this->from . ">\r\n";
        $str_headers .= "From: <" . $this->from . ">\r\n";
        $str_headers .= "Content-type: multipart/mixed; ";
        $str_headers .= "boundary = " . $this->boundary . "\r\n";

        //$str_headers .= "Content-type: text/html; charset=utf-8";

        $this->headers = $str_headers;
    }

    private function encondingAttchmentImage($attachment, $attachmentId) {

        $obj_file = fopen("./src/assets/" . $attachment, "rb");
        $bin_content = fread($obj_file, filesize("./src/assets/" . $attachment));
        fclose($obj_file);

        $str_encoded_content = chunk_split(base64_encode($bin_content));
        
        $str_text_attchment  = "--". $this->boundary . "\r\n";

        $int_aux1 = strrpos($attachment, ".");

        $str_type = strtolower(substr( $attachment, $int_aux1+1 ));

        if ($str_type == "gif")
            $str_text_attchment .="Content-Type: image/gif; name=" . $attachment . "\r\n";
        if (($str_type == "jpeg") || ($str_type == "jpg"))
            $str_text_attchment .="Content-Type: image/jpeg; name=" . $attachment . "\r\n";
        if ($str_type == "png")
            $str_text_attchment .="Content-Type: image/png; name=" . $attachment . "\r\n";
        
        $str_text_attchment .= "Content-Disposition: attachment; filename=". $attachment ."\r\n";
        $str_text_attchment .= "Content-id: <" . $attachmentId . ">\r\n";
        $str_text_attchment .= "Content-Transfer-Encoding: base64\r\n\r\n";

        $str_text_attchment .= $str_encoded_content ."\r\n";

        return $str_text_attchment;
    }

    private function createMailBody() {
        $str_mail_body = "";

        $str_mail_body .= "\r\n--" . $this->boundary . "\r\n";
        $str_mail_body .= "Content-Transfer-Encoding: 8bits\r\n";
        //$str_mail_body .= "Content-Type: text/html; charset=\"iso-8859-1\"\r\n\r\n";
        $str_mail_body .= "Content-Type: text/html; charset=\"utf-8\"\r\n\r\n";

        $str_html_body = $this->htmlBody . "\r\n";
        $str_attachments_encoded = "";

        for ($int_i = 0; $int_i < count($this->attachments); $int_i++) {
            $str_html_body = str_replace($this->attachments[$int_i], "cid:" . $this->attachmentIds[$int_i],  $str_html_body);
            $str_attachments_encoded .= $this->encondingAttchmentImage($this->attachments[$int_i], $this->attachmentIds[$int_i]);
        }

        $str_mail_body .= $str_html_body . $str_attachments_encoded;

        $str_mail_body .= "\r\n--" . $this->boundary . "--\r\n";

        $this->mailBody = $str_mail_body;
    }
}

?>