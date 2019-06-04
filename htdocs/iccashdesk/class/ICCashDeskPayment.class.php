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

abstract class ICCashDeskPayment {
	private static $payments;
	private static $default;
	public $class;

	public $name;
	public $title;
	public $settings;

	public function __construct($item) {
			$this->name = $item->name;
			$this->class = $item->class;
			$this->title = $item->title;
			$this->settings = $item->settings;
	}

	public static function init($name = NULL, $settings = array()) {
		self::$default = NULL;
		self::$payments = array();
		$weights = $payments = array();
		$weight = 100;

		if($name) {
			$candidates = array(ICCashDesk::getClass('ICCashDeskPayment', $name));

		} else {
			$candidates = ICCashDesk::getClasses('ICCashDeskPayment');
		}

		foreach($candidates as $class) {
			if(method_exists($class, 'hookInit')) {
				$payment = $class::hookInit();
				if($payment->name && !$payment->disabled) {
					$payment->class = $class;
					$payment->settings = (isset($payment->settings) ? $payment->settings : array());
					if(is_array($settings)) {
						$payment->settings = array_merge($payment->settings, $settings);
					}
					$payments[$payment->name] = $payment;
					$weight = (isset($payment->weight) ? $payment->weight : $weight);
					$weights[$weight][] = $payment->name;
				}
			}
		}

		if(count($weights) > 0) {
			ksort($weights);
			foreach($weights as $item) {
				foreach($item as $name) {
					self::$payments[$name] = $payments[$name];
				}
			}

			self::$default = reset($payments)->name;
		}
	}

	public static function load($name, $settings = array()) {
		if($item = self::$payments[$name]) {
			$item->settings = (isset($item->settings) ? $item->settings : array());
			if(is_array($settings)) {
				$item->settings = array_merge($item->settings, $settings);
			}
			$class = $item->class;

			return new $class($item);
		}

		return NULL;
	}

	public static function getCurrent() {
		$current = GETPOST('paymode', 'alpha', 2);

		if($current && self::$payments[$current])
			return self::$payments[$current];

		else if(self::getDefault())
			return self::getDefault();

		else
			return NULL;
	}

	public static function getDefault() {
		return (self::$default ? self::$payments[self::$default] : NULL);
	}

	public static function getPayments() {
		return self::$payments;
	}

	public function render() {
		$html = '';

		if(method_exists($this->class, 'hookRender')) {
			$html .= $this->hookRender();
		}
		$html .= ICCashDeskField::display(ICCashDeskField::hidden('paymode', $this->name));

		return $html;
	}

	public function validate(&$form) {
		if(!isset($form->post['paymode'])) {
			return FALSE;
		}

		$name = $form->post['paymode'];
		if($mode = self::getPayment($name)) {
			$class = $mode->class;
			if(method_exists($class, 'validate') && $class::validate($form)) {
				return TRUE;
			}
		}

		return FALSE;
	}
}
