# Kirby Mailjet (BETA)

Kirby Mailjet makes sending emails with Mailjet simple.

## Responsive HTML Emails Example

This will take no more than 60 minutes of your precious time.

- install all requirements
- setup mailjet account
- copy files of global field definitions
- edit two of these files
- click 4 different buttons
- fill out a form
- click a link in an email
- check your mailjet dashboard (optional)
- recieve about half a dozend emails during the whole process

### Fields and Snippets, Mjml, Html, Mustache

**TD;DR**

This section explains why this plugin exists and how it solves the task.

**Problem**

Use Kirby Builder to build html code for a responsive email. Make Kirby Builder work with mjml but still render a proper view in the panel (not just mjml code).

**Analysis**

- Kirby Builder needs field definitions to define data and snippets to show it.
- Generating good responsive email html code is hard. Very hard. Using a lib like mjml makes it easy.
- mjml files can be rendered to html documents using the command line tool `mjml`. But unless you can run node.js code on your server you have to render the mjml to html locally.
- mjml files must be valid to be rendered to html. The resulting html file is dauntingly complex. Injecting PHP with `mj-raw` is possible but imho not very elegant.
- Mustache can render data based on a template file. Its templating language is similar to mjml and keeps mjml files valid – both use `{{ VAR }}`.


**Solution**

- Create a mjml file with mustache code.
- Split the mjml code into blocks that match the parts used by the fieldsets and create snippets for each of them.
- Create a master snippet to chain them all back together.
- Call the master snippet to re-create the mjml file.
- Render the mjml file to html. it will still contain mustache code.
- Split the html into parts for the fieldsets.
- Kirby Builder will call the snippets for each part. these will each render the fieldset data into the html part.

**Kirby Mailjet Plugin**

- Build the master mjml with a call of `Kirbymailjet::buildMJML($snippetname)`
- Tries to call `mjml` and split the resulting html into parts with `Kirbymailjet::execMJML($snippetname)`. But you better setup *watching* `mjml` yourself, too.
- Renders data to files using Mustache with `Kirbymailjet::renderMustache($file, $data)`. Kirby Builder Fieldset data to html files to be more specific.


## Requirements

- My [Kirby Opener Plugin](https://github.com/bnomei/kirby-opener), commercial on public server, free for testing purposes
- Tim Ötting's [Kirby Builder](https://github.com/TimOetting/kirby-builder), free
- Martin Zurowietz's [Kirby Uniform](https://github.com/mzur/kirby-uniform), free

### Install Requirements

Using [Kirby CLI](https://github.com/getkirby/cli) and [npm for mjml](https://mjml.io/download).

```
kirby plugin:install bnomei/kirby-opener
kirby plugin:install mzur/kirby-uniform
kirby plugin:install TimOetting/kirby-builder
```

```
npm install mjml
export PATH="$PATH:./node_modules/.bin"
```

### Setup Mailjet Account

Read [all about it here](https://github.com/bnomei/kirby-mailjet#setupmailjetaccount).

### Installation of Panel Fields and Snippets

This plugin comes with some example panel fields to get you started. You can find their their [global field definitions](https://getkirby.com/docs/panel/blueprints/global-field-definitions) in the [kirby-mailjet/blueprints/fields](https://github.com/bnomei/kirby-mailjet/blob/master/blueprints/fields/) folder.

Copy **all** of provided global field definitions from the plugin to your `site/blueprints/fields` folder. Create the folder if needed.

Also copy **all** of provided snippets from the plugin to your `site/snippets` folder.

> **Why copy?** The plugin could install these with the example but the examples should be disabled on prodution server. So you have to copy them sooner or later. Furthermore you will probably make adjustments to the button labels and snipepts etc to fit your needs and that should not happen in the plugins folder. Also I do not want to mess up [Kirby Builders snippet root folder](https://github.com/TimOetting/kirby-builder/blob/master/builder.php#L43).

## Example Page

### Create Example Page

Enable the examples in your `site/config/config.php` file.

```php
c::set('plugin.mailjet.examples', true); // default: false
```

> **Important:** On a production server you must disable the examples again for security reasons.

Enable the blueprint for the example in your `site.yml`. Please adjust this to your needs as [explained in the docs](https://getkirby.com/docs/panel/blueprints/subpages-settings#templates-for-subpages). The most basic blueprint would look like this.

```yaml
title: Site

# pages: default
pages:
  template:
    - default
    - mj-example

# ...more fields...
```

Start the Kirby Panel and create a new page with the template `mj-example` *Mailjet Plugin Example Page* this plugin provides. It should look something like this.

![Example Page in Panel](http://bnomei.com/kirby-mailjet/example-page-in-panel-small.gif)

- Example page with three subpages
- Kirby Builder with some fieldsets
- Select Fields and Buttons to trigger the API this plugin provides

But the Select Fields will not work yet. Lets fix that.

### Setup Custom Routes

Since Kirby does not support relative urls in Select Panel Fields [below v2.4.2](https://github.com/getkirby/panel/issues/1035) you might have to hardcode your routes. But do not worry, that is easy to do.

Visit the *Mailjet Plugin Example Page* in the panel and find the Kirby-Mailjet-Hash Field. Alternatively in the frontend by clicking on *Open Preview* in the Panel then locate the output called `hash`. If it is not there make sure you are logged into the panel.

> **Why use a hash in the routes URL?** The hash is unique for your server and not public unless you tell someone what it is. That way your mailjet data is kept private but can still be accessed from the panel. Please, do not forget to [disable examples on production servers](https://github.com/bnomei/kirby-mailjet#pluginmailjetexamples)!

It should look like this.

![Example Page in Frontend](http://bnomei.com/kirby-mailjet/example-page-in-frontend-small.gif)

Edit the files `mj-example-contactslists.yml` and `mj-example-segments.yml` in your `site/blueprints/fields` folder in replacing the URL and HASH with your values. Like...

```yml
label: Segment
type: select
options: http://YOUR_DOMAIN_HERE/kirby-mailjet/PLUGIN_HASH_HERE/json/segments.json
```

or if you have Kirby v2.4.2

```yml
label: Segment
type: select
options: url
url: kirby-mailjet/PLUGIN_HASH_HERE/json/segments.json
```

Save the files and verify success in refreshing the browser tab showing *Mailjet Plugin Example Page* in Panel. Your Contactslist and Segments (if you have any) should be listed in their Select Panel Fields.

### Preview, Send, Test and Publish

Add a email-adress if needed, Select a Contactslist then press the *Save*-Button. Now then lets start hitting these buttons.

- 1st one will open a new tab which looks like the Kirby Builder fieldsets but in one big html file.
- 2nd one will send a transactional email to your panel account email or the one you entered (before saving). That means just that one email-adress, not the Contactslist you selected.
- 3rd one will send a test version of the Newsletter to the email-adress. The Newsletter object is now pushed to the mailjet dashboard.
- 4th one will publish the Newsletter and send it to the Contactslist. It will **not** allow you to publish it again, unless you pick a different Contactslist or Segment. But you can keep sending transactional emails and tests.


## Newsletter Double-Optin Form

### Custom Contact-Properties in Mailjet

For this example form to work you have to create `firstname` and `lastname` as [custom Contact-Properties in your Mailjet-Dashboard](https://app.mailjet.com/contacts/lists/properties).

### Test the Form

Then head over to the example frontend Page and fill out the form. You will get an email with a verification opt-in link. When clicking on that you will be subscribed to **Newsletter Test** and recieve a confirmation email.

## In-Depth: mjml, Snippets and Kirby Builder

A *master* snippet is used to compose the mjml file code. The *block* snippets are used to define repeatable blocks. In this example the *master* snippet for email and newsletter share some blocks. The controller calls the snippet and stores its output into an mjml file. Kirby Builder uses the blueprints to find the right *block* snippet.

### Technical Details

When `buildMJML` is [called the contoller](https://github.com/bnomei/kirby-mailjet/blob/master/controllers/mj-example.php#L239) **mj-example-newsletter.php** with the name of the snippet **mj-example-newsletter**

```php
$snippet = 'mj-example-newsletter';
$html = null;
if($htmlfile = KirbyMailjet::buildMJML($snippet)) {
    KirbyMailjet::execMJML($htmlfile);
}
$html = KirbyMailjet::renderMustache(
    $snippet,
    $mustache
);
```

- The output of [the snippet](https://github.com/bnomei/kirby-mailjet/blob/master/snippets/mj-example-newsletter.php) is written to an mjml file.
- If you setup node.js `mjml` to watch the mjml file it will be converted into html. The class is trying to call `mjml` with `execMJML` but on localhost it [probably will not work](https://forum.getkirby.com/t/how-to-bash-it-right-make-local-apache-call-node-js-bin/7272) since node can not be called from localhost php afaik. But it also [splits the resulting html file into the html parts](https://github.com/bnomei/kirby-mailjet/blob/master/kirby-mailjet-class.php#L209) needed for the snippet to render preview in *Kirby Builder*. These are the html files with the same name as the *blocks*.
- So if you setup your local mjml to watch the mjml file you should be fine and the html-blocks should be generated.

### Custom Newsletter based on the Example

To create a custom newsletter do the following. Start reusing existing blocks then change these.

- Create a new snippet [based on mine](https://github.com/bnomei/kirby-mailjet/blob/master/snippets/mj-example-newsletter.php). lets call it `mynewsletter.php`.
- Use the filename of `mynewsletter` in the [controller here](https://github.com/bnomei/kirby-mailjet/blob/master/controllers/mj-example.php#L237).
- If calling the controller (like when using the *preview email button*) the new `mynewsletter.mjml` file should be created.
- Then you need to make your local `mjml` watch it: `mjml -w mynewletter.mjml`. A html file should be created.
- The html file is loaded and parsed by `renderMustache` to generate the email html code

### Custom mjml Blocks

- Create a new snippet based on any existing *block*. Lets call it `myblock.php`
- Update your master snippet `mynewsletter.php` to use the new block
- Then refresh the email preview. the controller should call `buildMJML` and  `execMJML`. This will create a updated mjml file, your watching `mjml` will create a html file and `execMJML` will split the html file generating one for your new block (it waits for 5 sec for mjml to finish the job). The `myblock.html` file now exists.
- Last step is to tell *kirby builder* about the new block. You need to create new field blueprint for your block. Based on [the examples](https://github.com/bnomei/kirby-mailjet/tree/master/blueprints/fields) and add it to the builder [fieldsets here](https://github.com/bnomei/kirby-mailjet/blob/master/blueprints/mj-example.yml#L33) in the controller blueprint.

## Where to go from here?

Take a look at the [mjml code flavoured with mustache](https://github.com/bnomei/kirby-mailjet/blob/master/snippets/mj-example-newsletter.php) or even copy it to the [Online Editor](https://mjml.io/try-it-live).

Check out the [controller](https://github.com/bnomei/kirby-mailjet/blob/master/controllers/mj-example.php) and [template](https://github.com/bnomei/kirby-mailjet/blob/master/templates/mj-example.php) how the example does its magic and weave your own based on that.

If you find any issues, please [create a new issue](https://github.com/bnomei/kirby-mailjet/issues/new) or join the discussions in the [Kirby Forum](https://forum.getkirby.com/search?q=kirby-mailjet).
