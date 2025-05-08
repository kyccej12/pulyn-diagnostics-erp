<?php
	include("../includes/dbUSE.php");


	$a = dbquery("select branch, trans_no from cr_header where `status` = 'Posted';");
	while(list($branch,$trans_no) = mysql_fetch_array($a)) {
		echo "select discount, net, ewt from cr_header where trans_no = '$trans_no' and branch = '$branch';<br/>";
		list($disc,$net,$ewt) = getArray("select discount, net, ewt from cr_header where trans_no = '$trans_no' and branch = '$branch';");
		dbquery("insert ignore into acctg_gl (branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,credit,cost_center,doc_remarks,posted_by,posted_on) select a.branch, date_format(a.cr_date,'%Y') as cy, a.trans_no as doc_no, a.cr_date as doc_date, 'CR' as doc_type, a.customer as contact_id,'$branch' as acct_branch,'1001' as acct, '0$net' as debit, '0' as credit, '' as cost_center,a.remarks as doc_remarks,'$_SESSION[userid]',now() from cr_header a where a.trans_no = '$trans_no' and a.branch = '$branch' and `status` = 'Posted' group by a.trans_no;");
		if($disc > 0) { 
			dbquery("insert ignore into acctg_gl (branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,credit,cost_center,doc_remarks,posted_by,posted_on) select a.branch, date_format(a.cr_date,'%Y') as cy, a.trans_no as doc_no, a.cr_date as doc_date, 'CR' as doc_type, a.customer as contact_id,'$branch' as acct_branch,'8003' as acct, '0$disc' as debit, '0' as credit, '' as cost_center,a.remarks as doc_remarks,'$_SESSION[userid]',now() from cr_header a where a.trans_no = '$trans_no' and a.branch = '$branch' and `status` = 'Posted' group by a.trans_no;");
		}
		
		if($ewt > 0) { 
			dbquery("insert ignore into acctg_gl (branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,credit,cost_center,doc_remarks,posted_by,posted_on) select a.branch, date_format(a.cr_date,'%Y') as cy, a.trans_no as doc_no, a.cr_date as doc_date, 'CR' as doc_type, a.customer as contact_id,'$branch' as acct_branch,'1402' as acct, '0$ewt' as debit, '0' as credit, '' as cost_center,a.remarks as doc_remarks,'$_SESSION[userid]',now() from cr_header a where a.trans_no = '$trans_no' and a.branch = '$branch' and `status` = 'Posted' group by a.trans_no;");
		}
		
		dbquery("insert ignore into acctg_gl (branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,credit,cost_center,doc_remarks,posted_by,posted_on) select a.branch, date_format(a.cr_date,'%Y') as cy, a.trans_no as doc_no, a.cr_date as doc_date, 'CR' as doc_type, a.customer as contact_id,'$branch' as acct_branch,if(b.ref_type = 'CR','2021','1101') as acct, 0 as debit, sum(b.amount_paid) as credit, '' as cost_center,a.remarks as doc_remarks,'$_SESSION[userid]',now() from cr_header a left join cr_details b on a.trans_no = b.trans_no and a.branch = b.branch where a.trans_no = '$trans_no' and a.branch = '$branch' and `status` = 'Posted' group by a.trans_no, b.ref_type;");
	
		echo "REPOSTING CR NO. $branch - $trans_no<br/>";
	}
?>