<?php

class MessageboxField extends BaseField {
	public function input() {
		$fontwidth = 'normal';
		$fontstyle = 'normal';
		$padding = isset($this->padding) ? $this->padding : '10px';
		$textalign = isset($this->align) ? $this->align : 'center';
		$color = isset($this->color) ? $this->color : '#000';
		$borderwidth = '2px';
		$borderstyle = 'solid';
		$borderradius = '0px';
		$bordercolor = isset($this->bordercolor) ? $this->bordercolor : '#000';
		$backgroundcolor = isset($this->backgroundcolor) ? $this->backgroundcolor : 'transparent';

		$reminder = new Brick('div', $this->text);
		$reminder->attr('style', "width:100%;text-align:$textalign;font-weight:$fontwidth;font-style:$fontstyle;color:$color;background-color:$backgroundcolor;border:$borderwidth $borderstyle $bordercolor;padding:$padding;border-radius:$borderradius;");

		return $reminder;
	}
}