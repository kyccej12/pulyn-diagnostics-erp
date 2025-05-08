<?php
	
	function createTID() {
		$numLenth = 25;
		$numSeed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$getNumber = "";
		for($i = 0; $i < $numLenth; $i ++) {
		 $getNumber .= $numSeed[rand(0, strlen($numSeed))];
		}
		return $getNumber;
	}
	
	echo createTID();

?>