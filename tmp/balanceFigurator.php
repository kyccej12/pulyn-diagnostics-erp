<?php
	include("../includes/dbUSE.php");
	
	$a = dbquery("SELECT a.customer, b.doc_no, SUM(amount_paid) AS applied_amount FROM cr_header a LEFT JOIN cr_details b ON a.trans_no = b.trans_no AND a.branch = b.branch WHERE a.status = 'Posted' AND b.ref_type = 'AR-BB' AND doc_no != 0 AND amount_paid >  0 GROUP BY b.doc_no,a.customer ORDER BY b.doc_no");
	while($b = mysql_fetch_array($a)) {
		dbquery("update arbeg_details set balance = balance - $b[applied_amount], applied_amount = applied_amount + $b[applied_amount] where invoice_no = '$b[doc_no]';");
		echo "Update AR Beginning Invoice No. $b[doc_no]<br/>";
	}
	@mysql_close($con);
?>