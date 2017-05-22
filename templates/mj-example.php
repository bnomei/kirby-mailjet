<!DOCTYPE html>
<html>
<head>
    <title><?= $page->title() ?></title>
    <?= css('assets/plugins/kirby-mailjet/css/pastie.css') ?>
    <style>
        pre, form { margin: 40px 0px; }
        span.variable, span.array, span.list { background-color: rgb(0, 119, 0);;
color: rgb(255, 255, 255); }
        span.comment { background-color: rgb(255, 240, 240);
color: rgb(221, 34, 0);}

        .uniform__potty {
            position: absolute;
            left: -9999px;
        }
        form { padding: 40px;  background-color: #EEEEEE;  border: 1px solid #DEDEDE; }
        form label { display: inline-block; margin-top: 20px; width: 100px; margin-right: 10px; }
        form input { display: inline-block; margin-top: 20px; width: calc(100% - 140px); height: 20px;}
        form input[type=submit] {width: calc(100% - 20px); line-height: 20px; }
        form input.error { background-color: red; color: white; }
        form .error-text { margin: 10px 0; padding-left: 120px; font-size: 0.8em; color: red; }

    </style>
</head>
<body style="max-width: 1000px;margin:0 auto;font-family:'Helvetica Neue', Helvetica,Arial, sans-serif;">
    <center>
        <h1 style="margin-top:40px;"><?= $page->title() ?></h1>
        <div><a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="<?= $site->url() ?>/panel/pages/<?= $page->diruri() ?>/edit">Edit in Panel</a> <a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="https://github.com/bnomei/kirby-mailjet">Github Docs</a>
        <a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="https://mjml.io/try-it-live">mjml Online Editor</a>
        <br><br></div>
    </center>

    <?php if(site()->user()): ?>
    <pre><code data-language="php"><?php print_r(['hash'=>Kirbymailjet::hash()]) ?></code></pre>
    <?php endif; ?>

    <pre><code data-language="mustache"><?php print_r($mustache); ?></code></pre>

    <pre><code data-language="mustache"><?php echo $mjmlCode ?></code></pre>

    <?= js('assets/plugins/kirby-mailjet/js/rainbow-custom.min.js') ?>
    <script>
        Rainbow.extend('mustache', [
            {
                name: 'comment',
                pattern: /&lt;\!--[\S\s]*?--&gt;/g
            },
            {
                name: 'variable',
                pattern: /{{([\w\ ]+)}}/gm
            },
            {
                name: 'array.key',
                pattern: /\[([\w\ ]+)\]/gm
            },
            {
                name: 'list.begin',
                pattern: /{{(\#[\w\ ]+)}}/gm
            },
            {
                name: 'list.end',
                pattern: /{{(\/[\w\ ]+)}}/gm
            }
        ]);
    </script>

    <?php if($newsletterStatus = get('newsletter')): ?>
    <form>
        <p><b>Status: </b><?php echo $newsletterStatus ?></p>
    </form>
    <?php else: ?>
    <form action="<?php echo $page->url() ?>" method="POST">
        <h2><i>Newsletter Test</i> Contactslist Subscription</h2>

        <p>You have to create <i>firstname</i> and <i>lastname</i> as <a href="https://app.mailjet.com/contacts/lists/properties" target="_blank">custom Contact-Properties in your Mailjet-Dashboard</a>.</p>

        <label>Firstname</label>
        <input<?php if ($form->error('firstname')): ?> class="error"<?php endif; ?> name="firstname" type="text" value="<?php echo $form->old('firstname') ?>">
        <?php snippet('mj-example-form-error', ['field' => 'firstname']) ?>

        <label>Lastname</label>
        <input<?php if ($form->error('lastname')): ?> class="error"<?php endif; ?> name="lastname" type="text" value="<?php echo $form->old('lastname') ?>">
        <?php snippet('mj-example-form-error', ['field' => 'lastname']) ?>

        <label>Email</label>
        <input<?php if ($form->error('email')): ?> class="error"<?php endif; ?> name="email" type="email" value="<?php echo $form->old('email') ?>">
        <?php snippet('mj-example-form-error', ['field' => 'email']) ?>

        <?php echo csrf_field() ?>
        <?php echo honeypot_field() ?>
        <input type="submit" value="Submit">
        <?php snippet('mj-example-form-error', ['field' => \Uniform\Actions\EmailAction::class]) ?>
    </form>
    <?php endif; ?>
</body>
</html>