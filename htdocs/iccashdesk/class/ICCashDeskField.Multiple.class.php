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

class ICCashDeskFieldMultiple extends ICCashDeskField {
	private $index;

	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'simple' : $this->widget);
		$this->element['#multiple'] = TRUE;

		$this->index = (isset($this->settings['index']) ? $this->settings['index'] : 0);
		$this->element['#mainattrs']['index'] = $this->index;
		if($this->settings['callback'])
			$this->element['#prefix'] .= ICCashDeskField::display(ICCashDeskField::hidden('callback-' . $this->name, $this->settings['callback']));

	}

	protected function simpleWidget() {
		return $this->newItem();
/*
        if($index == 0) {
                $output .= "<button id=\"adddate\" type=\"button\" class=\"dpInvisibleButtons\">\n";
                $output .= img_picto($langs->trans('AddDate'), 'plus-12.png@iccashdesk');
                $output .= "</button>\n";

        } elseif($index > 0) {
                $output .= "<button type=\"button\" class=\"deldate dpInvisibleButtons\">\n";
                $output .= img_picto($langs->trans('Delete'), 'cancel-12.png@iccashdesk');
                $output .= "</button>\n";
        }
        $output .= "<button id=\"erasedate-" . $index . "\" type=\"button\" class=\"erasedate dpInvisibleButtons\" index=\"$index\">\n";
        $output .= img_picto($langs->trans('Erase'), 'eraser-12.png@iccashdesk');
        $output .= "</button>\n";
        $output .= "</div>\n";
*/
		return $html;
	}

	public function newItem() {
		$html = '';

		foreach($this->settings['items'] as $name => $item) {
			$item['#realname'] = $item['#name'];
			$item['#name'] .= $this->index;
			$html .= ICCashDeskField::load($item)->render();
		}
		$html .= ICCashDeskField::load(ICCashDeskField::action('add', 'icon-plus', NULL, array('#js' => array('add'))))->render();
		$html .= ICCashDeskField::load(ICCashDeskField::action('del', 'icon-minus', NULL, array('#js' => array('del'))))->render();

		return $html;
	}

	protected function hookStore() {
ICCashDesk::debug($this->post);
	}
}
