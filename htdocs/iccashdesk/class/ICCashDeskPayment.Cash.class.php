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

class ICCashDeskPaymentCash extends ICCashDeskPayment {

	public static function hookInit() {
		$result = new StdClass();

		$result->name = 'cash';
		$result->weight = 1;
		$result->title = 'Cash';

		return $result;
	}

	public function hookRender() {
		$html = '';

		$options = array(
			'#required' => TRUE,
			'#exclude' => array('0'),
			'#attributes' => array('maxLength' => 8, 'size' => '5'),
		);
		$html .= ICCashDeskField::display(ICCashDeskField::numeric('received', 'Received', NULL, $options));

		$options['#disabled'] = 'yes';
		$html .= ICCashDeskField::display(ICCashDeskField::numeric('due', 'Due', 0, $options));

		return $html;
	}

	public function hookValidate(&$form) {
		if(isset($form->post['amountrec']) && $form->post['amountrec'] >= $form->post['total']) {
			return TRUE;

		} else {
			$form->error[] = 'amountrec';
		}

		return FALSE;
	}
}
