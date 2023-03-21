<?php

trait Notifiable
{
    public function notify($notification)
    {
        Mail::to($this->email)->send($notification);
    }

}