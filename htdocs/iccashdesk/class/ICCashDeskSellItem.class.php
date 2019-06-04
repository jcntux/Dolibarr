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

require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

class ICCashDeskSellItem {
	public $iid;
	public $pid;
	public $pu;
	public $tva;
	public $discount;
	public $qty;
	public $stock;

	public function __construct($pid, $qty, $discount = 0) {
		$this->iid = NULL;
		$this->pid = NULL;
		if(self::fetch($pid)) {
			$this->qty = (isset($qty) ? $qty : 0);
			$this->tva = (isset($tva) ? $tva : 0);
			$this->discount = (isset($discount) ? $discount : 0);
		}

	}

	private function fetch($pid = NULL) {
		$p = new Product(ICCashDesk::$db);
		$p->fetch($pid);
		if($p->id) {
			$this->pid = $p->id;
			$this->pu = $p->price;
			$this->stock = $p->stock_reel;

			return TRUE;
		}

		return FALSE;
	}
}
