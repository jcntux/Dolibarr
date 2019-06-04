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

class ICCashDeskFieldLink extends ICCashDeskField {
	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'link' : $this->widget);
		$this->element['#input'] = FALSE;
	}

	protected function linkWidget() {
		$html = '';

		if($this->settings['url']) {
			$attrs = ICCashDesk::serialize($this->attributes);
			$html .= "<a href=\"" . $this->settings['url'] . "\" $attrs>" . $this->element['#title'] . "</a>\n";
		}

		return $html;
	}
}
