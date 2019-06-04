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

abstract class ICCashDeskFieldHandler {
	private static $handlerlers;

	public static function init() {
		self::$handlerlers = array();

		foreach(ICCashDesk::getClasses('ICCashDeskFieldHandler') as $class) {
			if(($info = $class::register()))
				self::setController($info, $class);
		}
	}

	private static function setController($item = array(), $class) {
		foreach($item as $name => $desc) {
			self::$handlerlers[$name] = new StdClass();
			self::$handlerlers[$name]->name = $name;
			self::$handlerlers[$name]->description = $desc;
			self::$handlerlers[$name]->class = $class;
		}
	}

	public static function getController($name = NULL) {
		if(!$name) {
			return self::$handlerlers;

		} elseif(isset(self::$handlerlers[$name])) {
			return self::$handlerlers[$name];
		}

		return NULL;
	}

	abstract protected static function register();
}
