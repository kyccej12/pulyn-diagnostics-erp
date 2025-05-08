<?php
	//ini_set("display_errors","On");
	ini_set("max_execution_time", -1);
	include("../includes/dbUSE.php");
	
	function createTID() {
		$numLenth = 25;
		$numSeed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$getNumber = "";
		for($i = 0; $i < $numLenth; $i ++) {
		 $getNumber .= $numSeed[rand(0, strlen($numSeed))];
		}
		return $getNumber;
	}
	
	$a = dbquery("SELECT * FROM (SELECT tmpfileid,COUNT(tmpfileid) AS xcount FROM sjpi.pos_header WHERE `status` = 'Finalized' GROUP BY tmpfileid,branch) a WHERE xcount > 1;");
	while(list($tmpid) = mysql_fetch_array($a)) {
		$b = dbquery("SELECT trans_id FROM sjpi.pos_header WHERE tmpfileid = '$tmpid';");
		while(list($tid) = mysql_fetch_array($b)) {
			list($c) = getArray("SELECT COUNT(*) FROM sjpi.pos_details WHERE trans_id = '$tid' AND tmpfileid = '$tmpid';");
			if($c == 0) {
				$newTID = createTID();
				dbquery("update sjpi.pos_header set `status` = 'Cancelled', tmpfileid='$newTID' where tmpfileid = '$tmpid' and trans_id = '$tid';");
				echo "Updating POS Transaction ID # $tid (TMPFILED => $tmpid), TMPFILED ID is replaced with $newTID<br/>"; 
				$newTID = "";
			}
		}
	}
	
	@mysql_close($con);
?>