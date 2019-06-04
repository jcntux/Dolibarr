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

class ICCashDeskCallback {
	private static $callbacks;
	protected static $tab;

	public static function init() {
		self::$callbacks = array();
		self::$tab = NULL;

		foreach(ICCashDesk::getClasses('ICCashDeskCallback') as $class) {
			$cb =  new $class();
			if(!$cb->disabled) self::$callbacks[$cb->name] = $cb;
		}
	}

	public function handle() {
		if($callback = GETPOST('callback', 'alpha', 1)) {
			$class = __CLASS__;
			$callback = strtolower($callback);

			if(self::$tab = ICCashDeskTab::getCurrent())
				$class = self::$callbacks[self::$tab->name]->class;

			if(method_exists($class, $callback))
				$class::$callback();

			else
				print '';

			exit(0);
		}
	}

	protected static function getProducts() {
		$string = '';

		if($name = GETPOST('name', '', 1)) {
			$string = GETPOST($name, '', 1);
		}

		if(!empty($string)) {
			$products = self::fetchProduct($string);

		} else {
			$products = array();
		}

		header("Content-Type: application/json; charset=utf-8");
		print json_encode($products);
	}

	protected static function productPhoto() {
		global $conf;

		$photo = '';

		require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		if($pid = GETPOST('pid', 'int', 1)) {
			$product = new Product(ICCashDesk::$db);
			$product->fetch($pid);

			if($product->is_photo_available($conf->product->multidir_output[$product->entity])) {
				$photo = $product->show_photos($conf->product->multidir_output[$product->entity], 1, 0, 0, 0, 0, 120, 120);
			}
		}
		print $photo;
	}

	protected static function productInfo() {
		global $langs;

		$info =  new StdClass();
		$info->product = new StdClass();
		$info->output = '';

		if($pid = GETPOST('pid', 'int', 1)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

			$langs->load('product');

			$product = new Product(ICCashDesk::$db);
			$product->fetch($pid);
			$html .= ICCashDeskField::display(ICCashDeskField::item('ref', $langs->trans('Reference'), $product->ref));
			$html .= ICCashDeskField::display(ICCashDeskField::item('description', $langs->trans('Description'), $product->description));
			$html .= ICCashDeskField::display(ICCashDeskField::item('barcode', $langs->trans('BarCode'), $product->barcode, array('#default' => 'NA')));
			$html .= ICCashDeskField::display(ICCashDeskField::item('price', $langs->trans('Price'), price($product->price)));
			$html .= ICCashDeskField::display(ICCashDeskField::item('stock_reel', $langs->trans('Stock'), $product->stock_reel));
			$html .= ICCashDeskField::display(ICCashDeskField::item('note', $langs->trans('Note'), $product->note));
			$form = new ICCashDeskForm('info');
			$form->setHtml($html);
			$info->output = $form->render();
		}
		print $info->output;
	}

	protected static function getProduct() {
		$product = new StdClass();

		if($pid = GETPOST('pid', 'int', 1)) {
			require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

			$p = new Product(ICCashDesk::$db);
			$p->fetch($pid);
			$product->id = $pid;
			$product->ref = $p->ref;
			$product->label = $p->label;
			$product->pu = price($p->price);
			$product->stock = $p->stock_reel;
		}

		header("Content-Type: application/json; charset=utf-8");
		print json_encode($product);
	}

	protected static function fetchProduct($string = NULL) {
		$products = array();

		if(empty($string)) {
			return $products;
		}

		$string = ICCashDesk::$db->escape($string);
		$where = array();
		$where[] = "ref LIKE '%$string%'";
		$where[] = "label LIKE '%$string%'";
		$where[] = "ref_ext LIKE '%$string%'";
		$where[] = "description LIKE '%$string%'";
		$where[] = "barcode LIKE '%$string%'";

		$query = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product WHERE " . implode('OR ', $where);
		$result = ICCashDesk::$db->query($query);
		if($result && ICCashDesk::$db->num_rows($result) > 0) {
			while($product = ICCashDesk::$db->fetch_object($result)) {
				$product->key = $product->rowid;
				$product->value = $product->ref;
				$product->label = $product->ref . ' - ' . $product->label;

				$products[] = $product;
			}
			ICCashDesk::$db->free();
		}

		return $products;
	}
}
