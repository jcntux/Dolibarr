<?php
/* Copyright (C) 2013 IComm NOMOREDJO Jean-christophe <jcnrdjo@yahoo.fr>
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

abstract class ICCashDeskGrid {
	private $index;
	private $name;
	private $items;

	public function __construct($name = NULL) {
		$this->index = 0;
		$this->empty = NULL;
		$this->items = array();
		$this->name = $name;
	}

	public function getCursor() {
		return $this->index;
	}

	public function setCursor($index = NULL) {
		$this->index = $index;
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name = NULL) {
		$this->name = $name;;
	}

	public function getItems() {
		return $this->$items;
	}

	public function setItems($items = array()) {
		$this->items = $items;
	}

	public function load($name = NULL) {
		$this->setItems();
		if($name) $this->setName($name);

		if($items = GETPOST($this->name, '', 2)) {
			$this->setItems($items);
		}
	}

	public function render() {
		$html ='';

		$html .= "<table id=\"$this->name\" class=\"grid\">\n";
		$html .= "<tr class=\"grid-header\">\n";
		foreach($this->cells() as $name => $cdef) {
			$html .= "<td class=\"header-$name\">\n";
			if($cdef['title']) {
				$html .= $cdef['title'];
			}
			$html .= "</td>\n";
		}
		$html .= "</tr>\n";

		if(count($this->items) == 0) {
			$html .= $this->newline(0);

		} else {
			foreach($this->items as $id => $item) {
				$html .= $this->newline($id, $item);
			}
		}
		$html .= "</table>\n";
		$html .= "<input type=\"hidden\" grid=\"" . $this->name . "\" name=\"" . $this->name . '-index' . "\" value=\"" . $this->index . "\" />\n";

		return $html;
	}
	
	public function newline($id = NULL, $item = array(), $attrs = array()) {
		$html = '';
		$indexed = FALSE;

		if(isset($id)) {
			$index = $id;
			if($index > $this->index) $this->index = $index;

		} else {
			$index = $this->index;
		}
		$params = array('id' => "line-$id", 'class' => 'grid-line', 'index' => $index);
		$attrs = ICCashDeskElements::getattribute($attrs, $params);

		$html .= "<tr $attrs>\n";
		foreach($this->cells() as $name => $cdef) {
			$callback = (isset($cdef['callback']) ? $cdef['callback'] : NULL);
			$name = strtolower($name);
			if($callback && method_exists($this, $callback)) {
				$cell = (isset($item[$name]) ? $item[$name] : NULL);

				$html .= "<td class=\"grid-cell cell-$name\">\n";
				$html .= $this->$callback($id, $cell);
				if(!$indexed) {
					$html .= ICCashDeskElements::hidden("index[$id]", $id);
					$indexed = TRUE;
				}
				$html .= "</td>\n";
			}
		}
		$html .= "</tr>\n";

		$html .= "<tr class=\"grid-line\"><td>\n";
		$options = array('icon' => 'icon-arrow top');
		$html .= ICCashDeskElements::block(NULL, NULL, NULL, $options);
		$html .= "</td></tr>\n";

		$this->index++;
		return $html;
	}

	abstract public function cells();
}
