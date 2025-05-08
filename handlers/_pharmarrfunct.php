<?php
	require_once "_generics.php";
	//ini_set("display_errors","On");
	class myRR extends _init {
		
		function updateHeaderAmt($rr_no,$bid) {
			list($amt) = parent::getArray("select sum(amount) from pharma_rr_details where rr_no = '$rr_no' and branch = '$bid';");
			parent::dbquery("update ignore pharma_rr_header set amount = '$amt' where rr_no = '$rr_no' and branch = '$bid';");
		}
		
		function setHeaderControls($status,$rr_no,$urights) {
			
			$headerControls = '';
			
			if($lock != 'Y') {
				switch($status) {
					case "Finalized":
						if($urights == "admin") {
							$headerControls = "<a href=\"#\" class=\"topClickers\" onclick=\"javascript: reopenPharmaRR();\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status</a>&nbsp;";
						}
						$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:printPharmaRR()\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print Receiving Report</a>&nbsp;";
					break;
					case "Cancelled":
						if($urights == "admin") {
							$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" onclick=\"javascript:reuseRR();\" style=\"padding: 5px;\"><img src=\"images/icons/refresh.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Recycle this Document</a>";	
						}
					break;
					case "Active": default:
						$headerControls = "<a href=\"#\" class=\"topClickers\" onClick=\"javascript: finalizePRR();\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Finalize Receiving Report</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:savePRRHeader();\"><img src=\"images/save.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:downloadPharmaPO();\"><img src=\"images/icons/copy.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Copy From Purchase Order</a>&nbsp;";
						if($urights == "admin" && $dS != 1) {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelRR();\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel this Document</a>&nbsp;";
						}
					break;
				}
			} else {
				$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:printPharmaRR()\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Re-Print Receiving Report</a>&nbsp;";
			}
			echo $headerControls;
		}
		
		function setNavButtons($rr_no) {
			
			$nav = '';
			
			list($fwd) = parent::getArray("select rr_no from pharma_rr_header where rr_no > $rr_no and branch = '1' limit 1;");
			list($prev) = parent::getArray("select rr_no from pharma_rr_header where rr_no < $rr_no and branch = '1' order by rr_no desc limit 1;");
			list($last) = parent::getArray("select rr_no from pharma_rr_header where branch = '1' order by rr_no desc limit 1;");
			list($first) = parent::getArray("select rr_no from pharma_rr_header where branch = '1' order by rr_no asc limit 1;");
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewPharmaRR('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewPharmaRR('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			echo "<a href=# onclick=\"parent.viewPharmaRR('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewRR('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
		}
	}
		
?>