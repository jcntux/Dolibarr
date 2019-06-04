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

require_once('ICCashDeskSellItem.class.php');

class ICCashDeskSell {
	public static $sid;
	public static $cid;
	public static $wid;
	public static $totalht;
	public static $tva;
	public static $note;
	public static $received;
	public static $items;
	public static $paymode;

	private static $index;

	public function init() {
		self::$sid = time();
		self::$cid = NULL;
		self::$wid = NULL;
		self::$totalht = NULL;
		self::$tva = NULL;
		self::$note = NULL;
		self::$received = NULL;
		self::$index = 0;
		self::$items = array();
		self::$paymode = NULL;
	}

	public function reset() {
		if($_SESSION['SELL']) unset($_SESSION['SELL']);
		self::init();
	}

	public function save() {
		$_SESSION['SELL'] = serialize($this);
	}

	public function load() {
		if($_SESSION['SELL']) {
			return unserialize($_SESSION['SELL']);
		}

		return NULL;
	}

	public function addItem($item = NULL) {
		if(($item instanceof ICCashDeskSellItem) && $item->pid) {
			$item->iid = self::setIndex();
			$this->items[$item->iid] = $item;
		}

		return $item->iid;
	}

	public function getItem($id = NULL) {
		if($id) {
			return (isset($this->items[$id]) ? $this->items[$id] : NULL);
			
		} elseif(count($this->items) > 0) {
			return $this->items;
		}

		return NULL;
	}

	public function deleteItem($id = NULL) {
		if(isset($id) && $this->items[$id]) {
			unset($this->items[$id]);
		}
	}

	private static function setIndex() {
		self::$index++;

		return self::$index;
	}

	public static function getIndex() {
		return self::$index;
	}

	public function flush() {
		$this->init();
	}

	public function execute() {
		$result = FALSE;

		if(count($this->items) == 0) {
			return FALSE;
		}

		foreach($this->items as $item) {
			// discount
			$this->totalht += ($item->pu * $item->qty);
		}
		// Generate invoice
		//ICCashDeskInvoice::generate();

		// Generate payment orders
		if($mode = ICCashDeskPaymentCore::getPayment($this->paymode)) {
			$class = $mode->class;
			//$payment = new $class();
		}

		return $result;
	}
}
