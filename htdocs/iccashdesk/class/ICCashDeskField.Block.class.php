<?php
/* Copyright (C) 2014 IComm NOMOREDJO Jean-christophe <jcnrdjo@yahoo.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class ICCashDeskFieldBlock extends ICCashDeskField {
	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'block' : $this->widget);
		$this->element['#input'] = FALSE;
		$this->element['#value'] = $this->element['#default'];
		$this->element['#class'][] = 'block';
	}

	protected function blockWidget() {
		$html = '';

		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<div " . $this->element['#idstring'] . " $attrs>\n";
		if($this->settings['#icon']) $html .= "<span class=\"" . $this->settings['#icon'] . "\"></span>\n";
		$html .= "<div class=\"block-block\" >\n";
		$html .= "<div class=\"block-header\" >\n";
		$html .= "<span class=\"element-title\">" . $this->element['#title'] . "</span>\n";
		if($this->settings['close'])
			$html .= "<i class=\"block-close " . $this->settings['close'] . "\"></i>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"block-content\" >\n";
		$html .= $this->element['#value'];
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		return $html;
	}

	protected function bubbleWidget() {
		$html = '';

		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<div " . $this->element['#idstring'] . " $attrs>\n";
		$html .= "<i class=\"arrow top\"></i>\n";
		$html .= "<div class=\"block-block\">\n";
		$html .= "<div class=\"block-header\">\n";
		$html .= "<span class=\"element-title\">" . $this->element['#title'] . "</span>\n";
		if($this->settings['close'])
			$html .= "<i class=\"block-close " . $this->settings['close'] . "\"></i>\n";
		$html .= "</div>\n";
		$html .= "<div class=\"block-content\">\n";
		$html .= $this->element['#value'];
		$html .= "</div>\n";
		$html .= "</div>\n";
		$html .= "</div>\n";

		return $html;
	}
}
