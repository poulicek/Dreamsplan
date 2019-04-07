<?php

abstract class Controls
{
	public static function PrintWindow($id, $content, $visible = true)
	{
		echo self::GetWindow($id, $content, $visible);
	}

	public static function GetWindow($id, $content, $visible = true, $jsCallback = null)
	{
		$html  = '<div id="'.$id.'" class="popup" '.($visible ? '' : 'style="display: none"').'>';
		$html .= '<div class="fader"></div>';
		$html .= '<div class="window">';
		$html .= '<table><tr><td class="windowContent">';
		$html .= $content;
		$html .= '</td></tr>';
		$html .= '<tr><td class="windowFooter">';
		$html .= '<a onmouseup="'.($jsCallback ? $jsCallback : 'tasklists.ClosePopup(this)').'" onfocus="this.blur()" href="javascript:void(0)" class="button">continue</a>';
		$html .= '</td></tr></table>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}

?>
