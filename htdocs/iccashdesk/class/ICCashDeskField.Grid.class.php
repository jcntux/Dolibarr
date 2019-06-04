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

class ICCashDeskFieldGrid extends ICCashDeskField {
	private $index;
	private $items;
	private $data;
	const PREFIX = 'grid';

	protected function hookInit() {
		$this->index = 0;
		$this->data = array();
		$this->row = array();
		$this->attributes['class'][] = 'grid-line';
		$this->element['#multiple'] = TRUE;
		$this->element['#js']['#grid'] = array(
			'prefix' => self::PREFIX,
		);

		foreach($this->settings['items'] as $realname => $item) {
			$name = self::PREFIX . $realname;
			if($item['#type']) {
				$item['#name'] = $name;
				$item['#realname'] = $realname;
				$item['#multiple'] = $this->element['#multiple'];
			}
			$this->items[$name] = $item;
		}
		unset($this->settings['items']);
		$this->widget = (empty($this->widget) ? 'grid' : $this->widget);
	}

	protected function hookStore() {
		$this->data = array();

		foreach($this->post as $key => $value) {
			$suffix = self::parseName($key);
			if(isset($suffix->id) && !$this->data[$suffix->id]) {
				foreach($this->items as $name => $item) {
					$field = ICCashDeskField::load($item);
					if(is_object($field)) {
						$postname = $name . $suffix->id;
						$this->data[$suffix->id][$name] = (isset($this->post[$postname]) ? $this->post[$postname] : NULL);
					}
				}
			}
		}
	}

	protected function hookValidate() {
		$success = TRUE;

		foreach($this->data as $index => $row) {
			foreach($this->items as $name => $item) {
				$field = ICCashDeskField::load($item);
				if(is_object($field)) {
					$field->post = $row[$name];
					$field->store();
					
					if($field->validate() == FALSE) {
						$success = FALSE;
						$this->data[$index]['#error'][$name] = $field->element['#error'];
					}
				}
			}
		}

		return $success;
	}

	protected function gridWidget() {
		$html = '';

		$html = "<tr class=\"grid-header\">\n";
		foreach($this->settings['head'] as $name => $item) {
			$html .= "<td class=\"header-$name\">\n";
			if(is_array($item)) {
				foreach($item as $subitem) {
					$field = ICCashDeskField::load($subitem);
					$html .= $field->render();
				}

			} else {
				$html .= $item;
			}
			$html .= "</td>\n";
		}
		$html .= "</tr>\n";

		if(count($this->data) == 0) {
			$this->index = 0;
			$html .= self::newline(0);

		} else {
			foreach($this->data as $index => $row) {
				$html .= self::newline($index, $row);
			}
		}
		$this->index ++;
		$html = "<table id=\"$this->name\" class=\"grid\" index=\"$this->index\">\n" . $html;
		$html .= "</table>\n";

		return $html;
	}

	public function newline($index, $row = array()) {
		$html = '';

		$this->index = $index;
		$this->attributes['index'] = $index;
		if($key = array_search('error', $this->attributes['class'])) {
			unset($this->attributes['class'][$key]);
		}
		$attrs = ICCashDesk::serialize($this->attributes);
		$html .= "<tr $attrs>\n";
		foreach($this->items as $name => $item) {
			$html .= "<td class=\"grid-cell cell-" . strtolower($name) . "\">\n";
			if(isset($item['#type'])) {
				if($row['#error'][$name])
					$item['#error'] = $row['#error'][$name];
				$field = ICCashDeskField::load($item);
				$field->name = $name . $index;
				$field->post = $row[$name];
				$field->store();
				$html .= $field->render();

			} elseif(is_array($item)) {
				foreach($item as $child) {
					$field = ICCashDeskField::load($child);
					$html .= $field->render();
				}

			}
			$html .= "</td>\n";
		}
		$html .= "</tr>\n";

		return $html;
	}

	private static function parseName($name) {
		$result = NULL;

		if(preg_match('/^' . self::PREFIX . '(.+?)(\d+)$/', $name, $matches) == 1) {
			$result = new StdClass();
			array_shift($matches);
			list($result->name, $result->id) = $matches;
		}

		return $result;
	}

}
