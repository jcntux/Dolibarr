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

class ICCashDeskFieldSelect extends ICCashDeskField {
	public function hookInit() {
		$this->widget = (empty($this->widget) ? 'select' : $this->widget);
	}

	protected function selectWidget() {
		$html = '';

		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<select name=\"$this->name\" $attrs>\n";
		foreach($this->settings['options'] as $key => $val) {
			$html .= "<option value=\"$key\">$val</option>\n";
		}
		$html .= "</select>\n";

		return $html;
	}

	protected function bankWidget() {
		$html = '';

		$form = new Form(ICCashDesk::$db);
		$html .= "<span class=\"element-title line-break\">". $this->element['#title'] . "</span>\n";
		ob_start();
		$form->select_comptes($this->element['#value'], $this->name, 0, $this->settings['filter']);
		$html .= ob_get_contents();
		ob_end_clean();

		return $html;
	}

	protected function clientWidget() {
		$html = '';

		$form = new Form(ICCashDesk::$db);
		$html .= "<span class=\"element-title line-break\">". $this->element['#title'] . "</span>\n";
		$html .= $form->select_company($this->element['#value'], $this->name, 's.client in (1,3)', 0, 0);

		return $html;
	}

	protected function dateWidget() {
		$html = '';

		$form = new Form(ICCashDesk::$db);

		$html .= "<span class=\"element-title line-break\">". $this->element['#title'] . "</span>\n";
		$html .= $form->select_date(-1,$this->name . "[$index][]", 0, 0, 0, '', 1, 0, 1);
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
}
