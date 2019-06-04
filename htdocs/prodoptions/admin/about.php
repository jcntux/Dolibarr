<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		prodoptions/admin/about.php
 * 	\ingroup	prodoptions
 * 	\brief		This file is an about page for module prodoptions
 * 				
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/prodoptions.lib.php";

dol_include_once('/prodoptions/lib/PHP Markdown 1.0.1o/markdown.php');


//require_once "../class/myclass.class.php";
// Translations
$langs->load("prodoptions@prodoptions");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

/*
 * View
 */
$page_name = "ProdOptionsAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = prodoptionsAdminPrepareHead();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("Module550Name"),
	0,
	'product'
);

// About page goes here
echo $langs->trans("ProdOptionsAboutPage");

echo '<br>';

$buffer = file_get_contents(dol_buildpath('/prodoptions/README.md', 0));
echo Markdown($buffer);

echo '<br>',
'<a href="' . dol_buildpath('/prodoptions/COPYING', 1) . '">',
'<img src="' . dol_buildpath('/prodoptions/img/gplv3.png', 1) . '"/>',
'</a>';

llxFooter();

$db->close();
