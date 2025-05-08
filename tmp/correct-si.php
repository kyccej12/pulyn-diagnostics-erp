<?php
	ini_set("max_execution_time",-1);
	include("../includes/dbUSE.php");
	//ini_set("display_errors","On");
	
	list($numrows) = getArray("select count(*) from sjpi.opensi;");
	$q = dbquery("SELECT doc_no, branch FROM sjpi.opensi order by branch, doc_no;");
	while($r = mysql_fetch_array($q)) {
		/* POST TO GENERAL LEDGER */
		
		$yata = dbquery("select a.company,a.branch,invoice_no, invoice_date, date_format(invoice_date,'%Y') as cy, customer, a.terms, amount, b.vatable, a.remarks, a.updated_by, a.updated_on, a.discount from sjpi.invoice_header a left join contact_info b on a.customer=b.file_id where a.invoice_no = '$r[doc_no]' and a.branch = '$r[branch]' and a.company = '2';");
		while($rowbot = mysql_fetch_array($yata)) {
			
			/* REVENUE */
			$bo = dbquery("select if(sales_group='','1299',sales_group) as rev_acct, CAST(sum(amount) as DECIMAL(18,2)) as amount, if(sales_group!='',if('$rowbot[vatable]' = 'Y',CAST((SUM(amount)/1.12) * 0.12 AS DECIMAL(18,2)),0),0) as vat from sjpi.invoice_details where invoice_no = '$rowbot[invoice_no]' and branch = '$rowbot[branch]' and company = '$rowbot[company]' group by sales_group;"); 
			while($co = mysql_fetch_array($bo)) {
				$neto = $co['amount'] - $co['vat']; $vatT+=$co['vat'];
				if($neto > 0) {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$co[rev_acct]','$neto','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				} else {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$co[rev_acct]','".abs($neto)."','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				}
			}
		
			if($vatT != 0) {
				switch($_SESSION['company']) { case "1": $vAcct = '2204'; break; default: $vAcct = '2202'; break; }
				if($vatT > 0) {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$vAcct','$vatT','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				} else {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$vAcct','".abs($vatT)."','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				}
			}

			/* DEBIT ACCOUNT */
			if($rowbot['terms'] != 0) { $dAcct = "1101"; } else { $dAcct = "1001"; }
			if($rowbot['discount'] > 0) {
				$discount = ROUND(($rowbot['amount'] * $rowbot['discount']) / 100,2);
				$net = $rowbot['amount'] - $discount;
				dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$dAcct','$net','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','4013','$discount','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");	
			} else {
				if($rowbot['amount'] > 0) {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$dAcct','$rowbot[amount]','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				} else {
					dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$dAcct','".abs($rowbot['amount'])."','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
				}
			}
			
			/* INSERT COGS */
			$d = dbquery("SELECT if(b.cogs_acct='','1299',b.cogs_acct) as cogs_acct,SUM(ROUND(((qty*b.unit_cost) / 1.12),2)) AS cost from sjpi.invoice_details a LEFT JOIN products_master b ON a.item_code = b.item_code AND a.company=b.company WHERE a.invoice_no = '$rowbot[invoice_no]' AND a.branch = '$rowbot[branch]' AND a.company = '$rowbot[company]' and a.sales_group != '' GROUP BY b.cogs_acct;");
			while($e = mysql_fetch_array($d)) {
				dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$e[cogs_acct]','$e[cost]','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
			}
			
			$f = dbquery("SELECT if(b.asset_acct='','1299',b.asset_acct) as asset_acct,SUM(ROUND(((qty*b.unit_cost) / 1.12),2)) AS cost from sjpi.invoice_details a LEFT JOIN products_master b ON a.item_code = b.item_code AND a.company=b.company WHERE a.invoice_no = '$rowbot[invoice_no]' AND a.branch = '$rowbot[branch]' AND a.company = '$rowbot[company]' and a.sales_group != '' GROUP BY b.asset_acct;");
			while($g = mysql_fetch_array($f)) {
				dbquery("insert ignore into sjpi.acctg_gl_si (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,doc_remarks,posted_by,posted_on) values ('$rowbot[company]','$rowbot[branch]','$rowbot[cy]','$rowbot[invoice_no]','$rowbot[invoice_date]','SI','$rowbot[customer]','$rowbot[branch]','$g[asset_acct]','$g[cost]','REALIGNED SI','$rowbot[updated_by]','$rowbot[updated_on]');");
			}
			
			$net = 0; $vatT = 0; $discount = 0;
			
		
		}
		
		
		echo "Processing $i of ".number_format($numrows)." Records: Realigning Sales Invoice # $r[branch]-$r[doc_no]<br/>";  
		$i++;
	}
	
	@mysql_close($con);
?>