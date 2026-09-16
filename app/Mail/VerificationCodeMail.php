<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
  use Queueable, SerializesModels;

  public $code;
  public $type;

  /**
   * Create a new message instance.
   */
  public function __construct(string $code, string $type = 'registration')
  {
    $this->code = $code;
    $this->type = $type;
  }

  /**
   * Build the message.
   */
  public function build()
  {
    // 1. Determine the subject and view dynamic based on the type
    if ($this->type === 'registration') {
      $subject = 'Email verification code';
      $view = 'emails.verification_code';
    } else {
      $subject = 'Password verification code';
      $view = 'emails.password_reset'; // Points to the new blade file.
    }

    // 2. Return the configured mail
    return $this->subject($subject)
      ->view($view)
      ->with([
        'code' => $this->code,
        'type' => $this->type
      ]);
  }
}
