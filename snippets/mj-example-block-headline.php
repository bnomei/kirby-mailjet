<?php

    $isPanelBuilder = isset($page) && isset($data);

    /************************************
     * Panel Kirby Builder CALL
     */
    if ($isPanelBuilder == true) {
        $htmlFile = str_replace('.php', '.html', __FILE__);

        $mustachedata = [
            'headline' => $data->headline()->html()->value(), // string not Field-Object!
        ];

        //a::show($mustachedata);
        if (isset($json) && $json) {
            echo a::json($mustachedata);
        } else {
            echo KirbyMailjet::renderMustache($htmlFile, $mustachedata);
        }
    }

    /************************************
     * Kirbymailjet::buildMJML CALL
     */
    if ($isPanelBuilder == false):

        $snippet = basename(__FILE__, '.php');
?>
<mj-raw><!--PART:<?= $snippet ?>--></mj-raw>
<mj-section mj-class="bgcolor">
	<mj-column>
		<mj-text mj-class="headline">{{ headline }}</mj-text>
	</mj-column>
</mj-section>
<mj-raw><!--/PART:<?= $snippet ?>--></mj-raw>

<?php endif; ?>