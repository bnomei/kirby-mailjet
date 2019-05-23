<?php

/****************************************
  CLASS
 ***************************************/

require_once __DIR__ . '/vendor/autoload.php';

use \Mailjet\Resources;

class KirbyMailjet
{
    const EMAIL_SERVICE = 'kirby-mailjet';
    const PHPMAIL_SERVICE = 'kirby-mailjet-phpmail';

    const LIST_UNSUB = "unsub";
    const LIST_ADDNOFORCE = "addnoforce";
    const LIST_REMOVE = "remove";
    const LIST_ADDFORCE = "addforce";

    private static $_errors = array();
    public static function errors()
    {
        return self::$_errors;
    }
    public static function hasErrors()
    {
        return count(self::$_errors);
    }
    private static function pushLog($err, $isError = true)
    {
        $now = date('c');
        if ($isError) {
            self::$_errors[$now] = $err;
        }
        if ($log = c::get('plugin.mailjet.logfile', false)) {
            f::write($log, $err.PHP_EOL, true); // append
        }
    }
    private static function getResponseError($response)
    {
        $error = null;
        if (!$response->success()) {
            $d = $response->getData();
            $error = l::get('mailjet-error-dump') . PHP_EOL .
                implode(PHP_EOL, [
                    'StatusCode: ' . a::get($d, 'StatusCode', ''),
                    'ErrorMessage: ' . a::get($d, 'ErrorMessage', ''),
                    'ErrorInfo: ' . a::get($d, 'ErrorInfo', ''),
                ]);
        }
        return $error;
    }

    private static $_client = null;
    public static function client()
    {
        self::loadTranslation();

        if (!self::$_client) {
            $apikey = c::get('plugin.mailjet.apikey', '');
            $apisecret = c::get('plugin.mailjet.apisecret', '');

            if (strlen($apikey) == 32 && strlen($apisecret) == 32) {
                self::$_client = new \Mailjet\Client($apikey, $apisecret);
            } else {
                self::pushLog(l::get('mailjet-error-invalid-keys'));
            }
        }
        return self::$_client;
    }

    private static $_from = null;
    public static function senderAdress($emailadress = null)
    {
        if (!self::$_from || $emailadress) {
            $apifrom = $emailadress ? $emailadress : c::get('plugin.mailjet.from');

            if ($apifrom && v::email($apifrom)) {
                self::$_from = $apifrom;
                self::senderName(); // refresh
            }
        }
        return self::$_from;
    }

    private static $_fromname = null;
    public static function senderName()
    {
        if (!self::$_fromname) {
            $mj = self::client();
            $sa = self::senderAdress();
            if ($mj && $sa) {
                $response = $mj->get(Resources::$Sender, ['filters' => ['Email' => $sa], 'body' => null]);
                if ($response->success()) {
                    foreach ($response->getData() as $r) {
                        if ($r["Email"] == $sa) {
                            self::$_fromname = $r['Name'];
                        }
                    }
                }
                if (self::$_fromname == null) {
                    self::pushLog(str_replace('{email}', $sa, l::get('mailjet-error-sendername')));
                }
            }
        }
        return self::$_fromname;
    }

    /////////////////////////////////////
    // build mjml template (with mustache code)
    public static function buildMJML($snippet, $pluginFolder = false)
    {
        if (!self::is_localhost()) {
            return null;
        }

        $file = null;

        try {
            $mjml = snippet($snippet, [], true);

            if (c::get('plugin.mailjet.tidy.xml', true) && class_exists('tidy')) {
                $mjml = preg_replace("/<(mj-\w+)([\w\W][^<]+)\/>/is", '<$1$2></$1>', $mjml); // unroll self closing mj- (tidy can not handle these)
                $config = array(
                   'indent'     => true,
                   'input-xml'  => true,
                   'output-xml' => true,
                   'input-encoding' => 'utf8',
                   'char-encoding' => 'utf8',
                   'wrap'       => false
                );
                $tidy = new tidy();
                $tidy->parseString(str::utf8($mjml), $config);
                $tidy->cleanRepair();
                $mjml = tidy_get_output($tidy);
                $mjml = preg_replace("/><\/(mj-\w+)>/is", ' />', $mjml); // close self closing mj- again
            }

            $file = kirby()->roots()->snippets() . DS . $snippet . '.mjml';
            if ($pluginFolder) {
                $file = kirby()->roots()->site() . DS . 'plugins' . DS . 'kirby-mailjet' . DS . 'snippets' . DS . $snippet . '.mjml';
            }
            f::write($file, $mjml);
        } catch (Exception $ex) {
            self::pushLog(l::get('mailjet-error-dump').$ex->getMessage());
        }

        return $file;
    }


    /////////////////////////////////////
    // mjml to html template (with mustache code) on localhost
    public static function execMJML($snippet)
    {
        if (!self::is_localhost()) {
            return null;
        }

        if (f::exists($snippet)) {
            $mjmlfile = str_replace('.mjml', '', $snippet);
        } else {
            $mjmlfile = kirby()->roots()->snippets() . DS . $snippet;
        }

        $out = null;
        $ret = null;
        $ecx = null;

        /*
        //exec('ls -l /usr/local/bin', $out, $ret);
        exec('/usr/local/bin/node -h', $out, $ret);
        f::write($mjmlfile.'.log', implode(PHP_EOL, [implode(PHP_EOL, $out), $ret]));
        return;
        */
        $cmd = implode(' ', [
            c::get('plugin.mailjet.mjml-command', 'mjml'),
            '--render "' . $mjmlfile . '.mjml"',
            '--output "' . $mjmlfile . '.html"',
        ]);
        exec($cmd);

        /*
        $cmd = 'mjml -h';

        exec($cmd, $out, $ret);
        f::write($mjmlfile.'.log', implode(PHP_EOL, [implode(PHP_EOL, $out), $ret, $cmd]));
        return;
        */

        /*
        if(c::get('plugin.mailjet.mjml-backup', false) && f::exists($mjmlfile.'.html')) {
            f::copy($mjmlfile.'.html', $mjmlfile.'.bak.html');
        }

        $shOrBat = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'bat' : 'sh';
        $script = $mjmlfile . '.' . $shOrBat;
        if($shOrBat == 'sh') {
            f::write($script, "#!/bin/bash\n\r".$cmd);
            $ecx = "chmod +x \"{$script}\"; \"{$script}\";";
            exec($ecx, $out, $ret);

        } elseif($shOrBat == 'bat') {
            f::write($script, $cmd);
            $ecx = "cmd /c \"{$script}\"";
            system($ecx);

        }

        if($logfile = c::get('plugin.mailjet.logfile.execmjml', false)) {
            f::write($logfile, implode(PHP_EOL, [$cmd, $ecx, implode(PHP_EOL, $out), $ret]).PHP_EOL, true); // append
        }

        sleep(5); // wait for shell script
        */

        // generate parts for kirby builder
        $htmlfile = $mjmlfile . '.html';
        if (f::exists($htmlfile)) {
            $html = f::read($htmlfile);
            preg_match_all('/(<!--PART:([\w\d\s\-]+)-->)([\w\W]+)(<!--\/PART:(\2)-->)/is', $html, $matches);
            //var_dump($matches);

            if ($matches) {
                for ($m=0; $m < count($matches[0]); $m++) {
                    $pid = $matches[2][$m];
                    $pco = $matches[3][$m];
                    $filepart = dirname($htmlfile) . DS . $pid . '.html';

                    $pco = preg_replace('/(<!--\[if mso\]>)(.+?)(<!\[endif\]-->)/is', '', $pco);
                    $pco = preg_replace('/(<!--\[if mso \| IE\]>)(.+?)(<!\[endif\]-->)/is', '', $pco);

                    f::write($filepart, $pco);
                }
            }
        }

        return $cmd;
    }

    /////////////////////////////////////
    // build html from html-template (using mustache)
    public static function renderMustache($file, $mustachedata = [], $pluginFolder = false)
    {
        $out = null;

        if (!f::exists($file) && $pluginFolder) {
            $file = kirby()->roots()->site() . DS . 'plugins' . DS . 'kirby-mailjet' . DS . 'snippets' . DS . $file . '.html';
        }

        if (!f::exists($file)) {
            $file = kirby()->roots()->snippets() . DS . $file .'.html';
        }

        if (f::exists($file)) {
            try {
                $mustache = new Mustache_Engine([
                     'escape' => function ($value) {
                         // do not escape!
                        return $value; // https://github.com/bobthecow/mustache.php/wiki
                     }
                    ]);

                $out = $mustache->render(
                    f::read($file),
                    $mustachedata
                );
            } catch (Exception $ex) {
                self::pushLog(l::get('mailjet-error-dump').$ex->getMessage());
            }
            return $out;
        } else {
            self::pushLog(str_replace('{file}', $file, l::get('mailjet-error-mustache-file')));
        }
        return $out;
    }

    /////////////////////////////////////
    //
    private static $_senders = null;
    public static function senders()
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }
        if (self::$_senders) {
            return self::$_senders;
        }

        $cl = array();
        $exclude = c::get('plugin.mailjet.json-senders.exclude', []);
        $response = $mj->get(Resources::$Sender, ['body' => null]);
        if ($response->success()) {
            foreach ($response->getData() as $r) {
                if (in_array($r['Email'], $exclude) || strpos($r['Email'], '*') === 0 ) {
                    continue;
                }
                if (in_array($r['Name'], $exclude)) {
                    continue;
                }

                $cl[$r['Email']] = $r['Name'];
            }
            self::$_senders = $cl;
        }

        return $cl;
    }

    /////////////////////////////////////
    //
    private static $_segments = null;
    public static function segments()
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }
        if (self::$_segments) {
            return self::$_segments;
        }

        $cl = array();
        $exclude = c::get('plugin.mailjet.json-segments.exclude', []);
        $response = $mj->get(Resources::$Contactfilter, ['body' => null]);
        if ($response->success()) {
            foreach ($response->getData() as $r) {
                if (in_array($r['ID'], $exclude)) {
                    continue;
                }
                if (in_array($r['Name'], $exclude)) {
                    continue;
                }

                $cl[$r['ID']] = $r['Name'];
            }
            self::$_segments = $cl;
        }

        return $cl;
    }

    /////////////////////////////////////
    //
    private static $_contactslists = null;
    public static function contactslists()
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }
        if (self::$_contactslists) {
            return self::$_contactslists;
        }

        $cl = array();
        $exclude = c::get('plugin.mailjet.json-contactslists.exclude', []);
        $response = $mj->get(Resources::$Contactslist, ['body' => null, 'filters' => ['Limit' => '0']]);
        if ($response->success()) {
            foreach ($response->getData() as $r) {
                if (in_array($r['ID'], $exclude)) {
                    continue;
                }
                if (in_array($r['Name'], $exclude)) {
                    continue;
                }

                $cl[$r['ID']] = $r['Name'];
            }
            self::$_contactslists = $cl;
        }

        return $cl;
    }

    /////////////////////////////////////
    //
    public static function getContactslist($contactslistname)
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }

        if (ctype_digit($contactslistname)) {
            $contactslistID = intval($contactslistname);
        } else {
            $response = $mj->get(Resources::$Contactslist, ['filters'=>['Name'=>$contactslistname], 'body' => null]);
            $contactslistID = -1;
            if ($response->success()) {
                foreach ($response->getData() as $r) {
                    if ($r['Name'] == $contactslistname) {
                        $contactslistID = $r['ID'];
                        break;
                    }
                }
            }
        }

        if ($contactslistID == -1) {
            $response = $mj->post(Resources::$Contactslist, ['body' => ['Name' => $contactslistname]]);
            if ($response->success()) {
                $contactslistID = $response->getData()[0]['ID'];
            } else {
                self::pushLog(str_replace('{contactslistname}', $contactslistID, l::get('mailjet-error-contactslist-create')));
            }
        }
        if ($contactslistID == -1) {
            self::pushLog(str_replace('{contactlist}', $contactslistname, l::get('mailjet-error-contactslist')));
            return null;
        }
        return $contactslistID;
    }

    /////////////////////////////////////
    //
    public static function getSegment($segname)
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }

        $segid = -1;
        if (ctype_digit($segname)) {
            $segidtry = intval($segname);

            $response = $mj->get(Resources::$Contactfilter, ['body' => null]);
            foreach ($response->getData() as $r) {
                if ($segidtry == $r['ID']) {
                    $segid = $r['ID'];
                    break;
                }
            }
        } else {
            $response = $mj->get(Resources::$Contactfilter, ['filters'=>['Name'=>$segname], 'body' => null]);

            foreach ($response->getData() as $r) {
                if ($segname == $r['Name']) {
                    $segid = $r['ID'];
                    break;
                }
            }
        }
        if ($segid == -1) {
            self::pushLog(str_replace('{segment}', $segname, l::get('mailjet-error-segment')));
            return null;
        }
        return $segid;
    }

    /////////////////////////////////////
    // un-/register to contact lists
    public static function updateContactslist($contactslistname, $action, $data)
    {
        $mj = self::client();
        if (!$mj) {
            return null;
        }

        $contactslistID = self::getContactslist($contactslistname);
        if (!$contactslistID) {
            return null;
        }

        $email = a::get($data, 'email', '');
        if (!v::email($email)) {
            return null;
        }

        /********************************
        	REMOVE, UNSUB
    	 *******************************/
        if (in_array($action, [self::LIST_UNSUB, self::LIST_REMOVE])) {
            $body = [
                'Email' => $email,
                'Action' => $action
            ];
            $response = $mj->post(Resources::$ContactslistManagecontact, ['id' => $contactslistID, 'body' => $body]);

            if ($response->success() || $response->getData()['Status'] == 400) {
                return true;
            } else {
                self::pushLog(str_replace(['{contactslist}','{cmd}','{email}'], [$contactslistname, $action, $email], l::get('mailjet-error-contactlist-change')));
                return false;
            }
        } // unsub


        /********************************
        	ADDFORCE, ADDNOFORCE
    	 *******************************/
        if (in_array($action, [self::LIST_ADDFORCE, self::LIST_ADDNOFORCE])) {
            // add contact to mailjet
            $error = null;
            $contactID = -1;
            $body = [
                'Email' => strtolower($email),
            ];
            $response = $mj->post(Resources::$Contact, ['body' => $body]);
            if ($response->success()) {
                $contactID = $response->getData()[0]['ID']; // first element
            }
            if ($response->getData()['StatusCode'] == 400) {
                $contactID = strtolower($email);
            }
            if ($contactID == -1) {
                self::pushLog(str_replace('{email}', $email, l::get('mailjet-error-contact')));
                return false;
            }

            // subscribe to contactlist
            $body = [
                'ContactsLists' => [
                    [
                        'ListID' => $contactslistID,
                        'Action' => $action
                    ]
                ]
            ];

            $response = $mj->post(
                Resources::$ContactManagecontactslists,
                ['id' => $contactID, 'body' => $body]
                );

            // if new or exists
            if ($response->success() || $response->getData()['Status'] == 400) {
                $dataToAdd = array();
                foreach ($data as $key => $value) {
                    if ($key == 'email') {
                        continue;
                    }

                    $dataToAdd[] = [
                            'Name' => $key,
                            'value' => $value
                        ];
                }

                // update contactdata
                $response = $mj->put(Resources::$Contactdata, [
                    'id' => $contactID,
                    'body' => ['Data' => $dataToAdd]
                ]);
                if ($response->success()) {
                    //$contactID = $response->getData()[0]['ID']; // first element
                } elseif ($response->getData()) {
                    $error = l::get('mailjet-error-dump').a::show($response->getData(), false);
                    self::pushLog($error);
                }
            } else {
                $error = str_replace(['{contactslist}','{cmd}','{email}'], [$contactslistname, $action, $email], l::get('mailjet-error-contactlist-change'));
                self::pushLog($error);
            }

            return $error == null ? true : $error;
        } // addforce

        return null;
    }

    /////////////////////////////////////
    // send transaction email and tests
    public static function sendMail($params)
    {

        // if mailjet is available, check service
        // else send using default
        if (self::client() && !a::get($params, 'service')) {
            $params['service'] = self::EMAIL_SERVICE;
        }

        $emailKirby = email($params);
        try {
            if (!$emailKirby || !$emailKirby->send()) {
                $msg = str_replace(
                    '{email}',
                    a::get($params, 'to', 'MISSING'),
                    l::get('mailjet-error-sendmail-failed')
                );
                throw new Error($msg);
            }
        } catch (Error $e) {
            $msg = $e->getMessage() . PHP_EOL . a::show($params, false);
            self::pushLog($msg);
            return false;
        }
        return true;
    }

    /////////////////////////////////////
    // send newsletter
    // default is just a test
    public static function sendNewsletter($contactslistname, $campaign_body, $campaign_content, $testEmail = true, $schedule = null)
    {
        $jsonResponse = [ 'code' => 400]; // 'message' => 'Unknown Error'

        $mj = self::client();
        if (!$mj) {
            $jsonResponse['message'] = l::get('mailjet-error-client');
            self::pushLog(trim($jsonResponse['message']));
            return $jsonResponse;
        }

        $contactslistID = self::getContactslist($contactslistname);
        if (!$contactslistID) {
            $jsonResponse['message'] = str_replace('{contactlist}', $contactslistname, l::get('mailjet-error-contactslist'));
            self::pushLog(trim($jsonResponse['message']));
            return $jsonResponse;
        }

        /* LIKE
        $campaign_body = [
            'Locale' => "de_DE",
            'Sender' => $senderName,
            'SenderEmail' => $senderEmail, // verified sender of domain
            'Subject' => $subject,
            //'ContactsListID' => $contactslistID, // POST only, not PUT
            'Title' => $now,
            'Url' => trim($purl),
        ];
        */

        $campain_keys = ['Locale', 'Sender', 'SenderEmail', 'Subject', 'Title', 'Url'];
        $missing = a::missing($campaign_body, $campain_keys);
        if (count($missing) > 0) {
            $jsonResponse['message'] = str_replace('{keys}', implode(', ', $missing), l::get('mailjet-error-newsletter-body-missing-keys'));
            self::pushLog(trim($jsonResponse['message']));
            return $jsonResponse;
        }

        $hasTestEmail = v::email($testEmail);
        $hasPublish = $testEmail == 'Publish' || $testEmail == false;
        $schedule = strtolower($schedule) == 'now' ? 'NOW' : $schedule;
        if($schedule || $testEmail == 'Schedule') {
            $hasPublish = false;
        }

        /////////////////////////////////
        /// CAMPAIN GET/CREATE
        ///
        // 1) get campain newsletter or create
        $campaign_id = -1;
        $f = [
            'Subject' => a::get($campaign_body, 'Subject', ''),
            'Contactslist' => $contactslistID,
            //'Status' => '0', // comma seperated
            // -2 : deleted
            // -1 : archived draft
            // 0 : draft
            // 1 : programmed => schedule
            // 2 : sent
            // 3 : A/X tesring
        ];
        if ($segid = a::get($campaign_body, 'SegmentationID', null)) {
            $f['Segmentation'] = $segid;
        }
        $response = $mj->get(Resources::$Newsletter, ['filters' => $f, 'body' => null]);

        // might EXISTS so update
        $found = false;

        if ($response->success() && count($response->getData()) > 0) {
            $campaigns = $response->getData();
            foreach ($campaigns as $campaign) {
                if ($campaign['Subject'] != a::get($campaign_body, 'Subject', '')) {
                    continue;
                }
                if ($campaign['ContactsListID'] != $contactslistID) {
                    continue;
                }
                if ($segid = a::get($campaign_body, 'SegmentationID', null)) {
                    if ($segid != a::get($campaign, 'SegmentationID')) {
                        continue;
                    }
                }

                $found = true;
                $campaign_id = $campaign['ID'];
                $isDraft = $campaign['Status'] == 0 || $campaign['Status'] == 1;

                // do not create or update new if exists but is not draft
                if ($hasPublish && !$isDraft) {
                    $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}'], [$campaign_id, $contactslistname], l::get('mailjet-error-newsletter-publish-non-draft'));
                    self::pushLog(trim($jsonResponse['message']));
                    return $jsonResponse;
                }

                if ($isDraft) {
                    // PUT // found. now update.
                    $response = $mj->put(Resources::$Newsletter, ['id' => $campaign_id, 'body' => $campaign_body]);
                    if ($response->success()) {
                        $campaign_id = $campaign['ID'];

                        if(!$schedule) {
                            $mj->delete(Resources::$NewsletterSchedule, ['id' => $campaign_id]);
                        }

                    } else {
                        $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}'], [$campaign_id, $contactslistname], l::get('mailjet-error-newsletter-update-draft'));
                        self::pushLog(trim($jsonResponse['message']));
                        return $jsonResponse;
                    }
                }
            }
        }

        if (!$found) { // POST // create

            $campaign_body['ContactsListID'] = $contactslistID; // add default
            //var_dump($campaign_body); die();
            $response = $mj->post(Resources::$Newsletter, ['body' => $campaign_body]);
            $jsonResponse['message'] = $response->success() ? '1' : $campaign_body;
            if ($response->success()) {
                $campaign_id = $response->getData()[0]['ID'];
            } else {
                $jsonResponse['message'] = self::getResponseError($response);
                self::pushLog(trim($jsonResponse['message']));
                return $jsonResponse;
            }
        }

        if ($campaign_id == -1) {
            $jsonResponse['message'] = str_replace('{newsletter}', $campaign_id, l::get('mailjet-error-newsletter'));
            self::pushLog(trim($jsonResponse['message']));
            return $jsonResponse;
        }

        /////////////////////////////////
        /// CAMPAIGN BODY POST/PUT
        ///
        // 2) post/put detail
        /*
        $campaign_content = [
                'Html-part' => $html,
                'Text-part' => "Falls die Nachricht nicht korrekt angezeigt wird, klicken Sie bitte hier: ".trim($page->url()),
            ];
         */
        if (count(a::missing($campaign_content, ['Html-part'])) > 0) {
            $jsonResponse['message'] = str_replace('{newsletter}', $campaign_id, l::get('mailjet-error-newsletter-html'));
            return $jsonResponse;
        }
        $response = $mj->put(Resources::$NewsletterDetailcontent, ['id' => $campaign_id, 'body' => $campaign_content]);
        if (!$response->success()) {
            $jsonResponse['message'] = str_replace('{newsletter}', $campaign_id, l::get('mailjet-error-newsletter-create'));
            self::pushLog(trim($jsonResponse['message']).self::getResponseError($response));
            return $jsonResponse;
        }

        /////////////////////////////////
        /// CAMPAIN TEST OR CONTACTSLIST
        ///
        // TEST now
        if ($hasTestEmail) {
            $response = $mj->post(Resources::$NewsletterTest, ['id' => $campaign_id, 'body' =>
                ['Recipients' => [['Email' => $testEmail]]]
            ]);
            if ($response->success()) {
                $jsonResponse['code'] = 200;
                $jsonResponse['message'] = str_replace(['{newsletter}', '{email}', '{service}'], [$campaign_id, $testEmail, ''], l::get('mailjet-success-newsletter-test'));
                self::pushLog(trim($jsonResponse['message']), false);
                return $jsonResponse;
            } else {
                $params = [
                    'to' => $testEmail,
                    'from' => a::get($campaign_body, 'SenderEmail'),
                    'replyTo' => a::get($campaign_body, 'SenderEmail'),
                    'subject' => '[TEST] ' . a::get($campaign_body, 'Subject'),
                    'body' => a::get($campaign_content, 'Html-part'),
                    'service' => self::EMAIL_SERVICE,

                ];
                if (self::sendMail($params)) {
                    $jsonResponse['code'] = 200;
                    $jsonResponse['message'] = str_replace(['{newsletter}', '{email}', '{service}'], [$campaign_id, $testEmail, ' (transactional)'], l::get('mailjet-success-newsletter-test'));
                    self::pushLog(trim($jsonResponse['message']), false);
                    return $jsonResponse;
                } else {
                    // error already tracked
                }
            }

            // SCHEDULE
        } elseif ($schedule) {
            $response = $mj->put(Resources::$NewsletterSchedule, ['id' => $campaign_id, 'body' => [
                'date' => $schedule
            ]]);
            if ($response->success()) {
                $jsonResponse['code'] = 200;
                $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}','{schedule}'], [$campaign_id, $contactslistID, $schedule], l::get('mailjet-success-newsletter-schedule'));
                self::pushLog(trim($jsonResponse['message']), false);
                return $jsonResponse;
            } else {
                $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}','{schedule}'], [$campaign_id, $contactslistID, $schedule], l::get('mailjet-error-newsletter-schedule'));
                self::pushLog(trim($jsonResponse['message']).PHP_EOL.self::getResponseError($response));
                return $jsonResponse;
            }

            // PUBLISH to contactslist
        } elseif ($hasPublish) {
            $response = $mj->post(Resources::$NewsletterSend, ['id' => $campaign_id]);
            if ($response->success()) {
                $jsonResponse['code'] = 200;
                $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}'], [$campaign_id, $contactslistID], l::get('mailjet-success-newsletter-publish'));
                self::pushLog(trim($jsonResponse['message']), false);
                return $jsonResponse;
            } else {
                $jsonResponse['message'] = str_replace(['{newsletter}','{contactlist}'], [$campaign_id, $contactslistID], l::get('mailjet-error-newsletter-publish'));
                self::pushLog(trim($jsonResponse['message']).PHP_EOL.self::getResponseError($response));
                return $jsonResponse;
            }
        }

        return $jsonResponse;
    }

    public static function is_localhost()
    {
        $whitelist = array( '127.0.0.1', '::1' );
        if (in_array($_SERVER['REMOTE_ADDR'], $whitelist)) {
            return true;
        }
        return false;
    }

    public static function hash()
    {
        return c::get('plugin.mailjet.hash', sha1('m38j_193K&1'.__DIR__));
    }

    private static $_translationLoaded = false;
    public static function loadTranslation()
    {
        if (self::$_translationLoaded) {
            return;
        }
        self::$_translationLoaded = true;

        $site = kirby()->site();
        $code = $site->multilang()
            ? $site->language()->code()
            : c::get('plugin.mailjet.language', 'en');

        $l18nFile = __DIR__ . DS . 'languages' . DS . $code . '.php';
        if (!f::exists($l18nFile)) {
            $l18nFile = __DIR__ . DS . 'languages' . DS . 'en.php';
        }
        include_once $l18nFile;
    }
}

/****************************************
  WIDGET
 ***************************************/
if (str::length(c::get('plugin.mailjet.license', '')) != 40) {
    // Hi there, play fair and buy a license. Thanks!
    $kirby->set('widget', 'mailjet', __DIR__ . '/widgets/mailjet');
}
