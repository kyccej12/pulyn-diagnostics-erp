<?php
	ini_set("max_execution_time",-1);
	ini_set("memory_limit",-1);
	include("../includes/dbUSE.php");
	$xdtf = "2017-01-01";
	
	for($i = 10; $i <= 11; $i++) {
		list($dtf,$dt2,$month,$year) = getArray("SELECT DATE_ADD('$xdtf',INTERVAL $i MONTH), LAST_DAY(DATE_ADD('$xdtf',INTERVAL $i MONTH)),DATE_FORMAT(DATE_ADD('$xdtf',INTERVAL $i MONTH),'%m'), DATE_FORMAT(DATE_ADD('$xdtf',INTERVAL $i MONTH),'%Y');");
			
		$itapok = dbquery("SELECT branch, acct, ROUND(SUM(debit-credit),2) AS amt, cost_center FROM sjpi.acctg_gl WHERE doc_date BETWEEN '$dtf' AND '$dt2' GROUP BY branch, acct, cost_center order by branch, acct, cost_center;");
		while($gitapok = mysql_fetch_array($itapok)) {
			if($gitapok[amt] > 0) { $db = $gitapok[amt]; $cr = 0; } else { $db = 0; $cr = abs($gitapok[amt]); }
			dbquery("insert ignore into sjpi.acctg_mo_tbalance (branch,`month`,`year`,`acct`,`debit`,`credit`,cost_center) values ('$gitapok[branch]','$month','$year','$gitapok[acct]','$db','$cr','$gitapok[cost_center]');");
			$db = 0; $cr = 0;
		}
	
		echo "Creating Summary Trial Balance for $month-$year<br/>";
		dbquery("insert into sjpi.closingtime (`month`,`year`,`closing_memo`,closed_by,closed_on) values ('$month','$year','AUTO-POSTED BY SYSTEM','1',now());");
	}
	@mysql_close($con);
?>