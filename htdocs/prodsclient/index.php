<?php
/* Copyright (C) 2012-2013 Philippe Berthet  <berthet@systune.be>
 * Version V1.1
 * Licensed under the GNU GPL v3 or higher 
 */

/**
 *	\file       htdocs/prodsclient/index.php
 *      \ingroup    prodsclient
 *	\brief      Add a tab on customer view to list all products/services baught by this customer
 */

require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

$langs->load("prodsclient@prodsclient");
// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Sort & Order fields
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='datePrint';
$limit = $conf->liste_limit;

// Search fields
$sref=GETPOST("sref");
$sprod_fulldescr=GETPOST("sprod_fulldescr");
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

// Clean up on purge search criteria ?
if (GETPOST("button_removefilter"))
{
    $sref='';
    $sprod_fulldescr='';
    $year='';
    $month='';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");

$titre = $langs->trans("prodsClientsHeader",$object->name);
llxHeader('',$titre,'http://www.onelog.be/dolibarr/prodsClient?lang='.$langs->defaultlang);
    
try {    
    if (empty($socid)) throw new Exception('This page cannot be called without a socid');
    $head = societe_prepare_head($object);
    dol_fiche_head($head, 'prodsclient', $langs->trans("ThirdParty"),0,'company');

    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$socid.'">';
    
    print '<table class="border" width="100%">';
    print '<tr><td width="20%">'.$langs->trans('ThirdPartyName').'</td>';
    print '<td colspan="3">';
    $form = new Form($db);
    
    print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom');
    print '</td></tr>';

    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

    if ($object->client)
    {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
        $sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
        $resql=$db->query($sql);
        if (!$resql)
            throw new Exception('Ínternal error : '. $db->lasterror);
        $obj = $db->fetch_object($resql);
        $nbFactsClient = $obj->nb;
        $thirdTypeArray['customer']=$langs->trans("customer");
    }

    if ($object->fournisseur)
    {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
        $sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."commande_fournisseur where fk_soc = ".$socid;
        $resql=$db->query($sql);
        if (!$resql)
            throw new Exception('Ínternal error : '. $db->lasterror);
        $obj = $db->fetch_object($resql);
        $nbCmdsFourn = $obj->nb;
        $thirdTypeArray['supplier']=$langs->trans("supplier");
    }
    print '</table><br/>';
    
    $productstatic=New Product($db);

    // Guess if we have to show customer or supplier products
    if($thirdTypeSelect == 'supplier') {
        $show_customer = false;
    } elseif ($thirdTypeSelect == 'customer') {
        $show_customer = true;
    } elseif ($nbCmdsFourn > $nbFactsClient) { //not comming from a customer/supplier selection
        $show_customer = false; // This third party have more orders than invoices, assume it's a supplier
    } else {
        $show_customer = true; // Else assume it's a customer.
    }
    
    if($show_customer) { // Customer : show products from invoices
        $documentstatic=new Facture($db);
        $sql_select = 'SELECT f.rowid as doc_id, f.facnumber as doc_number, f.type as doc_type, f.datef as datePrint, ';
        $tables_from = MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as d";
        $where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
        $where.= " AND d.fk_facture = f.rowid";
        $where.= " AND f.entity = ".$conf->entity;
        $datePrint = 'f.datef';
        $doc_number='f.facnumber';
        $thirdTypeSelect='customer';
        $docType = $langs->trans('invoices');
    } else { // Supplier : Show products from orders.
        $documentstatic=new CommandeFournisseur($db);
        $sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, "1" as doc_type, c.date_creation as datePrint, ';
        $tables_from = MAIN_DB_PREFIX."commande_fournisseur as c,".MAIN_DB_PREFIX."commande_fournisseurdet as d";
        $where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
        $where.= " AND d.fk_commande = c.rowid";
        $datePrint = 'c.date_creation';
        $doc_number='c.ref';
        $thirdTypeSelect='supplier';
        $docType = $langs->trans('orders');
    }
    $sql = $sql_select;
    $sql.= ' d.fk_product as product_id, d.description as prod_descr, ';
    $sql.= ' d.qty as prod_qty, p.rowid as prod_id, p.fk_product_type as prod_type,';
    $sql.= " s.rowid as socid, p.ref as prod_ref, p.label as prod_label, concat_ws(' ',p.ref,p.label,d.description) as prod_fulldescr";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".$tables_from;
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid ';
    $sql.= $where;
    if ($month > 0) {
        if ($year > 0) { 
            $start = sprintf('%4d-%02d-01',$year,$month);
            $sql.= " AND ".$datePrint." BETWEEN '".$start."' AND date_add('".$start."',INTERVAL 1 MONTH)";
        } else {
            $sql.= " AND date_format(".$datePrint.", '%m') = '".sprintf('%02d',$month)."'";
        }
    } else if ($year > 0) {
        $sql.= " AND date_format(".$datePrint.", '%Y') = '".sprintf('%04d',$year)."'";
    }
    if ($sref)     $sql.= " AND ".$doc_number." LIKE '%".$sref."%'";
    if ($sprod_fulldescr) $sql.= " AND concat_ws(' ',p.ref,p.label,d.description) LIKE '%".$sprod_fulldescr."%'";
    $sql.= $db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit + 1, $offset);

    if(count($thirdTypeArray)>1) { // Third is customer AND supplier, let user choose what to show.
        $thirdSelect = $form->selectarray("third_select_id",$thirdTypeArray, 
                    $thirdTypeSelect/*sel key*/, 
                    0/*show_empty*/, 
                    0/*key_in_label*/, 
                    0/*value_as_key*/, 
                    ""/*option*/,
                    1/*translate*/,
                    0 /*maxlen*/,
                    0 /*disable*/);
        $button = '<input type="image" name="button_third" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    } else { // Only customer OR supplier : No choice, just a title.
        $thirdSelect = reset($thirdTypeArray); // Get first value of assoc array
        $button = '';
    }
    $param="&amp;sref=".$sref."&amp;month=".$month."&amp;year=".$year."&amp;sprod_fulldescr=".$sprod_fulldescr."&amp;socid=".$socid;
    print_barre_liste($langs->trans('prodsClientTitle', $docType, $thirdSelect, $object->name, $button), $page, "index.php", $param, $sortfield, $sortorder,'',$num); 
    
    $resql=$db->query($sql);
    if (!$resql)
        throw new Exception('Ínternal error : '. $db->lasterror);
    print '<table class="liste" width="100%">'."\n";
    // Titles with sort buttons
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'datePrint','',$param,'align="center" width="150"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Product'),$_SERVER['PHP_SELF'],'prod_fulldescr','',$param,'align="left"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Quantity'),$_SERVER['PHP_SELF'],'prod_qty','',$param,'align="right"',$sortfield,$sortorder);
    // Filters
    print '<tr class="liste_titre">';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
    print '</td>';
    print '<td class="liste_titre">'; // date
    $formother = new FormOther($db);
    print $formother->select_month($month?$month:-1,'month',1);
    $formother->select_year($year?$year:-1,'year',1, 20, 1);
    print '</td>';
    print '<td class="liste_titre" align="left">';
    print '<input class="flat" type="text" name="sprod_fulldescr" size="15" value="'.$sprod_fulldescr.'">';
    print '</td>';
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("resetFilters")).'" title="'.dol_escape_htmltag($langs->trans("resetFilters")).'">';
    print '</td>';
    print '</tr>';

    $var=true;
    $num = $db->num_rows($resql);
    $i = 0;
    while (($objp = $db->fetch_object($resql)) && $i < $conf->liste_limit )
    {
        $var=!$var;
        print "<tr $bc[$var]>";
        print '<td class="nobordernopadding" nowrap="nowrap" width="100">';
        $documentstatic->id=$objp->doc_id;
        $documentstatic->ref=$objp->doc_number;
        $documentstatic->type=$objp->type;
        print $documentstatic->getNomUrl(1);
        print '</td>';
        if ($objp->datePrint > 0) { // Seems that sometimes there is no date on bills...
            print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->datePrint),'day').'</td>';
        } else {
            print '<td align="right"><b>!!!</b></td>';
        }
        $prodreftxt='';
        if(!empty($objp->prod_id)) {
            $productstatic->id = $objp->prod_id;
            $productstatic->ref = $objp->prod_ref;
            $productstatic->status = $objp->prod_type;
            $prodreftxt = $productstatic->getNomUrl(0);
            if(!empty($objp->prod_label)) $prodreftxt .= ' - '.$objp->prod_label;
        }
        if(!empty($objp->prod_descr)) {
            if(!empty($prodreftxt)) {
                $prodreftxt .= '<br/>'.$objp->prod_descr;
            } else {
                $prodreftxt .= $objp->prod_descr;
            }
        }
        print '<td align="left">'.$prodreftxt.'</td>';
        print '<td align="right">'.$objp->prod_qty.'</td>';
        print "</tr>\n";
        $i++;
    }
    if ($num > $conf->liste_limit) {
        print_barre_liste('', $page, "index.php", $param, $sortfield, $sortorder,'',$num);
    }
    $db->free($resql);
    print "</table>";
    print "</form>";
} catch (Exception $e) {
        $error=$langs->trans($e->getMessage());
}


/*
 * Errors
 */

dol_htmloutput_errors($warning);
dol_htmloutput_errors($error,$errors);

llxFooter();

//

?>
