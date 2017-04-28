<?php

/****************************************
  EMAIL SERVICE
 ***************************************/
use \Mailjet\Resources;

email::$services[KirbyMailjet::EMAIL_SERVICE] = function($email) {

  $send = false;
  $error = 'The email could not be sent.';
  if($mj = KirbyMailjet::client()) {
    $body = [
      'method'    => 'POST',
      'FromEmail' => KirbyMailjet::senderAdress($email->from), // verified sender of domain
      'FromName'  => KirbyMailjet::senderName(),
      'Subject'   => str::utf8($email->subject),
      'Html-part' => str::utf8($email->body),
      'Recipients' => [['Email' => $email->to]],
    ];

    if($textpart = a::get($email->options, 'Text-part')) {
      $body['Text-part'] = str::utf8($textpart);
    }

    if($mjcampaign = a::get($email->options, 'Mj-campaign')) {
      $body['Mj-campaign'] = str::utf8($mjcampaign);
    }

    if($attachments = a::get($email->options, 'Attachments')) {
      if(is_array($attachments) && count($attachments) > 0 ) {
        $allValid = true;
        foreach ($attachments as $at) {
          $missing = a::missing($at, ['Content-type', 'Filename', 'content']);
          if(count($missing) > 0) {
            $allValid = false;
            break;
          }
        }
        if($allValid) {
          $body['Attachments'] = $attachments;
        }
      }
    }

    $response = $mj->post(Resources::$Email, ['body' => $body]);
    if($response->success()) {
      $send = true;
    } else {
      $error = KirbyMailjet::getError($response);
    }
  }

  if(!$send) {
    throw new Error($error);
  }
};

email::$services[KirbyMailjet::PHPMAIL_SERVICE] = function($email) {
  $headers = array(
    'From: ' . $email->from,
    'Reply-To: ' . $email->replyTo,
    'Return-Path: ' . $email->replyTo,
    'Message-ID: <' . time() . '-' . $email->from . '>',
    'X-Mailer: PHP v' . phpversion(),
    'Content-Type: text/html; charset=utf-8',
    'Content-Transfer-Encoding: 8bit',
  );
  if(a::get($email->options, 'bcc') && v::email($email->options['bcc'])) { // add bcc
    array_push($headers, 'Bcc: ' . $email->options['bcc']);
  }

  ini_set('sendmail_from', $email->from);
  $send = mail($email->to, str::utf8($email->subject), str::utf8($email->body), implode(PHP_EOL, $headers));
  ini_restore('sendmail_from');

  if(!$send) {
    throw new Error('The email could not be sent');
  }
};
