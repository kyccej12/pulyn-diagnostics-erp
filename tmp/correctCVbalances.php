<?php
	include("../includes/dbUSE.php");

	$a = dbquery("SELECT a.cv_no, a.cv_date, b.ref_no, b.ref_type, b.acct,b.debit FROM cv_header a LEFT JOIN cv_details b ON a.cv_no = b.cv_no WHERE a.status = 'Posted' AND b.ref_type = 'AP' ORDER BY a.cv_no, ref_no ASC;");
	while($b = mysql_fetch_array($a)) {
		dbquery("update apv_header set applied_amount = applied_amount + 0$b[debit], balance = balance - 0$debit where apv_no = '$b[ref_no]';");

		echo "update apv_header set applied_amount = applied_amount + 0$b[debit], balance = balance - 0$b[debit] where apv_no = '$b[ref_no]';<br/>";
	}
	@mysql_close($con);
?>