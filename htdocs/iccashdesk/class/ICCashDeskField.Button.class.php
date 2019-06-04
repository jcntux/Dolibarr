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

class ICCashDeskFieldButton extends ICCashDeskField {
	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'button' : $this->widget);
		$this->element['#class'][] = 'icbutton';
		$this->element['#input'] = FALSE;
		$this->element['#value'] = $this->element['#default'];
	}

	protected function buttonWidget() {
		$html = '';

		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<button type=\"button\" name=\"$this->name\" value=\"" . $this->element['#value'] . "\" $attrs>\n";
		$html .= $this->element['#title'];
		$html .= "</button>\n";

		return $html;
	}

	protected function submitWidget() {
		$html = '';

		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<button type=\"submit\" name=\"$this->name\" value=\"" . $this->element['#value'] . "\" $attrs>\n";
		$html .= $this->element['#title'];
		$html .= "</button>\n";

		return $html;
	}
}
