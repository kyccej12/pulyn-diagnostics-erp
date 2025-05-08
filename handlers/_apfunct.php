<?php
	
	require_once "_generics.php";


	class myAP extends _init {
		
		function updateHeadAmount($apv_no) {
			list($ewt) = parent::getArray("select sum(credit-debit) from apv_details where apv_no = '$apv_no' and acct in ('30207') and branch = '$_SESSION[branchid]';");
			list($input) = parent::getArray("select sum(debit-credit) from apv_details where apv_no = '$apv_no' and acct = '30208' and branch = '$_SESSION[branchid]';");
			list($ap) = parent::getArray("select sum(credit-debit) from apv_details where apv_no = '$apv_no' and acct in ('30101','30102') and branch = '$_SESSION[branchid]';");
			parent::dbquery("update ignore apv_header set amount = '$ap', vat = '$input', ewt_amount = '$ewt', balance = '$ap' where apv_no = '$apv_no' and branch = '$_SESSION[branchid]';");
		}
		
		function setHeaderControls($status,$apv_no,$urights) {
			$headerControls = '';
			if($lock != 'Y') {
				switch($status) {
					case "Posted":
						if($urights == "admin") {
							$headerControls = "<a href=\"#\" class=\"topClickers\" onclick=\"javascript: reopenAP('$apv_no');\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status</a>&nbsp;";
						}
						$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printAPV('$apv_no','$_SESSION[userid]','N');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print Voucher</a>";
					break;
					case "Cancelled":
						if($urights == "admin") {
							$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" onclick=\"javascript:reuseAP('$apv_no');\" ><img src=\"images/icons/refresh.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Recycle this Document</a>&nbsp;";	
						}
					break;
					case "Active": default:
						$headerControls = "<a href=\"#\" class=\"topClickers\" onClick=\"javascript:finalizeAPV('$apv_no','$_SESSION[userid]');\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Finalize Voucher</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:saveAPVHeader();\"><img src=\"images/save.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:loadInvoices();\"><img src=\"images/icons/attach-icon.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Setup AP thru Receiving Report</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:encodeInvoices();\"><img src=\"images/icons/invoice.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Direct Purchases</a>&nbsp;";
						if($urights == "admin" && $dS != 1) {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelAPV('$apv_no');\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel Document</a>";
						}
					break;
				}
				
			} else {
				$headerControls = $headerControls . "&nbsp;<button  onClick=\"javascript:parent.printAPV('$apv_no','$_SESSION[userid]','Y');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Re-Print Voucher</button>";
			}
			echo $headerControls;
		}
		
		function setNavButtons($apv_no) {
			$nav = '';
			list($fwd) = parent::getArray("select apv_no from apv_header where apv_no > $apv_no and branch = '$_SESSION[branchid]' limit 1;");
			list($prev) = parent::getArray("select apv_no from apv_header where apv_no < $apv_no and branch = '$_SESSION[branchid]' order by apv_no desc limit 1;");
			list($last) = parent::getArray("select apv_no from apv_header where branch = '$_SESSION[branchid]' order by apv_no desc limit 1;");
			list($first) = parent::getArray("select apv_no from apv_header where branch = '$_SESSION[branchid]' order by apv_no asc limit 1;");
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewAP('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewAP('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			echo "<a href=# onclick=\"parent.viewAP('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewAP('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
		}

		function getMod($def,$mod) {
			if($def == $mod) { echo "class=\"float2\""; }
		}
	
	}


?>