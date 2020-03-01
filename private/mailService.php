<?php

require_once dirname(__DIR__) . '/phpmailer/PHPMailerAutoload.php';

$TO = 
[
    "romellocaccamisi@hotmail.com",
];

class MailService
{
    private $phpMailer;

    function __construct()
    {
        $this->forceActivate();
    }

    function forceActivate()
    {
        if(!empty($this->phpMailer)) return;
        global $TO;
        $this->phpMailer = new PHPMailer();
        $this->phpMailer->IsSMTP();
        $this->phpMailer->IsHTML(true);
        $this->phpMailer->CharSet = 'UTF-8';
        $this->phpMailer->Encoding = 'base64';
        $this->phpMailer->Host = "mail.pandaroo.yo.fr";
        $this->phpMailer->SMTPAuth = true;
        $this->phpMailer->Username = 'admin@pandaroo.yo.fr';
        $this->phpMailer->Password = 'JN138a12!';
        $this->phpMailer->From='admin@pandaroo.yo.fr';
        $this->phpMailer->FromName = "Error Pandaroo Admin";
        foreach($TO as $to)
            $this->phpMailer->AddAddress($to);
    }

    function notifyAdminError($subject, $message)
    {
        $this->forceActivate();
        $this->phpMailer->Subject = "[Error] $subject";
        $this->phpMailer->Body = $message;
        $this->phpMailer->Send();
    }

    function __destruct()
    {
        $this->close();
    }

    function close()
    {
        $this->phpMailer->smtpClose();
        unset($this->phpMailer);
    }
}

?>