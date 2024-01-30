<?php

Class Mail
{
    /**
     * Set the sender of the message.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return Mailable
     */
    public function from($address, $name = null)
    {
        $res = new Mailable;
        return $res->from($address, $name);
    }

    /**
     * Set the email recipient
     * 
     * @param  string  $recipient
     * @return Mailable
     */
    public static function to($recipient)
    {
        $res = new Mailable;
        return $res->to($recipient);
    }

    /**
     * Add CC recipients
     * 
     * @param  string  $address
     * @return Mailable
     */
    public static function cc($recipients)
    {
        $res = new Mailable;
        return $res->cc($recipients);
    }

    /**
     * Add BCC recipients
     * 
     * @return Mailable
     */
    public static function bcc($recipients)
    {
        $res = new Mailable;
        return $res->bcc($recipients);
    }

    /**
     * Set the email subject
     * 
     * @return Mailable
     */
    public static function subject($subject)
    {
        $res = new Mailable;
        return $res->subject($subject);
    }

    /**
     * Set the view data for the message.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return Mailable
     */
    public static function with($key, $value = null)
    {
        $res = new Mailable;
        return $res->with($key, $value);
    }

}