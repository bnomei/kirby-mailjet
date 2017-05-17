<?php

/****************************************
  ROUTES
 ***************************************/

require_once __DIR__ . '/vendor/autoload.php';

use \Mailjet\Resources;

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
				$cl = null;
				$cacheFile = kirby()->roots->cache() . DS . $file;
				if($cache = f::read($cacheFile)) {
					$cl = json_decode($cache, true);
				}

				if($file == 'contactslists.json') {
					if(!$cl || f::modified($cacheFile) + c::get('plugin.mailjet.json.cache', 60*5) < time()) {
						$cls = KirbyMailjet::contactslists();
						if(count($cls) > 0) {
							f::write($cacheFile, json_encode($cl));
							$cl = $cls;
						}
					} 
					if($cl) {
						$json = $cl;
						$code = 200;
					}
				}
				else if($file == 'segments.json') {
					if(!$cl || f::modified($cacheFile) + c::get('plugin.mailjet.json.cache', 60*5) < time()) {
						$cls = KirbyMailjet::segments();
						if(count($cls) > 0) {
							f::write($cacheFile, json_encode($cl));
							$cl = $cls;
						}
					} 
					if($cl) {
						$json = $cl;
						$code = 200;
					}
				}
			}

			return response::json($json, $code);
	    },
	    
	)
);