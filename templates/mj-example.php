<!DOCTYPE html>
<html>
<head>
	<title><?= $page->title() ?></title>
	<?= css('assets/plugins/kirby-mailjet/css/pastie.css') ?>
	<style>
		pre { margin: 40px 0px; }
		span.variable, span.array, span.list { background-color: rgb(0, 119, 0);;
color: rgb(255, 255, 255); }
		span.comment { background-color: rgb(255, 240, 240);
color: rgb(221, 34, 0);}
	</style>
</head>
<body style="max-width: 1000px;margin:0 auto;font-family:'Helvetica Neue', Helvetica,Arial, sans-serif;">
	<center>
		<h1 style="margin-top:40px;"><?= $page->title() ?></h1>
		<div><a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="<?= $site->url() ?>/panel/pages/<?= $page->diruri() ?>/edit">Edit in Panel</a> <a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="https://github.com/bnomei/kirby-mailjet">Github Docs</a>
		<a style="border-radius: 5px;border:1px solid #666;color:#666;text-decoration:none;padding:5px;" target="_blank" href="https://mjml.io/try-it-live/HJWTTKyJW">mjml Online Editor</a>
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
</body>
</html>