<?php

Class Mail
{
    private static $_instance;
    public $recipient;
    public $cc = null;
    public $bcc = null;
    public $subject = '';
    public $content = '';

    
    /**
     * Get Route instance
     * 
     * @return Mail
     */
    public static function getInstance()
    {
        if (!self::$_instance)
            self::$_instance = new Mail();

        return self::$_instance;
    }


    /**
     * Set the email recipient
     * 
     * @return Mail
     */
    public static function to($recipient)
    {
        $res = self::getInstance();
        $res->recipient = $recipient;
        return $res;
    }

    /**
     * Add CC recipients
     * 
     * @return Mail
     */
    public static function cc($recipients)
    {
        $res = self::getInstance();
        $res->cc = $recipients;
        return $res;
    }

    /**
     * Add BCC recipients
     * 
     * @return Mail
     */
    public static function bcc($recipients)
    {
        $res = self::getInstance();
        $res->bcc = $recipients;
        return $res;
    }

    /**
     * Set the email subject
     * 
     * @return Mail
     */
    public static function subject($subject)
    {
        $res = self::getInstance();
        $res->subject = $subject;
        return $res;
    }

    private function buildTemplate($template)
    {
        $final = $template->build(); 

        $vars = array();
        $view = $final->_template;
        unset($final->_template);

        foreach ($final as $key => $val)
        {
            $vars[$key] = $val;
        }

        $result = View::loadTemplate($view, $final);

        $this->content = $result;
    }


    public function send($template)
    {
        $this->buildTemplate($template);

        $default = Helpers::config('mail.default');
        
        if ($default=='smtp')
            return $this->sendSmtp();

        if ($default=='sendmail')
            return $this->sendMail();
    }

    public function queue($template)
    {
        $this->buildTemplate($template);

        DB::query("CREATE TABLE IF NOT EXISTS `baradur_queue` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(50) NOT NULL,
            `status` INT(11) NOT NULL DEFAULT '0',
            `content` TEXT NOT NULL,
            PRIMARY KEY (`id`)
        )");
        
        DB::table('baradur_queue')->create(array(
            'type' => 'Mail',
            'status' => 0,
            'content' => serialize($this)
        ));      
    }

    public function sendSmtp()
    {
        //echo "Sending through PHPMAILER<br>";
        require_once(_DIR_.'vendor/PHPMailer/PHPMailerAutoload.php');

        $default = Helpers::config('mail.default');
        $mailers = Helpers::config('mail.mailers'); 
        $from = Helpers::config('mail.from'); 

        $mail = new PHPMailer();

        $mail->isSMTP();
        $mail->Host = $mailers[$default]['host'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $mailers[$default]['encryption'];
        $mail->Username = $mailers[$default]['username'];
        $mail->Password = $mailers[$default]['password'];
        $mail->Port = $mailers[$default]['port'];

        $mail->setFrom($from['address'], $from['name']);
        $mail->addAddress($this->recipient);

        if (isset($this->cc))
        {
            if (is_array($this->cc))
            {
                foreach ($this->cc as $r)
                    $mail->addCC($r);
            }
            else
            {
                $mail->addCC($this->cc);
            }
        }

        if (isset($this->bcc))
        {
            if (is_array($this->bcc))
            {
                foreach ($this->bcc as $r)
                    $mail->addBCC($r);
            }
            else
            {
                $mail->addBCC($this->bcc);
            }
        }

        $mail->Subject = $this->subject;
        $mail->Body = $this->content;

        //$mail->SMTPDebug = SMTP::DEBUG_SERVER;

        return $mail->send();

    }

    public function sendMail()
    {
        //echo "Sending through sendmail<br>";

        $from = Helpers::config('mail.from'); 

        $encoding = "utf-8";
        $subject_preferences = array(
            "input-charset" => $encoding,
            "output-charset" => $encoding,
            "line-length" => 76,
            "line-break-chars" => "\r\n"
        );

        $header = "MIME-Version: 1.0 \r\n";
        $header .= "Content-type: text/html; charset=utf-8 \r\n";
        $header .= "Content-Transfer-Encoding: 8bit \r\n";
        $header .= "Date: ".date("r (T)")." \r\n";
        $header .= "From: ".$from['name']." <".$from['address']."> \r\n";
        $header .= 'To: '.$this->recipient."\r\n";
        $header .= iconv_mime_encode("Subject", $this->subject, $subject_preferences);
        
        return mail($this->recipient, $this->subject, $this->content, $header);

        //$this->xxmail('cepiperez@gmail.com', $this->subject, $content, $header);

    }




}