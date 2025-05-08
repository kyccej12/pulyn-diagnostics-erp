<?php
	ini_set("max_execution_time",-1);
	include("../includes/dbUSE.php");

	$a = dbquery("SELECT b.record_id, a.j_no, j_date, a.branch, doc_no, b.acct, b.credit FROM sjpi.journal_header a INNER JOIN sjpi.journal_details b ON a.company = b.company AND a.branch = b.branch AND a.j_no = b.j_no WHERE `status` = 'Posted' AND doc_type = 'SRR' AND b.acct = '' ORDER BY a.branch, a.j_no;");

	while($b = mysql_fetch_array($a)) {
		list($ref_type) = getArray("SELECT ref_type FROM sjpi.srr_header WHERE srr_no = '$b[doc_no]' AND branch = '$b[branch]' AND company = '2';");
		
		switch($ref_type){
			case 'FP': 	$cr_acct = '1299'; 	break;
			case 'DR': 	$cr_acct = '5004'; 	break;
			case 'STR': $cr_acct = '5004'; 	break;
			case 'INV': $cr_acct = '5004'; 	break;
			case 'OTH': $cr_acct = '5004'; 	break;
		}
		
		dbquery("update sjpi.journal_details set acct = '$cr_acct' where record_id = '$b[record_id]';");
		dbquery("update sjpi.acctg_gl set acct = '$cr_acct' where doc_no = '$b[j_no]' and branch = '$b[branch]' and doc_type = 'jv' and acct = '' and credit = '$b[credit]';");
		
		echo "Updating Journal Voucher No. $b[branch]-$b[j_no], setting Credit Account to $cr_acct <br/>";
	
	}
	@mysql_close($con);
	
?>