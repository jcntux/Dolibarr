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

require_once('ICCashDeskForm.class.php');

class ICCashDeskTab {
	private static $tabs;
	private static $current;

	public static function init() {
		self::$tabs = array();
		self::$current = NULL;

		foreach(ICCashDesk::getClasses('ICCashDeskTab') as $class) {
			$tab = new $class();
			self::$tabs[$tab->name] = $tab;
		}
	}

	public function display() {
		$html = '';

		if(!self::getCurrent()) {
			return ICCashDesk::pagenotfound('no tabs found');
		}
		self::$current->init();

		$html .= "<div class=\"tabs\">\n";
		$html .= "<div class=\"tabs-header\">\n";
		$html .= "<ul>\n";
		foreach(self::$tabs as $name => $tab) {
			$active = ($name == self::$current->name ? 'class="active"' : '');
			$html .= "<li $active>\n";
			$html .= "<a href=\"" . ICCashDesk::url(NULL, array('tab' => $name)) . "\">" . $tab->caption . "</a>\n";
			$html .= "</li>\n";
		}
		$html .= "</ul>\n";
		$html .= "</div> <!-- tabs-header -->\n";

		$html .= "<div class=\"tabs-content\">\n";
		$html .= self::$current->render();
		$html .= "</div> <!-- tabs-content -->\n";
		$html .= "</div> <!-- tabs -->\n";

		return $html;
	}

	public static function getCurrent() {
		if(self::$current)
			return self::$current;

		if(($name = GETPOST('tab', 'alpha', 1)) && ($tab = self::getTab($name)->class)) {
			self::$current = new $tab();

		} elseif(is_array(self::$tabs)) {
			$tab = reset(self::$tabs)->class;
			self::$current = new $tab();
		}

		return self::$current;
	}

	public static function getTabs() {
		return (isset(self::$tabs) ? self::$tabs : array());
	}

	private static function getTab($name = NULL) {
		if($name && isset(self::$tabs[$name])) {
			return self::$tabs[$name];
		}

		return NULL;
	}
}
