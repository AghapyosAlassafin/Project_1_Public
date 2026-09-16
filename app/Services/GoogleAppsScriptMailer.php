<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleAppsScriptMailer
{
  protected $url;

  public function __construct()
  {
    $this->url = env('GOOGLE_SCRIPT_MAIL_URL');
  }

  /**
   * Send email via Google Apps Script.
   *
   * @param  string  $to
   * @param  string  $subject
   * @param  string  $htmlBody
   * @return array
   */
  public function send($to, $subject, $htmlBody)
  {
    $response = Http::post($this->url, [
      'to'       => $to,
      'subject'  => $subject,
      'body'     => $htmlBody,
    ]);

    if ($response->successful()) {
      return $response->json();
    }

    throw new \Exception('Google Apps Script Mail Error: ' . $response->body());
  }
}
