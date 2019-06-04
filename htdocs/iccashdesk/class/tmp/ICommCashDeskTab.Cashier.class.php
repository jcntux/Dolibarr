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

require_once('ICommCashDeskGrid.class.php');
require_once('ICommCashDeskPaymentCore.class.php');
require_once('ICommCashDeskSell.class.php');

class ICommCashDeskTabCashier extends ICommCashDeskTab {
	private static $grid;
	private static $sell;
	private static $form;
	private static $payblock;

	public function __construct() {
		$this->name = 'cashier';
		$this->caption = 'Cashier';
		$this->weight = 1;
		$this->class = __CLASS__;
		self::$payblock = 'payment-block';
	}

	public function init() {
		ICommCashDeskPaymentCore::init();
		ICommCashDeskSell::init();
		self::$sell = ICommCashDeskSell::load();
		self::$form = new ICommCashDeskFormCore('sellform', $fields);
		self::$form->render();

		if(self::$form->validated) {
			if(self::validateForm()) {
				//self::handleForm();
				//self::$sell->execute();

			} else {
				// error
				print "ERROR";
			}
		}
	}

	private static function validateForm() {
		self::$form->validated = TRUE;

		if(! ICommCashDeskPaymentCore::validate(self::$form)) self::$form->validated = FALSE;
		//if(!isset(self::$form->post['cid'])) self::$form->validated = FALSE;
		//if(!isset(self::$form->post['wid'])) self::$form->validated = FALSE;
		self::$sell->note = (isset(self::$form->post['note']) ? self::$form->post['note'] : NULL);

		foreach(self::$form->post['index'] as $index) {
			if(!isset(self::$form->post['qty'][$index])) self::$form->validated = FALSE;
			if(!isset(self::$form->post['product'.$index])) self::$form->validated = FALSE;
		}

		return self::$form->validated;
	}

	private static function handleForm() {
		ICommCashDeskSell::reset();
		self::$sell->paymode = self::$form->post['paymode'];
		self::$sell->note = self::$form->post['note'];
		self::$sell->cid = self::$form->post['cid'];
		self::$sell->wid = self::$form->post['wid'];
		self::$sell->received = self::$form->post['amountrec'];

		foreach(self::$form->post['index'] as $index) {
			$pid = self::$form->post['product'.$index];
			$item = new ICommCashDeskSellItem($pid, self::$form->post['qty'][$index]);
			self::$sell->addItem($item);
		}
		self::$sell->save();
	}

	public function render() {
		$html = '';

		self::$grid = new ICommCashDeskTabCashierGrid('product-list');
		self::$grid->load();

		$formname = 'sellform';
		$url = ICommCashDeskCore::url(NULL, $_GET);

		$html.= "<form id=\"" . self::$form->name . "\" method=\"post\" action=\"$url\">\n";
		$html .= ICommCashDeskForm::tokenize(self::$form->token);

		$html .= "<div class=\"tab-block left\">\n";
		$html .= self::$grid->render();
		$html .= "</div><!-- content-block -->\n";

		$html .= "<div class=\"tab-block right\">\n";
		$html .= self::showPanel();
		$html .= "</div><!-- content-block -->\n";

		$html .= "<div class=\"tab-block left\">\n";

		$payment = ICommCashDeskPaymentCore::getDefault();
		$content = ICommCashDeskPaymentCore::display($name);
		$html .= ICommCashDeskElements::block(self::$payblock, $payment->title, $content, array('close' => FALSE, 'hidden' => FALSE));
		$html .= "</div><!-- content-block -->\n";

		$html .= "</form>\n";

		return $html;
	}

	private static function showPanel() {
		$html = '';

		$html .= "<div class=\"panel-line\">\n";
		$icon = "<i class=\"icon-off\"></i>\n";
		$options = array('grid' => self::$grid->getName(), 'content' => $icon, 'class' => 'action resetsell', 'callback' => 'resetsell');
		$html .= ICommCashDeskElements::button('resetsell', 'resetsell', 'button', $options);

		$icon = "<i class=\"icon-save\"></i>\n";
		$options = array('content' => $icon, 'class' => 'action suspend', 'callback' => 'suspendsell');
		$html .= ICommCashDeskElements::button('suspend', 'suspend', 'button', $options);
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$html .= ICommCashDeskElements::selectClient('client', 'Client', 1);
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$options = array('id' => 'amountrec', 'maxLength' => 8, 'size' => '5');
		$html .= ICommCashDeskElements::text('amountrec', 'Receive', 0, $options);

		$options = array('id' => 'amountdue', 'maxLength' => 8, 'size' => '5', 'disabled' => 'yes');
		$html .= ICommCashDeskElements::text('amountdue', 'Payback', 0, $options);

		$options = array('id' => 'amountsell', 'maxLength' => 8, 'size' => '5', 'disabled' => 'yes');
		$html .= ICommCashDeskElements::text('amountsell', 'Total', 0, $options);
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";

		foreach(ICommCashDeskPaymentCore::getPayment() as $mode) {
			$options = array('content' => $mode->title, 'mode' => $mode->name, 'class' => 'action icbutton');
			if($mode->callback) {
				$options['target'] = self::$payblock;
				$options['callback'] = $mode->callback;
			}
			$html .= ICommCashDeskElements::button($mode->name, $mode->title, 'button', $options);
		}
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$options = array('content' => 'Invoice');
		$html .= ICommCashDeskElements::button('sell', 'Invoice', 'submit', $options);
		$html .= "</div>\n";

		return $html;
	}
}

class ICommCashDeskTabCashierGrid extends ICommCashDeskGrid {
	public function cells() {
		$options = array(
			'callback' => 'cashline',
			'content' => "<i class=\"icon-plus\"></i>\n",
			'class' => 'action add',
			'grid' => $this->getName(),
		);
		$actions = ICommCashDeskElements::button('newline', 'Add', 'button', $options);
		$options = array(
			'content' => "<i class=\"icon-refresh\"></i>\n",
			'class' => 'action clear',
			'grid' => $this->getName(),
		);
		$actions .= ICommCashDeskElements::button('clearall', 'Clear', 'button', $options);

		return array(
			'product' => array(
				'title' => 'Article',
				'callback' => 'cellProduct',
				'input' => TRUE,
			),
			'qty' => array(
				'title' => 'Qty',
				'callback' => 'cellQty',
				'input' => TRUE,
			),
			'pu' => array(
				'title' => 'PU',
				'callback' => 'cellPu',
			),
			'total' => array(
				'title' => 'Total',
				'callback' => 'cellTotal',
			),
			'action' => array(
				'title' => $actions,
				'callback' => 'cellAction',
			),
		);
	}

	public static function cellProduct($id = NULL, $value = array()) {
		$options = array(
			'switch' => TRUE,
			'cursor' => $id,
		);
$value = 7814;
		return ICommCashDeskElements::selectProduct("product$id", '', $value, $options);
	}

	public static function cellQty($id = NULL, $value = array()) {
		$value = (empty($value) ? 1 : $value);
		$options = array('id' => 'qty', 'maxLength' => 4, 'size' => '2');
		return ICommCashDeskElements::text("qty[$id]", '', $value, $options);
	}

	public static function cellPu($id = NULL, $value = array()) {
		$options = array('id' => 'pu', 'disabled' => 'yes', 'maxLength' => 6, 'size' => '4');
		return ICommCashDeskElements::text("pu[$id]", '', $value, $options);
	}

	public static function cellTotal($id = NULL, $value = array()) {
		$options = array('id' => 'total', 'disabled' => 'yes', 'maxLength' => 6, 'size' => '4');
		return ICommCashDeskElements::text("total[$id]", '', $value, $options);
	}

	public function cellAction($id = NULL, $value = array()) {
		$html = '';

		$icon = "<i class=\"icon-minus\"></i>\n";
		$options = array('grid' => $this->getName(), 'content' => $icon, 'class' => 'action delete', 'cursor' => $id);
		$html .= ICommCashDeskElements::button('delete', 'del', 'button', $options);

		$icon = "<i class=\"icon-euro\"></i>\n";
		$options = array('content' => $icon, 'class' => 'action booking', 'cursor' => $id);
		$html .= ICommCashDeskElements::button("booking", 'book', 'button', $options);

		$icon = "<i class=\"icon-zoom-in\"></i>\n";
		$options = array('callback' => 'info', 'content' => $icon, 'class' => 'action icinfo', 'cursor' => $id);
		$html .= ICommCashDeskElements::button("info", 'info', 'button', $options);

		$icon = "<i class=\"icon-camera\"></i>\n";
		$options = array('callback' => 'photo', 'content' => $icon, 'class' => 'action photo', 'cursor' => $id);
		$html .= ICommCashDeskElements::button("photo", 'photo', 'button', $options);

		return $html;
	}
}

