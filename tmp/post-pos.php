<?php
	session_start();
	include("includes/dbUSE.php");
	ini_set("max_execution_time",0);

	$dtf = formatDate($_GET['dtf']); $dt2 = formatDate($_GET['dt2']);
	dbquery("delete from acctg_gl where doc_type = 'POS' and doc_date between '$dtf' and '$dt2' and company = '$_SESSION[company]';");
	$a = dbquery("SELECT a.tmpfileid, a.company, a.branch, trans_id AS invoice_no, trans_date AS invoice_date, DATE_FORMAT(trans_date,'%Y') AS cy, 0 AS customer, 0 AS terms, amount, 'Y' AS vatable, 'FROM POS' AS remarks, a.created_on AS updated_on, a.created_by AS updated_by FROM pos_header a  WHERE a.status = 'Finalized' AND a.trans_date BETWEEN '$dtf' AND '$dt2';");
	while($row = mysql_fetch_array($a)) {
		$amtGT = 0;
		/* REVENUE */
		$b = dbquery("SELECT sales_group AS rev_acct, ROUND(SUM(amount),2) AS amount, ROUND(SUM(amount) / 1.12 * 0.12,2) AS vat FROM pos_details WHERE item_code != '--' and  tmpfileid = '$row[tmpfileid]' GROUP BY sales_group;");
		while($c = mysql_fetch_array($b)) {
			$net = $c['amount'] - $c['vat']; $vatT+=$c['vat']; $amtGT+=$c['amount'];
			if($net > 0) {
				dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','$c[rev_acct]','$net','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");
			}
		}
	
		if($row['vatable'] == "Y" && $vatT > 0) {
			dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','2202','$vatT','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");
		}
		
		/* SALES DISCOUNT */
		list($discount) = getArray("SELECT IFNULL(ABS(SUM(amount)),0) AS disc FROM pos_details WHERE tmpfileid = '$row[tmpfileid]' AND item_code = '--';");
		if($discount > 0) {
			dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','4013','$discount','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");
		}

		/* DEBIT ACCOUNT */
		if($row['terms'] != 0) { $dAcct = "1101"; } else { $dAcct = "1001"; }
		dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','$dAcct','".ROUND($amtGT-$discount,2)."','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");

		/* COGS & INVENTORY */
		$d = dbquery("SELECT b.cogs_acct, b.asset_acct, CAST(SUM(unit_cost*qty)/1.12 AS DECIMAL(9,2)) AS cost FROM pos_details a INNER JOIN products_master b ON a.item_code = b.item_code WHERE a.item_code != '--' AND a.tmpfileid = '$row[tmpfileid]' AND b.company='$row[company]' GROUP BY a.item_code;");
		while($e = mysql_fetch_array($d)) {
			if($e['cost'] > 0) {
				dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','$e[cogs_acct]','$e[cost]','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");
			    dbquery("insert ignore into acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$row[company]','$row[branch]','$row[cy]','$row[invoice_no]','$row[invoice_date]','POS','$row[customer]','$row[branch]','$e[asset_acct]','$e[cost]','".mysql_real_escape_string($row['remarks'])."','$row[updated_by]','$row[updated_on]');");
			}
		}
		
		echo "POSTING POS TRANSACTION NO. => $row[invoice_no]<br/>";
		$net = 0; $vatT = 0;
	}

	mysql_close($con);
?>