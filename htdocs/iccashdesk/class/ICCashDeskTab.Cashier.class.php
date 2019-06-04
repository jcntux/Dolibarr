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

require_once('ICCashDeskPayment.class.php');
require_once('ICCashDeskSell.class.php');

class ICCashDeskTabCashier {
	public $name;
	public $weight;
	public $caption;

	private static $grid;
	private static $sell;
	private static $form;
	private static $payblock;
	private static $paymode;

	public function __construct() {
		$this->name = 'cashier';
		$this->caption = 'Cashier';
		$this->weight = 1;
		$this->class = __CLASS__;
		self::$payblock = 'block-payment';
		self::$paymode = NULL;
		self::$grid = new StdClass();
		self::$grid->name = 'product-list';
	}

	public function init() {
		ICCashDeskPayment::init();
		self::$paymode = ICCashDeskPayment::load(ICCashDeskPayment::getCurrent()->name);

		ICCashDeskSell::init();
		self::$sell = ICCashDeskSell::load();
		// submit + validation + error
		self::$form = new ICCashDeskForm('sellform', self::getFields());
	}

	public static function hookHeader() {
		return array(
			'js' => array(
				'tabcashier.js',
			),
		);
	}

	public static function getFields() {
		$row = array();
		$head = array(
			'product' => 'Article',
			'qty' => 'Qty',
			'pu' => 'PU',
			'total' => 'Total'
		);

		$options = array('#js' => array('grid-newline'));
		$head['action']['newline'] = ICCashDeskField::action('newline', 'icon-plus', 'add', $options);
		$options = array('#js' => array('reload'));
		$head['action']['reload'] = ICCashDeskField::action('reload', 'icon-refresh', 'reload', $options);

		$options = array(
			'#required' => TRUE,
			'#fullview' => TRUE,
			'#help' => 'Article selection: autocompletion on first 3 chars',
		);
		$row['product'] = ICCashDeskField::product('product', 'getProducts', $options);
		$options = array(
			'#exclude' => array('0'),
			'#required' => TRUE,
			'#attributes' => array('maxLength' => 4, 'size' => 2),
			'#handler' => array('positive'),
			'#help' => 'Qty: positive integer value',
		);
		$row['qty'] = ICCashDeskField::numeric('qty', NULL, 1, $options);
		$options = array(
			'#disabled' => 'yes',
			'#attributes' => array('maxLength' => 4, 'size' => 4),
		);
		$row['pu'] = ICCashDeskField::numeric('pu', NULL, NULL, $options);
		$row['total'] = ICCashDeskField::numeric('total', NULL, NULL, $options);

		$options = array('#js' => 'grid-delline');
		$row['action']['delete'] = ICCashDeskField::action('delete', 'icon-minus', 'delete', $options);

		$options = array('#js' => array('product-booking' => 'booking'));
		$row['action']['booking'] = ICCashDeskField::action('booking', 'icon-euro', 'booking', $options);

		$options = array('#js' => array('product-info' => 'productInfo'));
		$row['action']['info'] = ICCashDeskField::action('info', 'icon-info-sign', 'info', $options);

		$options = array('#js' => array('product-photo' => 'productPhoto'));
		$row['action']['photo'] = ICCashDeskField::action('photo', 'icon-camera', 'photo', $options);

		$fields = array(
			'grid' => array(
				'#type' => 'grid',
				'#name' => self::$grid->name,
				'#settings' => array(
					'head' => $head,
					'items' => $row,
				),
			),
			'cid' => ICCashDeskField::selectClient('cid', 'Client', 1),
			'totalht' => ICCashDeskField::numeric('totalht', NULL, 0, array('#disabled' => TRUE, '#attributes' => array('class' => array('big', 'fullwidth')))),
		);

		return $fields;
	}

	public function render() {
		$html = '';

		$html .= "<div class=\"tab-block block-left\">\n";
		$html .= self::$form->getField('grid')->render();

		$options = array('#class' => array('sticky', self::$payblock));
		$content = self::$paymode->render();
		$html .= ICCashDeskField::display(ICCashDeskField::block(self::$payblock, self::$paymode->title, $content, $options));
		$html .= "</div><!-- content-block -->\n";

		$html .= "<div class=\"tab-block block-right\">\n";
		$html .= self::displayPanel();
		$html .= "</div><!-- content-block -->\n";

		self::$form->setAction();
		self::$form->setMethod('post');
		self::$form->setHtml($html);
		$html = self::$form->render();

		return $html;
	}

	private static function displayPanel() {
		$html = '';

		$html .= "<div class=\"panel-line\">\n";
		$options = array(
			'#attributes' => array('grid' => self::$grid->name),
			'#js' => array('new' => 'newsell'),
		);
		$html .= ICCashDeskField::load(ICCashDeskField::action('new', 'icon-off', NULL, $options))->render();
		$options = array(
			'#attributes' => array('grid' => self::$grid->name),
			'#js' => array('suspend' => 'suspendsell'),
		);
		$html .= ICCashDeskField::load(ICCashDeskField::action('suspend', 'icon-save', NULL, $options))->render();
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$html .= self::$form->getField('cid')->render();
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$html .= self::$form->getField('totalht')->render();
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		
		ICCashDesk::addSettings(
			array(
				'paymode-params' => array(
					'target' => self::$payblock,
					'callback' => 'payblock'
				),
			)
		);
		foreach(ICCashDeskPayment::getPayments() as $mode) {
			$options = array(
				'#title' => $mode->title,
				'#js' => array(
					'paymode' => array(
						'params' => 'paymode-params',
						'mode' => $mode->name,
					),
				),
			);
			$html .= ICCashDeskField::load(ICCashDeskField::action($mode->name, NULL, NULL, $options))->render();
		}
		$html .= "</div>\n";

		$html .= "<div class=\"panel-line\">\n";
		$html .= ICCashDeskField::load(ICCashDeskField::button('sell', 'Invoice', 'invoice', array('#widget' => 'submit')))->render();
		$html .= "</div>\n";

		return $html;
	}

	public static function hookPrepare(&$form) {
		if($class = self::$paymode->class) {
			if(method_exists($class, 'hookPrepare')) {
				$class::hookPrepare($form);
			}
		}
	}

	public static function hookValidate(&$form) {
		if($class = self::$paymode->class) {
			if(method_exists($class, 'hookValidate'))
				$class::hookValidate($form);
		}
	}
}
