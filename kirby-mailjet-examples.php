<?php

/****************************************
  FIELDS
 ***************************************/

$fields = new Folder(__DIR__ . DS . 'fields');
foreach ($fields->children() as $folder) {
	$folderFiles = new Folder($folder->root());
	foreach ($folderFiles->files() as $file) {
		// field root
		if($file->extension() == 'php') {
			$kirby->set('field', $file->name(), $file->dir());
		}
	}
}

/****************************************
  BLUEPRINTS
 ***************************************/

$blueprints = new Folder(__DIR__ . DS . 'blueprints');
foreach ($blueprints->files() as $file) {
  $kirby->set('blueprint', $file->name(), $file->root());
}
/* manual file copy by user is better since examples disabled on production servers anyway.
*/
/*
$blueprintsFields = new Folder(__DIR__ . DS . 'blueprints' . DS . 'fields');
foreach ($blueprintsFields->files() as $file) {
  $kirby->set('blueprint', 'fields/'.$file->name(), $file->root());
}*/


/****************************************
  SNIPPETS
 ***************************************/

/* manual file copy by user is better since examples disabled on production servers anyway.
*/
/*
$snippets = new Folder(__DIR__ . DS . 'snippets');
foreach ($snippets->files() as $file) {
  if($file->extension() == 'php') {
    $kirby->set('snippet', $file->name(), $file->root());  
  }
}
// https://github.com/TimOetting/kirby-builder/blob/master/builder.php#L43
if(c::get('plugin.mailjet.buildersnippets', false)) {
	c::set('buildersnippets.path', __DIR__ . DS . 'snippets');
}
*/

/****************************************
  TEMPLATES and CONTROLLERS
 ***************************************/

$kirby->set('controller', 'mj-example', __DIR__ . DS . 'controllers' . DS . 'mj-example.php');
$kirby->set('template', 'mj-example', __DIR__ . DS . 'templates' . DS . 'mj-example.php');


/****************************************
  HOOKS
 ***************************************/

$kirby->set('hook', 'panel.page.create', function($page) {
if($page->template() == 'mj-example') {
  try {
    $builder = [
      [
        'headline' => 'Responsive Email Builder',
        '_fieldset' => 'headline',
      ],
      [
        'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam posuere, orci in **tristique consectetur**, quam lacus pulvinar ex, quis facilisis nibh velit *non magna*. In imperdiet est sit amet tincidunt pellentesque.',
        '_fieldset' => 'text',
      ],
      [
        'heroheadline' => 'Kirby Mailjet Plugin',
        'herobutton' => 'Buy a license',
        'herolink' => 'https://bnomei.onfastspring.com/kirby-mailjet',
        'heroimage' => 'https://github.com/bnomei/kirby-mailjet/assets/images/mj-example-heroimage.png?raw=true',
        '_fieldset' => 'hero',
      ],
      [
        'newsheadline' => 'News',
        '_fieldset' => 'news'
      ],
      [
        'text' => 'Sed ante turpis, feugiat ac tellus ac, cursus molestie tellus. Sed sem est, malesuada in dignissim vitae, porta ut magna.',
        'facebook' => "",
        'googleplus' => "",
        '_fieldset' => 'footer',
      ],
    ];
    $page->update([
      'title' => 'Mailjet Plugin Example Page',
      'builder' => yaml::encode($builder),
    ]);

    $subpages = [
      [
        'title' => 'Adipiscing',
        'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
      ],
      [
        'title' => 'Facilisis',
        'text' => 'Nam posuere, orci in tristique consectetur, quam lacus pulvinar ex, quis facilisis nibh velit non magna.',
      ],
      [
        'title' => 'Tincidunt',
        'text' => 'In imperdiet est sit amet tincidunt pellentesque.',
      ],
    ];
    foreach($subpages as $subpage) {
      $page->create(
        $page->diruri().'/'.str::slug($subpage['title']),
        'default',
        $subpage
      );
    }

  } catch(Exception $ex) {
    // echo $ex->getMessage();
  }
}
});
