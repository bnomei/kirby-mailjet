<?php

/****************************************
  ROUTES
 ***************************************/

$kirby->set('route',
	array(
		'pattern' => 'kirby-mailjet/(:any)/mjml/(:any)',
		'action' => function($hash, $snippet) {
			$json = array();
			$code = 400;

			// site()->user() // does not work with panel select field
			if($hash == KirbyMailjet::hash()) {
				KirbyMailjet::buildMJML($snippet);
				KirbyMailjet::execMJML($snippet);
				$code = 200;
			}

			return response::json($json, $code);
	    },
	    
	)
);

$kirby->set('route',
	array(
		'pattern' => 'kirby-mailjet/(:any)/json/(:any)',
		'action' => function($hash, $file) {
			$json = array();
			$code = 400;

			// site()->user() // does not work with panel select field
			if($hash == KirbyMailjet::hash()) {
				if($file == 'contactslists.json') {
					if($cl = KirbyMailjet::contactslists()) {
						$json = $cl;
						$code = 200;
					}
				}
				else if($file == 'segments.json') {
					if($cl = KirbyMailjet::segments()) {
						$json = $cl;
						$code = 200;
					}
				}
			}

			return response::json($json, $code);
	    },
	    
	)
);