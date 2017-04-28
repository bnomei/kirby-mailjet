# Kirby Mailjet (ALPHA)

![GitHub release](https://img.shields.io/github/release/bnomei/kirby-mailjet.svg?maxAge=1800) ![License](https://img.shields.io/badge/license-commercial-green.svg) ![Beta](https://img.shields.io/badge/Stage-alpha-blue.svg) ![Kirby Version](https://img.shields.io/badge/Kirby-2.4%2B-red.svg)

Kirby Mailjet makes sending emails with Mailjet simple. 

It's php helper class allows you to send transactional emails, as well as test and publish newletters. In combination with the [Kirby Opener Plugin](https://github.com/bnomei/kirby-opener) you can do all that from within the Panel. 

An example template and controller are provided to help you getting started with styling your own emails using [Kirby Builder](https://github.com/TimOetting/kirby-builder), the responsive email markup language [mjml](http://mjml.io) and the logic-less templating language [Mustache PHP](https://github.com/bobthecow/mustache.php).
But if you have your own toolchain to create responsive HTML code for emails you can continue using it.

**NOTE:** This is not a free plugin. In order to use it on a production server, you need to buy a license. For details on Kirby Mailjet's license model, scroll down to the License section of this document.

## Key Features

- swap your [email service](https://getkirby.com/docs/cheatsheet/helpers/email) from `mail` to `kirby-mailjet` and start sending emails using Mailjet
- sent emails can be assigned to new or existing Campaigns
- add attachements to emails
- add and remove Contacts from Contactslists
- test and publish Newsletters
- Panel Fields to access Contactslists and Segments
- Panel Buttons to send tests and publish (Kirby Opener required)
- example how to create responsive HTML emails

## Requirements

- [**Kirby**](https://getkirby.com/) 2.4+
- [Mailjet Account](http://mailjet.com), free plans available

## Included Dependencies

- [Mailjet PHP API v1.1.8](https://github.com/mailjet/mailjet-apiv3-php)
- [Mustache PHP v2.11.1](https://github.com/bobthecow/mustache.php)

## Installation

### [Kirby CLI](https://github.com/getkirby/cli)

```
kirby plugin:install bnomei/kirby-mailjet
```

### Git Submodule

```
$ git submodule add https://github.com/bnomei/kirby-mailjet.git site/plugins/kirby-mailjet
```

### Copy and Paste

1. [Download](https://github.com/bnomei/kirby-mailjet/archive/master.zip) the contents of this repository as ZIP-file.
2. Rename the extracted folder to `kirby-mailjet` and copy it into the `site/plugins/` directory in your Kirby project.


## Setup Mailjet Account

Create a [Mailjet account](https://app.mailjet.com/pricing) if you do not have one already.

- add a sender domain AND at least one sender email-adress [here](https://app.mailjet.com/account/sender)
- authentificate you domain [here](https://app.mailjet.com/account/domain)
- create a `Test` contactslist and add one of your own email-adresses to it.
- set your [API Public and Secret Keys](https://app.mailjet.com/transactional) and default sender-email-adress in your Kirby `/site/config/config.php` file.

```php
c::set('plugin.mailjet.apikey',    'YOUR_KEY_HERE');
c::set('plugin.mailjet.apisecret', 'YOUR_SECRET_HERE');
c::set('plugin.mailjet.from',      'YOUR_SENDER_EMAIL_ADRESS_HERE');
```

## Plugin Usage

### Updating Contactslists

You can call the helper class to add or remove contacts from a contactslist. If you defined [custom contact properties](https://app.mailjet.com/contacts/lists/properties) you can forward these to the api as well.

> **Tip:** If you provide a `string` and the contactslist does not exist, it will be created.

```php
KirbyMailjet::updateContactslist(
	'contactslistnameOrID',
	KirbyMailjet::LIST_ADDFORCE, // or LIST_ADDNOFORCE, LIST_UNSUB, LIST_REMOVE
	[
		'email' => 'test@example.com',
		//'firstname' => 'max', // custom contact property
	]
);
```

> **Tip:** Check out the [example](https://github.com/bnomei/kirby-mailjet/blob/master/example.md) on how to build your own newsletter double optin logic.

### Sending Transactional Emails

Transactional means sending an email to one person, not to a Constactslist with many people. To do so you can call the helper class directly or use the email service this plugin provides.

```php

// if you did set config...
// c::set('plugin.mailjet.from', 'my@email.com');
// ... you can get default sender adress
$senderEmail = KirbyMailjet::senderAdress();

// or set now
$senderEmail = KirbyMailjet::senderAdress('my@email.com');

$options = array();
// you could add this email to a Campaign to filter by
$options['Mj-campaign'] = 'Any Campaign';

// or add some attachments, assuming a Kirby Media object
// https://dev.mailjet.com/guides/#sending-with-attached-files
$attachedFile = [ 
	'Content-type' => $file->mime(),
	'Filename' => $file->filename(),
	'content' => base64_encode($file->content()),
];
$options['Attachments'] = array($attachedFile); // must be array even if only one file

$params = [
	'to'      => 'email@example.com',
    'from'    => $senderEmail, // this must be a valid sender email-adress
    'subject' => 'Sending emails with Kirby Mailjet is easy',
    'body'    => 'Hey! This was really easy!', // or responsive HTML code
    'service' => KirbyMailjet::EMAIL_SERVICE, // this is important!
    'options' => $options,
];

// https://getkirby.com/docs/cheatsheet/helpers/email
$email = email($params);
if($email->send()) {
  echo 'The email has been sent';
} else {
  echo $email->error();
}

// or just call the helper class instead
if(KirbyMailjet::sendMail($params)){
  echo 'The email has been sent';
} else {
  echo $email->error();
}
```

### Testing and Publishing Newsletters

```php
$senderEmail = KirbyMailjet::senderAdress();
// or
$senderEmail = KirbyMailjet::senderAdress('my@email.com');

// the subject is most important since that will be used 
// to identify your Newsletter in combination with 
// choosen Contactslist and Segment.
// if you change any of these you can publish it again
// since its treated like a new one by this plugin.
// so be careful with these three fellows.
$subject = trim($page->title());

// set an unique title for mailjets dashboard.
// this is never seen by newsletter reciepients.
$title = date('Y-m-d H:i:s')'; // just an example

// mailjet will not accept urls bigger than 100 chars
// for this property, even if this is used in dashboard only.
// urls within the html content can be longer than 100 chars.
$url = $page->url();
if(strlen($url) > 100) {
	$url = $page->tinyurl();
}

// these fields are required, you have to provide each of them
$campaign_body = [
	'Locale' => "en", // 'de_DE', ...
	'SenderEmail' => $senderEmail,
	'Sender' => KirbyMailjet::senderName(), // will get name from mailjet for you
	'Subject' => $subject, // UNIQUE for this Newsletter
	'Title' => $title,
	'Url' => trim($url),
];

// if you want to filter a contactslist 
// use a segmentation (only mailjet premium subscription)
if($segid = KirbyMailjet::getSegment($segmentationNameOrID)) {
	$campaign_body['SegmentationID'] = $segid;
}

$campaign_content = [
	'Html-part' => $html, // here goes your content
	'Text-part' => "Some plain text fallback with a link to the online version of the newsletter. ".trim($page->url()),
];

KirbyMailjet::sendNewsletter(
	'contactlistsnameOrID',
	$campaign_body,
	$campaign_content,
	'my@email.com', // email-adress to send test to or string 'Publish'
);

// you can enable logging in config file like this
// c::set('plugin.mailjet.logfile', YOUR_PATH_TO_FILE);
// or check for errors now
if(KirbyMailjet::hasErrors()) {
	a::show(KirbyMailjet::errors());
}
```

## Responsive HTML Emails Example

You can find the [example readme here](https://github.com/bnomei/kirby-mailjet/blob/master/example.md).

## Other Setting

You can set these in your `site/config/config.php`.

### plugin.mailjet.license
- default: ''
- add your license here and the widget reminding you to buy one will disappear from the Panel.

### plugin.mailjet.apikey (required)
- default: ''
- add your mailjet api key.

### plugin.mailjet.apisecret (required)
- default: ''
- add your mailjet api secret.

### plugin.mailjet.from (required)
- default: ''
- add your default mailjet sender adress here.

### plugin.mailjet.hash
- default: unique hash for your webserver
- this value is used to create the `hash` and you should set your own value to improve security but it is not required. it is used by the routes for the Contactslists and Segments Panel Fields.

### plugin.mailjet.examples
- default: `false`
- if disabled the plugin does not install any `blueprints, templates, controllers, hooks and routes` that are used by its examples. disable this setting in production enviroment.

### plugin.mailjet.logfile
- default: `false`
- set this value to the path of a file where you want the log to be written to. like `kirby()->roots()->site().DS.'logs'.DS.'mailjet-'.date('Ym').'.log'`

### plugin.mailjet.json-contactslists.exclude
- default: `[]`
- array of numeric ids to exclude from the `contactslists.json`

### plugin.mailjet.json-segments.exclude
- default: `[]`
- array of numeric ids to exclude from the `segments.json`

### plugin.mailjet.mjml-command
- default: `mjml`
- you could set the full path to mjml executable here if needed

## Localisation

English and german [languages are provided](https://github.com/bnomei/kirby-mailjet/blob/master/languages). You could PR another if you create one.

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby-mailjet/issues/new).

## License

Kirby Mailjet can be evaluated as long as you want on how many private servers you want. To deploy Kirby Mailjet on any public server, you need to buy a license. You need one unique license per public server (just like Kirby does). See `license.md` for terms and conditions.

[<img src="https://img.shields.io/badge/%E2%80%BA-Buy%20a%20license-green.svg" alt="Buy a license">](https://bnomei.onfastspring.com/kirby-mailjet)

However, even with a valid license code, it is discouraged to use it in any project, that promotes racism, sexism, homophobia, animal abuse or any other form of hate-speech.

## Technical Support

Technical support is provided on GitHub only. No representations or guarantees are made regarding the response time in which support questions are answered. But you can also join the discussions in the [Kirby Forum](https://forum.getkirby.com/search?q=kirby-mailjet).

## Credits

Kirby Mailjet is developed and maintained by Bruno Meilick, a game designer & web developer from Germany.
I want to thank [Fabian Michael](https://github.com/fabianmichael) for inspiring me a great deal and [Julian Kraan](http://juliankraan.com) for telling me about Kirby in the first place.
