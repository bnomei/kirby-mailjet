<?php

use Uniform\Form;

return function($site, $pages, $page) {

    /////////////////////////////////////
    // Opt-In Uniform
    $form = new Form([
        'firstname' => [
            'rules'   => ['required'],
            'message' => 'Please enter your firstname',
        ],
        'lastname' => [
            'rules'   => ['required'],
            'message' => 'Please enter your lastname',
        ],
        'email' => [
            'rules'   => ['required', 'email'],
            'message' => 'Please enter a valid email address',
        ],
    ]);

    if (r::is('POST') && 
        $form->honeypotGuard() && // perform at least one action/check...
        $form->success() // ... now success can be determined
    ) {

        $formFirstname = strip_tags( $form->data('firstname') );
        $formLastname  = strip_tags( $form->data('lastname') );
        $formEmail     = strip_tags( $form->data('email') );

        // Firstname Lastname <some@ema.il>
        $emailTo = implode(' ', 
            [
                $formFirstname,
                $formLastname,
                '<'.$formEmail.'>'
            ]
        );

        $emailSubject = 'Newsletter Opt-In Link';

        $optInLink = implode('',
            [
                $page->url(),
                '?newsletter=optin', // see #OPTIN
                '&firstname='.urlencode($formFirstname),
                '&lastname='. urlencode($formLastname),
                '&email='.    urlencode($formEmail),
                // add a token to check data. order is important. see #CHECK
                '&token='.sha1(
                    KirbyMailjet::hash(). // this is unique for your server, never use it alone
                    date('Ym'). // minimalistic timeout: current month
                    $formFirstname.
                    $formLastname.
                    $formEmail
                    ),
            ]
        );
        $data = [
            // mj-example-block-headline
            'headline' => $emailSubject,

            // mj-example-block-text
            'text' => '<p><a href="'.$optInLink.'">please click here</a></p>',
            'footer' => '',
        ];
        if($htmlfile = KirbyMailjet::buildMJML('mj-example-email')) {
            KirbyMailjet::execMJML($htmlfile);
        }
        $emailBody = KirbyMailjet::renderMustache('mj-example-email', $data);

        KirbyMailjet::sendMail([
            'to' => $emailTo,
            'from' => KirbyMailjet::senderAdress(),
            'subject' => $emailSubject,
            'body' => $emailBody,
            'service' => KirbyMailjet::EMAIL_SERVICE, // send with mailjet
            'options' => [
                // 'Text-part' =>
                'Mj-campaign' => $emailSubject
            ]
        ]);

        go($page->url().'?newsletter=verify');
        // or of you have a dedicated page...
        // go(page('newsletter/verify')->url());
        
    }

    /////////////////////////////////////
    // Opt-In Link Handler
    $optinStatus = null;
    if(get('newsletter') == 'optin') { // #OPTIN
        $linkFirstname = urldecode(get('firstname'));
        $linkLastname = urldecode(get('lastname'));
        $linkEmail = urldecode(get('email'));
        $token = get('token');

        $check = sha1( // #CHECK
            KirbyMailjet::hash(). // this is unique for your server, never use it alone
            date('Ym').
            $linkFirstname.
            $linkLastname.
            $linkEmail
        );

        if($token == $check) { // success

            $updatedContactslist = '';
            if(KirbyMailjet::updateContactslist(
                    'Newsletter Test', 
                    KirbyMailjet::LIST_ADDFORCE,
                    [
                        'email' => $linkEmail,
                        'firstname' => $linkFirstname,
                        'lastname' => $linkLastname,
                    ]
                )) {
                $optinStatus = 'success';
            } else {
                $optinStatus = 'failed';
            }

            $emailSubject = 'Newsletter Opt-In '.$updatedContactslist;

            $data = [
                // mj-example-block-headline
                'headline' => $emailSubject,

                // mj-example-block-text
                'text' => '<p>'.$optinStatus.'</p>',
                'footer' => '',
            ];
            if($htmlfile = KirbyMailjet::buildMJML('mj-example-email')) {
                KirbyMailjet::execMJML($htmlfile);
            }
            $emailBody = KirbyMailjet::renderMustache('mj-example-email', $data);

            KirbyMailjet::sendMail([
                'to' => $linkEmail,
                'from' => KirbyMailjet::senderAdress(),
                'subject' => $emailSubject,
                'body' => $emailBody,
                'service' => KirbyMailjet::EMAIL_SERVICE, // send with mailjet
                'options' => [
                    // 'Text-part' =>
                    'Mj-campaign' => $emailSubject
                ]
            ]);

        } else {
            // data changed or timeout
            $optinStatus = 'timeout';
        }
    }

    /////////////////////////////////////
    // Optin Link Handler Exit
    if($optinStatus) {
        // avoid dublicate optin submission using PRG-Pattern: https://wiki2.org/en/Post/Redirect/Get
        // redirect away from optin link of email
        go($page->url().'?newsletter='.$optinStatus);
        // or of you have a dedicated page...
        // go(page('newsletter/'.$optinStatus)->url());
    }


    /////////////////////////////////////
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
                if($htmlfile = KirbyMailjet::buildMJML($snippet)) {
                    KirbyMailjet::execMJML($htmlfile);
                }
                $html = KirbyMailjet::renderMustache(
                    $snippet, 
                    $mustache
                );

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
        return compact('mustache', 'mjmlCode', 'form');
    }
};