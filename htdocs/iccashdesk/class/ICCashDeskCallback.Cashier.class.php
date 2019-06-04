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

class ICCashDeskCallbackCashier extends ICCashDeskCallback {
	public function __construct() {
		$this->name = 'cashier';
		$this->class = __CLASS__;
	}

	public function payblock() {
		$info = new StdClass();
		$info->title = '';
		$info->content = 'no data';

		if($name = GETPOST('mode', 'alpha', 1)) {
			ICCashDeskPayment::init($name);
			if($paymode = ICCashDeskPayment::load($name)) {
				$info->title = $paymode->title;
				$info->content = $paymode->render();
			}
		}

		header("Content-Type: application/json; charset=utf-8");
		print json_encode($info);
	}

	public function newsell() {
		//ICCashDesk::$sell->reset();
	}

	public function suspend() {
		//ICCashDesk::$sell->reset();
	}

	public function cashline() {
		require_once('ICCashDeskField.Grid.class.php');
		$newline = '';
		$index = GETPOST('index', 'int', 1);
		$name = GETPOST('name', 'alpha', 1);

		if(isset($index) && $name) {
			$items = ICCashDeskTabCashier::getFields();
			$grid = new ICCashDeskFieldGrid($items['grid']);
			$newline = $grid->newline($index);
		}
		print $newline;
	}

	public function addReportLine() {
		$html = '';

		$index = GETPOST('index', 'int', 1);

		ICCashDeskPayment::init();
		$item = ICCashDeskPayment::load('report')->getFields('report');
		$item['#settings']['index'] = (empty($index) ? 0 : $index);
		$html = ICCashDeskField::display($item);

		print $html;
	}
}
