<?php

Class Mailable
{
    public $recipient;
    public $from = null;
    public $name = null;
    public $cc = null;
    public $bcc = null;
    public $subject = '';
    public $content = '';
    public $attachments = array();
    public $inline_attachments = array();
    public $data_attachments = array();

    public $view;
    public $html;
    public $textView;
    public $viewData = array();

    /**
     * Set the sender of the message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return Mailable
     */
    public function from($address, $name = null)
    {
        $this->from = $address;
        $this->name = $name;
        return $this;
    }

    /**
     * Set the email recipient
     * 
     * @param  string  $recipient
     * @return Mailable
     */
    public function to($recipient)
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * Add CC recipients
     * 
     * @param  string|array  $recipients
     * @return Mailable
     */
    public function cc($recipients)
    {
        $this->cc = is_array($recipients) ? $recipients : array($recipients);
        return $this;
    }

    /**
     * Add BCC recipients
     * 
     * @param  string|array  $recipients
     * @return Mailable
     */
    public function bcc($recipients)
    {
        $this->bcc = is_array($recipients) ? $recipients : array($recipients);
        return $this;
    }

    /**
     * Set the email subject
     * 
     * @param  string  $subject
     * @return Mailable
     */
    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the view and view data for the message.
     *
     * @param  string  $view
     * @param  array  $data
     * @return $this
     */
    public function view($view, $data = array())
    {
        $this->view = $view;

        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }

    /**
     * Set the rendered HTML content for the message.
     *
     * @param  string  $html
     * @return $this
     */
    public function html($html)
    {
        $this->html = $html;

        return $this;
    }
    
    /**
     * Set the plain text view for the message.
     *
     * @param  string  $textView
     * @param  array  $data
     * @return $this
     */
    public function text($textView, $data = array())
    {
        $this->textView = $textView;
        $this->viewData = array_merge($this->viewData, $data);

        return $this;
    }
    
    /**
     * Set the view data for the message.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return Mailable
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->viewData = array_merge($this->viewData, $key);
        } else {
            $this->viewData[$key] = $value;
        }

        return $this;
    }

    function embed($path)
    {
        $id = Str::uuid()->__toString();
        
        $this->inline_attachments[] = array(
            'file' => Attachment::fromPath($path)->__as($id),
            'local' => strpos($path, Storage::path(''))===0 ? Storage::path('') : ''
        );

        return 'cid:' . $id;
    }

    function embedData($data, $name)
    {
        $this->data_attachments[] = array(
            'name' => $name,
            'data' => $data
        );

        return 'cid:' . $name;
    }


    private function prepareMailableForDelivery($template)
    {
        if (method_exists($template, 'attachments')) {
            $this->attachments = $template->attachments();
        }

        if (is_string($template)) {
            return $template;
        }

        if (method_exists($template, 'build')) {
            $final = $template->build();

            foreach ($final as $key => $val) {
                if ($val) $this->$key = $val;
            }
        }

        $final->viewData['message'] = $this;

        $result = View::loadTemplate($final->view, $final->viewData);
        
        return $result;
    }

    /* public function plain($template)
    {
        $this->content = $template;
        
        $default = config('mail.default');
        
        if ($default=='smtp')
            return $this->sendSmtp();

        if ($default=='sendmail')
            return $this->sendMail();
    } */

    public function send($mailer)
    {
        $class = new ReflectionClass($mailer);

        if ($class->implementsInterface('ShouldQueue')) {
            return $this->queue($mailer);
        }

        $this->content = $this->prepareMailableForDelivery($mailer);
        
        $default = config('mail.default');
        
        if ($default=='smtp')
            return $this->sendSmtp();

        if ($default=='sendmail')
            return $this->sendMail();
    }

    public function queue($mailer)
    {
        $this->content = $this->prepareMailableForDelivery($mailer);

        DB::statement("CREATE TABLE IF NOT EXISTS `baradur_queue` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(50) NOT NULL,
            `status` INT(11) NOT NULL DEFAULT '0',
            `content` TEXT NOT NULL,
            PRIMARY KEY (`id`)
        )");
        
        DB::unprepared("INSERT INTO `baradur_queue` (type, status, content)
            VALUES ('Mail', 0, '" . serialize($this) . "')");

        return true;
    }

    public function sendSmtp()
    {
        //dd("Sending through PHPMAILER");
        //require_once(_DIR_.'vendor/PHPMailer/PHPMailerAutoload.php');

        require_once(_DIR_ . 'vendor/PHPMailer/class.phpmailer.php'); // PHP Mailer
        require_once(_DIR_ . 'vendor/PHPMailer/class.smtp.php'); // PHP Mailer SMTP support

        $default = config('mail.default');
        $mailers = config('mail.mailers'); 

        if (!$this->from) {
            $default_from = config('mail.from');
            $this->from = $default_from['address'];
            $this->name = $default_from['name'];
        }

        $mail = new PHPMailer();

        $mail->isSMTP();
        $mail->Host = $mailers[$default]['host'];
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = $mailers[$default]['encryption'];
        $mail->SMTPAutoTLS = $mail->SMTPSecure != '';
        $mail->Username = $mailers[$default]['username'];
        $mail->Password = $mailers[$default]['password'];
        $mail->Port = $mailers[$default]['port'];

        $mail->setFrom($this->from, $this->name);
        $mail->addAddress($this->recipient);

        if (isset($this->cc)) {
            if (is_array($this->cc)) {
                foreach ($this->cc as $r) {
                    $mail->addCC($r);
                }
            } else {
                $mail->addCC($this->cc);
            }
        }

        if (isset($this->bcc)) {
            if (is_array($this->bcc)) {
                foreach ($this->bcc as $r) {
                    $mail->addBCC($r);
                }
            } else {
                $mail->addBCC($this->bcc);
            }
        }

        foreach ($this->attachments as $attachment) {            
            $filename = $attachment->as ? $attachment->as : basename($attachment->path);

            if ($attachment->disk!==null) {
                Storage::delete($filename);
                $content = Storage::disk($attachment->disk)->get($attachment->path);
                Storage::put($filename, $content);
                $attachment->path = Storage::path($filename);
            } else {
                $attachment->path = Storage::path($attachment->path);
                //dd(Storage::path(''));
            }
            
            $mail->addAttachment(
                $attachment->path,
                $attachment->as ? $attachment->as : '',
                'base64',
                $attachment->mime ? $attachment->mime : ''
            );
        }

        foreach ($this->inline_attachments as $object) {
            $attachment = $object['file'];       
            $filename = basename($attachment->path);

            // If file is in local storage then replace with current path
            // this prevents errors in artisan console
            $attachment->path = str_replace($object['local'], Storage::path(''), $attachment->path);

            $mail->addEmbeddedImage(
                $attachment->path, 
                $attachment->as,
                basename($attachment->path)
            );
        }

        foreach ($this->data_attachments as $attachment) {            
            $filename = basename($attachment['name']);

            $mail->addStringEmbeddedImage(
                $attachment['data'], 
                $attachment['name'],
                $attachment['name']
            );

            //dump(base64_encode($attachment['data']));
        }

        $mail->Subject = $this->subject;
        $mail->Body = $this->content;
        $mail->isHTML(true);

        //dd($mail);
        
        //$mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;

        return $mail->send();

    }

    public function sendMail()
    {
        //echo "Sending through sendmail<br>";
        
        $from = config('mail.from'); 

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