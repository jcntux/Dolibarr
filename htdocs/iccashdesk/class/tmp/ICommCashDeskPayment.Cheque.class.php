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

class IcommCashDeskPaymentCheque extends ICommCashDeskPayment {
	private static $name;
	private static $title; 
	private static $callback;

	public function __contruct() {
		self::init();
	}

	public function init() {
		self::$name = 'cheque';
		self::$title = 'Cheque';
		self::$callback = 'paymentinfo';

		$register = new StdClass();
		$register->name = self::$name;
		$register->title = self::$title;
		$register->callback = self::$callback;

		return $register;
	}

	public static function render() {
		$html = '';

		$options = array('filter' => 'courant = 1');
		$html .= ICommCashDeskElements::selectBank('bankid', 'Bank', NULL, $options);

		$options = array('maxLength' => 20, 'size' => '20');
		$html .= ICommCashDeskElements::text('chequeid', 'Cheque number', NULL, $options);

		$options = array('maxLength' => 30, 'size' => '30', 'filter' => 'courant = 1');
		$html .= ICommCashDeskElements::text('sender', 'Sender', NULL, $options);

		return $html;
	}

	public static function validateField(&$form) {
		return TRUE;
	}
}
