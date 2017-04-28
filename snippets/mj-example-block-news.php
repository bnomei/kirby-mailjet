<?php

	$isPanelBuilder = isset($page) && isset($data);

	/************************************
	 * Panel Kirby Builder CALL
	 */
	if($isPanelBuilder == true){
		
		$htmlFile = str_replace('.php', '.html', __FILE__);

		$news = array();
		foreach ($page->children()/*->visible()*/ as $subpage) {
			$utm = implode([
					'?utm_source='.urlencode(site()->title()->value()),
					'&utm_medium=email',
					'&utm_campaign='.$page->slug(), // using slug of newsletter, not slug of subpage
				]);
			$news[] = [
				'date' => date('m/d/Y', $subpage->modified()),
				'text' => $subpage->text()->html()->toString(),
				'link' => url($subpage->url().$utm),
			];
		}

		$mustachedata = [
			'newsheadline' => $data->newsheadline()->html()->value(),
			'news' => $news,
		];

		//a::show($mustachedata);
		if(isset($json) && $json) {
			echo a::json($mustachedata);
		} else {
			echo KirbyMailjet::renderMustache($htmlFile, $mustachedata);
		}
	}

	/************************************
	 * Kirbymailjet::buildMJML CALL
	 */
	if($isPanelBuilder == false):

		$snippet = basename(__FILE__, '.php');
?>
<mj-raw><!--PART:<?= $snippet ?>--></mj-raw>
<mj-section mj-class="bgcolor">
	<mj-column>
        <mj-text mj-class="newsheadline">{{ newsheadline }}</mj-text>
    </mj-column>
</mj-section>
<mj-wrapper mj-class="newsblock">
	<mj-raw>{{# news }}</mj-raw>
    <mj-section mj-class="newsitem">
        <mj-column width="25%">
            <mj-text mj-class="newsdate">{{ date }}</mj-text>
        </mj-column>
        <mj-column width="55%">
            <mj-text>{{ text }}</mj-text>
        </mj-column>
        <mj-column width="20%" vertical-align="bottom">
            <mj-button mj-class="button" padding="0" href="{{ link }}">&#x2192;</mj-button>
        </mj-column>
    </mj-section>
	<mj-raw>{{/ news }}</mj-raw>
</mj-wrapper>
<mj-raw><!--/PART:<?= $snippet ?>--></mj-raw>

<?php endif; ?>