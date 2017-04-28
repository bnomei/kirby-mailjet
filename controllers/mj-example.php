<?php
return function($site, $pages, $page) {

	// prepare json response
	$mailjetJSON = ['code' => 400];

	$mustache = array();
	foreach($page->builder()->toStructure() as $data) {
		$fieldset = $data->_fieldset();
		$snipjson = json_decode(snippet('mj-example-block-'.$fieldset, [
				'page' => $page,
				'data' => $data,
				'json' => true, // request json from snippet
			], true), true);
		if($snipjson) {
			$mustache = array_merge($mustache, $snipjson);
		}
	}

	$mjmlCode = '';
	$mjmlFile = kirby()->roots()->site() . DS . 'plugins' . DS . 'kirby-mailjet' . DS . 'snippets' . DS . 'mj-example-newsletter.mjml';
	if(!f::exists($mjmlFile)) {
		$mjmlFile = KirbyMailjet::buildMJML('mj-example-newsletter', true); // snippet in plugin folder
		if($mjmlFile) {
			KirbyMailjet::execMJML($mjmlFile);
		}
		$mjmlCode = f::read($mjmlFile);
	} else {
		$mjmlCode = f::read($mjmlFile);
	}

	// call from panel kirby-opener button?
	if((r::ajax() || param('mode') == 'preview') && $site->user()) {

		// if mailjet available and kirby-opener secret valid
		if(KirbyMailjet::client() != null && $page->openerSecret() == param('secret')) {

			$contactslist = null;
			if($page->mjcontactslist()->isNotEmpty()) {
				$contactslist = $page->mjcontactslist()->value();
			} else {
				$mailjetJSON['message'] = 'Choose Contactslist';
				
				die(response::json($mailjetJSON, 200));
			}

			// determin send mode: test or publish
			$testEmail = null;
			if(param('mailjet') == 'send') {
				// maybe to a role permission check? up to you.
				$testEmail = 'Publish';	

			} else if(param('mailjet') == 'test') {
				$mjemail = $page->mjemail();
				if($mjemail->isNotEmpty()) {
					$testEmail = $mjemail->value();
				} else {
					$testEmail = $site->user()->email();
				}
			}

			if(param('mailjet') == 'send' || param('mailjet') == 'test') {

				$title = date('Y-m-d H:i:s').' ['.$site->user()->username().']';
				$subject = trim($page->title());
				$pageurl = $page->url();
				if(strlen($pageurl) > 100) { // mailjet will not accept that for its dashboard
					$pageurl = $page->tinyurl();
				}

				$snippet = 'mj-example-newsletter';
				$html = null;

				// EXAMPLE USING PLUGIN SNIPPETS
				$isSnippetInPluginFolder = true;
				if($isSnippetInPluginFolder) {
					if($htmlfile = KirbyMailjet::buildMJML(
							$snippet, 
							$isSnippetInPluginFolder  // this example only
						)) {
						KirbyMailjet::execMJML($htmlfile);
					}
				
					$html = KirbyMailjet::renderMustache(
						$snippet, 
						$mustache, 
						$isSnippetInPluginFolder // this example only
					);

				// YOUR CODE SHOULD LOOK LIKE THIS
				} else {

					if($htmlfile = KirbyMailjet::buildMJML($snippet)) {
						KirbyMailjet::execMJML($htmlfile);
					}
				
					$html = KirbyMailjet::renderMustache(
						$snippet, 
						$mustache
					);
				}

				$campaign_body = [
					    'Locale' => "en", // de_DE
					    'SenderEmail' =>KirbyMailjet::senderAdress(),
					    'Sender' => KirbyMailjet::senderName(),
					    'Subject' => $subject, // filter UNIQUE
					    'Title' => $title,
					    'Url' => trim($pageurl),
					];

				if($page->mjsegment()->isNotEmpty()) {
					$segmentation = $page->mjsegment()->value();
					$segid = KirbyMailjet::getSegment($segmentation);
					if($segid) {
						$campaign_body['SegmentationID'] = $segid;
					}
				}

				$txt = "If this message is not displayed properly, please visit this website: ".trim($page->url());

				$campaign_content = [
					    'Html-part' => $html,
					    'Text-part' => $txt,
					];

				// MODE: send transactional email
				if(param('mode') == 'transactional') {
					$emailparams = [
						'from' => KirbyMailjet::senderAdress(),
						'to' => $testEmail,
						'subject' => $subject,
						'body' => $html,
						'options' => [
							'Text-part' => $txt,
							'Mj-campaign' => trim($page->slug()),
						]
					];
					if(KirbyMailjet::sendMail($emailparams)) {
						$mailjetJSON['code'] = 200;
					}
				}

				// MODE: dump html
				else if(param('mode') == 'preview') {
					echo $html; die();
				}

				// MODE: create and send newsletter (test or publish)
				else {
					$mailjetJSON = KirbyMailjet::sendNewsletter(
						$contactslist,
						$campaign_body,
						$campaign_content,
						$testEmail
					);
				}
				
			} // send or test


		} else {
			$mailjetJSON['message'] = 'Authentication failed.';
		}

		die(response::json($mailjetJSON, 200)); // reached api

	// or frontend page view
	} else {
		return compact('mustache', 'mjmlCode');
	}
};