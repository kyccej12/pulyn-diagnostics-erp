<?php
	ini_set("max_execution_time",-1);
	include("../includes/dbUSE.php");
	
	dbquery("delete from sjpi.acctg_gl where doc_type = 'POS' and doc_date between '2016-01-01' and '2017-12-31';");
	$i = 1;
	list($numrows) = getArray("select count(*) from sjpi.acctg_gl_pos;");
	$a = dbquery("select * from sjpi.acctg_gl_pos order by branch, doc_date desc, doc_no desc;");
	while($b = mysql_fetch_array($a)) {
		dbquery("INSERT IGNORE INTO sjpi.acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,debit,credit,cost_center,doc_remarks,posted_by,posted_on) VALUES ('2','$b[branch]','$b[cy]','$b[doc_no]','$b[doc_date]','POS','0','$b[acct_branch]','$b[debit]','$b[credit]','$b[cost_center]','FROM POS','$b[posted_by]','$b[posted_on]');");
		
		echo "Processing $i of ".number_format($numrows)." Records: Realigning Doc # $b[branch]-$b[doc_no] :: Acct &raquo; $b[acct] :: Debit &raquo $b[debit] :: Credit &raquo; $b[credit]<br/>";  
		$i++;
	}
	

	@mysql_close($con);
?>