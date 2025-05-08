<?php
	ini_set("display_error","On");
	ini_set("max_execution_time",-1);
	include("../includes/dbUSE.php");
	$xdtf = "2017-01-01";
	$dbase = "ssjfc";
	for($i = 0; $i <= 11; $i++) {
		list($dtf,$dt2,$month,$year) = getArray("SELECT DATE_ADD('$xdtf',INTERVAL $i MONTH), LAST_DAY(DATE_ADD('$xdtf',INTERVAL $i MONTH)),DATE_FORMAT(DATE_ADD('$xdtf',INTERVAL $i MONTH),'%m'), DATE_FORMAT(DATE_ADD('$xdtf',INTERVAL $i MONTH),'%Y');");
		$string = "SELECT a.branch, 'SI' AS `type`, item_code AS `code`, ROUND(SUM(qty),2) AS sold, 0 AS `in`, 0 AS `out`, ROUND(SUM(qty*cost),2) AS amount FROM $dbase.invoice_header a INNER JOIN $dbase.invoice_details b ON a.invoice_no = b.invoice_no AND a.branch = b.branch AND a.company = b.company WHERE a.status = 'Finalized' AND a.invoice_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code UNION ALL SELECT a.branch, 'RR' AS `type`, item_code AS `code`, 0 AS sold, ROUND(SUM(qty),2) AS `in`, 0 AS `out`, ROUND(SUM(qty*cost),2) AS amount FROM $dbase.rr_header a INNER JOIN $dbase.rr_details b ON a.rr_no = b.rr_no AND a.branch = b.branch AND a.company = b.company WHERE a.status = 'Finalized' AND a.rr_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code UNION ALL SELECT a.branch, 'STR' AS `type`, item_code AS `code`, 0 AS sold, 0 AS `in`, ROUND(SUM(qty),2) AS `out`, ROUND(SUM(qty*cost),2) AS amount FROM $dbase.str_header a INNER JOIN $dbase.str_details b ON a.str_no = b.str_no AND a.branch = b.branch AND a.company = b.company WHERE a.status = 'Finalized' AND a.str_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code UNION ALL SELECT a.branch, 'SRR' AS `type`, item_code AS `code`, 0 AS sold, ROUND(SUM(qty),2) AS `in`, 0 AS `out`, ROUND(SUM(qty*cost),2) AS amount FROM $dbase.srr_header a INNER JOIN $dbase.srr_details b ON a.srr_no = b.srr_no AND a.branch = b.branch AND a.company = b.company WHERE a.status = 'Finalized' AND a.srr_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code UNION ALL SELECT a.branch, 'SW' AS `type`, item_code AS `code`, 0 AS sold, 0 AS `in`, ROUND(SUM(qty),2) AS `out`, ROUND(SUM(qty*cost),2) AS amount FROM $dbase.sw_header a INNER JOIN $dbase.sw_details b ON a.sw_no = b.sw_no AND a.branch = b.branch AND a.company = b.company WHERE a.status = 'Finalized' AND a.sw_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code UNION ALL SELECT a.branch, 'POS' AS `type`, item_code AS `code`, ROUND(SUM(qty),2) AS sold, 0 AS `in`, 0 AS `out`, ROUND(SUM(qty*price),2) AS amount FROM $dbase.pos_header a INNER JOIN $dbase.pos_details b ON a.tmpfileid = b.tmpfileid WHERE a.status = 'Finalized' AND a.trans_date BETWEEN '$dtf' AND '$dt2' GROUP BY a.branch, b.item_code";
		//echo $string ."<br/><br/>";
		$itapok = dbquery($string);
		while($gitapok = mysql_fetch_array($itapok)) {
			dbquery("insert ignore into $dbase.ijournal (branch,`month`,`year`,`code`,`type`,`sold`,`inbound`,`outbound`,`amount`) values ('$gitapok[branch]','$month','$year','$gitapok[code]','$gitapok[type]','$gitapok[sold]','$gitapok[in]','$gitapok[out]','$gitapok[amount]');");
			echo "Creating Inventory Journal for $month-$year for $gitapok[type] $raquo; $gitapok[code] :: Amount &raquo; $gitapok[amount]<br/>";
		}
	}
	@mysql_close($con);
?>