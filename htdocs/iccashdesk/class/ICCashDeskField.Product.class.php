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

class ICCashDeskFieldProduct extends ICCashDeskField {
	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'autocomplete' : $this->widget);
		$this->element['#class'][] = 'select-autocomplete';
		if(!isset($this->element['#showfull']))
			$this->element['#showfull'] = TRUE;
	}

	protected function autocompleteWidget() {
		$html = '';

                ICCashDesk::addSettings(
                        array(
                                'callbacks' => array(
                                        'getproduct' => 'getProduct',
                                ),
                        )
                );

		$this->element['#realname'] = 'search_' . $this->element['#realname'];
		if($this->settings['callback']) {
			$params = array(
				'callback' => 'getProducts',
				'name' => $this->name,
			);
			$this->settings['callback'] = ICCashDesk::url(NULL, $params);
			$attrs = ICCashDesk::serialize($this->attributes);
			$html .= "<span class=\"element-title\">". $this->element['#title'] . "</span>\n";
			$html .= ajax_autocompleter($this->element['#value'], $this->name, $this->settings['callback'], '', 3) . "\n";
			$html .= "<input type=\"text\" name=\"search_$this->name\" id=\"search_$this->name\" $attrs/>\n";
		}
		return $html;
	}
}
