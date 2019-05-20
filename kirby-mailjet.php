<?php

/****************************************
  KIRBY-MAILJET
 ***************************************/

// kirby opener must be loaded before kirby mailjet
// https://forum.getkirby.com/t/plugin-load-order/1701/2
if (c::get('plugin.mailjet.examples', true)) {
    $loadedOpener = kirby()->plugin('kirby-opener');
    $loadedUniform = kirby()->plugin('uniform');
}

require_once __DIR__ . '/kirby-mailjet-class.php';
require_once __DIR__ . '/kirby-mailjet-emailservice.php';
require_once __DIR__ . '/kirby-mailjet-routes.php';

if (c::get('plugin.mailjet.examples', true)) {
    require_once __DIR__ . '/kirby-mailjet-examples.php';
}
