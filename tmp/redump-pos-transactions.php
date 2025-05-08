<?php
	//ini_set("display_errors","On");
	ini_set("max_execution_time", -1);
	ini_set("memory_limit", -1);
	include("../includes/dbUSE.php");
	
	function createTID() {
		$numLenth = 32;
		$numSeed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$getNumber = "";
		for($i = 0; $i < $numLenth; $i ++) {
		 $getNumber .= $numSeed[rand(0, strlen($numSeed))];
		}
		return $getNumber;
	}
	
	$a = dbquery("SELECT DISTINCT branch FROM sjpi.pos_header ORDER BY branch;");
	while(list($branch) = mysql_fetch_array($a)) {
		echo ">>>> PROCESSING BRANCH $branch <<<< <br/>";
		$i = 1;
		$b = dbquery("SELECT tmpfileid,branch,trans_id,trans_date,amount,tendered,`status`,created_by,created_on FROM sjpi.pos_header WHERE `status` != 'Cancelled' AND branch = '$branch' ORDER BY trans_date ASC;");
		while($c = mysql_fetch_array($b)) {
			$newTID = createTID();
			dbquery("INSERT IGNORE sjpi.pos_header_new (tmpfileid,company,branch,trans_id,trans_date,amount,tendered,`status`,created_by,created_on) VALUES ('$newTID','2','$branch','$i','$c[trans_date]','$c[amount]','$c[tendered]','$c[status]','$c[created_by]','$c[created_on]');");
			
			$d = dbquery("SELECT item_code, description, sales_group, qty, price, disc_price, amount, uid FROM sjpi.pos_details WHERE tmpfileid = '$c[tmpfileid]';");
			while($e = mysql_fetch_array($d)) {
				dbquery("INSERT IGNORE INTO sjpi.pos_details_new (tmpfileid,trans_id,branch,item_code,description,sales_group,qty,price,disc_price,amount,uid) VALUES ('$newTID','$i','$branch','$e[item_code]','".mysql_real_escape_string($e['description'])."','$e[sales_group]','$e[qty]','$e[price]','$e[disc_price]','$e[amount]','$e[uid]');");	
			}
			echo "Realigning Transaction ID # $c[trans_id] to $i, updating hash from $c[tmpfileid] to $newTID <br/>";
			$i++; $newTID = "";
		}
	}
	
	@mysql_close($con);
?>