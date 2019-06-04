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

class ICCashDeskPaymentReport extends ICCashDeskPayment {

	protected static function hookInit() {
		$result = new StdClass();

		$result->name = 'report';
		$result->weight = 10;
		$result->title = 'Report';

		return $result;
	}

	protected function hookRender() {
		$html = '';

		$html .= ICCashDeskField::display($this->getFields('report'));

		return $html;
	}

	public function getFields($name = NULL) {
		$options = array(
			'#settings' => array(
				'items' => array(
					'reportdate' => ICCashDeskField::selectDate('reportdate', 'Date', NULL, array('#required' => TRUE)),
					'reporttotal' => ICCashDeskField::numeric('reporttotal', 'Montant', NULL, array('#required' => TRUE)),
				),
			),
		);
		$fields = array(
			'report' => ICCashDeskField::multiple('report', 'Echeance', 'addReportLine', $options),
		);

		if($name)
			return ($fields[$name] ? $fields[$name] : NULL);

		return $fields;
	}

	public function hookPrepare(&$form) {
		foreach(self::getFields() as $item) {
			$field = ICCashDeskField::load($item);
			if($form->submit)
				$field->post = $form->post;

			$field->store();
		}
	}

	public function hookValidate(&$form) {
/*
		if(isset($form->post['amountrec']) && $form->post['amountrec'] >= $form->post['total']) {
			return TRUE;

		} else {
			$form->error[] = 'amountrec';
		}
*/
		return FALSE;
	}
}
