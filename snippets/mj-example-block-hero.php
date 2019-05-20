<?php

    $isPanelBuilder = isset($page) && isset($data);

    /************************************
     * Panel Kirby Builder CALL
     */
    if ($isPanelBuilder == true) {
        $htmlFile = str_replace('.php', '.html', __FILE__);

        $mustachedata = [
            'heroheadline' => $data->heroheadline()->html()->value(), // string not Field-Object!
            'herobutton' => $data->herobutton()->html()->value(), // string not Field-Object!
            'herolink' => url($data->herolink()->value()),
            'heroimage' => url($data->heroimage()->value()),
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
<mj-wrapper mj-class="bgcolor">
    <mj-hero mode="fixed-height" height="200px" background-width="1200px" background-height="800px" background-url="{{ heroimage }}" background-color="#eafded" padding="48px 0px">
        <mj-hero-content width="100%">
            <mj-text mj-class="heroheadline">{{ heroheadline }}</mj-text>
            <mj-button 
                mj-class="button"
                href="{{ herolink }}" >{{ herobutton }}</mj-button>
        </mj-hero-content>
    </mj-hero>
</mj-wrapper>
<mj-raw><!--/PART:<?= $snippet ?>--></mj-raw>

<?php endif; ?>