<?php
	
	require_once("_generics.php");	
	class myPO extends _init {
		
		function setHeaderControls($status,$po_no,$urights) {

			$headerControls = '';

			if($lock != 'Y') {
				switch($status) {
					case "Finalized":
						list($posted_by,$posted_on) = parent::getArray("select fullname as name, date_format(updated_on,'%m/%d/%Y %p') as date_posted from pharma_po_header a left join user_info b on a.updated_by=b.emp_id where a.po_no = '$po_no';");
						
						if($urights == "admin") {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript: reopenPO();\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status</a>&nbsp;";
						}
						$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printPharmaPO('$po_no');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print Purchase Order</a>&nbsp;";
					break;
					case "Cancelled":
						if($urights == "admin") {
							$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" onclick=\"javascript:reusePO();\" style=\"padding: 5px;\"><img src=\"images/icons/refresh.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Recycle this Document</a>&nbsp;";	
						}
					break;
					case "Active": default:
						$headerControls = "<a href=\"#\" class=\"topClickers\" onClick=\"javascript:finalize();\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Finalize Purchase Order</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:savePOHeader();\"><img src=\"images/save.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes</a>&nbsp;";
						if($urights == "admin") {
							$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelPO();\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel this Document</a>&nbsp;";
						}
					break;
				}
			} else {
				$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printPharmaPO('$po_no');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Re-Print Purchase Order</a>&nbsp;";
			}
			echo $headerControls;
		}
		
		function setNavButtons($po_no,$bid) {	
			$nav = '';	
			list($fwd) = parent::getArray("select po_no from pharma_po_header where po_no > $po_no and branch = '$bid' limit 1;");
			list($prev) = parent::getArray("select po_no from pharma_po_header where po_no < $po_no and branch = '$bid' order by po_no desc limit 1;");
			list($last) = parent::getArray("select po_no from pharma_po_header where branch = '$bid' order by po_no desc limit 1;");
			list($first) = parent::getArray("select po_no from pharma_po_header where branch = '$bid' order by po_no asc limit 1;");
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewPO('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewPO('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			echo "<a href=# onclick=\"parent.viewPO('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewPO('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
		}
		
		function updateHeaderAmt($po_no,$bid) {
			list($amt) = parent::getArray("select sum(ROUND(qty*cost,2)) as amount from pharma_po_details where po_no = '$po_no' and branch = '$bid';");
			parent::dbquery("update ignore pharma_po_header set amount = '0$amt' where po_no = '$po_no' and branch = '$bid';");
		}
		
	}



?>