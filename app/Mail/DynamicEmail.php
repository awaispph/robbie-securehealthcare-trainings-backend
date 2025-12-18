<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DynamicEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subject;
    public $htmlContent;

    public function __construct($subject, $htmlContent)
    {
        $this->subject = $subject;
        $this->htmlContent = $htmlContent;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->html($this->htmlContent);
    }
}
