<?php

/**
 * Example callback to handle Mobile ID signature and authentication callbacks.
 * Mobile sessions get stored in text file only for sake of simplicity.
 * You should use a session database instead.
 */

$body = json_decode(file_get_contents('php://input'));
if (is_object($body)) {
  if ($body->event === 'mobile-sign') {
    $status = $body->error ? $body->error->statusCode : "OK";
    file_put_contents("out.txt", "sign;{$_GET['mobileSession']};{$status}\n", FILE_APPEND);
  } elseif ($body->event === 'mobile-login') {
    $status = $body->error ? $body->error->code : "OK";
    if ($body->success) {
      $additionalData = ";" . $body->country . ';' . $body->personalCode . ';' . $body->firstName . ';' . $body->lastName;
    }
    file_put_contents("out.txt", "authenticate;{$_GET['mobileSession']};{$status}{$additionalData}\n", FILE_APPEND);
  }
}
