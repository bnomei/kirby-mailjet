<?php

	$isPanelBuilder = isset($page) && isset($data);

	/************************************
	 * Panel Kirby Builder CALL
	 */
	if($isPanelBuilder == true){
		
		$htmlFile = str_replace('.php', '.html', __FILE__);

		$mustachedata = [
			'text' => $data->text()->html()->value(), // string not Field-Object!
			'facebook' => url($data->facebook()),
			'googleplus' => url($data->googleplus()),
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
<mj-section mj-class="bgcolor" padding-top="96px">
	<mj-column>
        <mj-text mj-class="footer text">{{ text }}</mj-text>
        <mj-divider border-width="1px" border-style="solid" border-color="#71feac"/>
        <mj-social color="#71feac" padding-bottom="48px" mode="horizontal" display="google facebook" google-icon-color="#71feac" facebook-icon-color="#71feac" facebook-href="{{ facebook }}" google-href="{{ googleplus }}"/>
    </mj-column>
</mj-section>
<mj-raw><!--/PART:<?= $snippet ?>--></mj-raw>

<?php endif; ?>