<?php
	require_once("_generics.php");	
	class myRFP extends _init {
		
		public $total;
		public $vat;
		public $ewt;
		public $net;
		public $cv;
		
		function getSummaryValues($rfpNo,$status) {
			$det = parent::getArray("SELECT SUM(amount) AS amount, SUM(vat) AS vat, SUM(ewt) AS ewt, SUM(net_payable) AS net FROM rfp_details WHERE rfp_no = '$rfpNo' AND branch = '$_SESSION[branchid]';");
			$this->total = number_format($det['amount'],2); 
			$this->vat = number_format($det['vat'],2); 
			$this->ewt = number_format($det['ewt'],2); 
			$this->net = number_format($det['net'],2);
			
			if($status === 'Finalized') {
				$x = parent::dbquery("SELECT a.cv_no FROM cv_header a LEFT JOIN cv_details b ON a.cv_no = b.cv_no and a.branch = b.branch WHERE b.ref_no = '$rfpNo' and b.rfp_type = 'RFP' AND a.branch = '$_SESSION[branchid]' and a.status = 'Posted';");
				$cvlist = '';
				while(list($myCV) = $x->fetch_array()) { $cvlist .= $myCV . ","; }
				$cvlist = substr($cvlist,0,-1);
				$this->cv = $cvlist;
			}
			
		}
		
		function updateHeadAmount($rfp_no) {
			list($amt) = parent::getArray("select sum(net_payable) from rfp_details where rfp_no = '$rfp_no';");
			parent::dbquery("update ignore rfp_header set amount = '$amt' where rfp_no = '$rfp_no';");
		}
			
		function setHeaderControls($status,$rfp_no,$uid,$dS) {
			list($urights) = parent::getArray("select user_type from user_info where emp_id = '$uid'");
			switch($status) {
				case "Finalized":
					list($posted_by,$posted_on) = parent::getArray("select fullname as name, date_format(updated_on,'%m/%d/%Y %p') as date_posted from rfp_header a left join user_info b on a.updated_by=b.emp_id where a.rfp_no='$rfp_no';");
					if($urights == "admin") {
						$headerControls = "<a href=\"#\" class=\"topClickers\"  onclick=\"javascript: reopenRFP('$rfp_no');\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status<a/>&nbsp;&nbsp;";
					} 
					$headerControls .= "<a href=\"#\" class=\"topClickers\"  onClick=\"javascript:reprintRFP('$rfp_no','$_SESSION[userid]');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print RFP<a/>&nbsp;";
				break;
				case "Cancelled":
					if($urights == "admin") {
						$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" type=button onclick=\"javascript:reuseRFP('$rfp_no');\" ><img src=\"images/icons/refresh.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Recycle this Document<a/>&nbsp;";	
					}
				break;
				case "Active": default:
					$headerControls = "<a href=\"#\" class=\"topClickers\"  onClick=\"javascript:printRFP('$rfp_no','$_SESSION[userid]');\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Finalize RFP<a/>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:saveHeader();\"><img src=\"images/icons/floppy.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes<a/>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:loadInvoices();\"><img src=\"images/icons/attach-icon.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Attach From Vouchers Payable<a/>&nbsp;&nbsp;";
					if($urights == "admin" && $dS != 1) {
						$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelRFP('$rfp_no');\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel this Document<a/>&nbsp;";
					}
				break;
			}
			echo $headerControls;
		}
		
		function setNavButtons($rfp_no) {
			/*
			list($fwd) = getArray("select rfp_no from rfp_header where rfp_no > $rfp_no limit 1;");
			list($prev) = getArray("select rfp_no from rfp_header where rfp_no < $rfp_no order by rfp_no desc limit 1;");
			list($last) = getArray("select rfp_no from rfp_header order by rfp_no desc limit 1;");
			list($first) = getArray("select rfp_no from rfp_header order by rfp_no asc limit 1;");
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewAP('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewAP('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			echo "<a href=# onclick=\"parent.viewAP('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewAP('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
			*/
		}
		
		function RFPDETAILS($rfp_no) {
			$i = 0; $t = 0;
			$details = parent::dbquery("SELECT *, DATE_FORMAT(apv_date,'%m/%d/%Y') AS ad8, DATE_FORMAT(due_date,'%m/%d/%Y') AS dd8, LPAD(apv_no,6,0) AS ano,IF(apv_remarks='0','',apv_remarks) AS my_remarks FROM rfp_details WHERE rfp_no = '$rfp_no';");
			list($status) = parent::getArray("select status from rfp_header where rfp_no = '$rfp_no';");
			echo '<table width=100% cellspacing=0 cellpadding=0>';
			while($x = $details->fetch_array()) {
				if($status == "Active") { 
					$dbt = '<input type=radio name = "lineItem" value = "'.$x['line_id'].'">';
				}
				echo '<tr bgcolor="'.parent::initBackground($i).'">
						<td align=center class="grid" width="10%">'.$x['ano'].'</td>
						<td align=center class="grid" width="10%">'.$x['ad8'].'</td>
						<td align=left  class="grid" width="31%">'.$x['my_remarks'].'</td>
						<td align=center class="grid" width="10%"p>'.$x['dd8'].'</td>
						<td align=right class="grid" width="10%">'.number_format($x['amount'],2).'</td>
						<td align=right class="grid" width="10%">'.number_format($x['vat'],2).'</td>
						<td align=right class="grid" >'.number_format($x['ewt'],2).'</td>
						<td align=right class="grid" width=10%>'.number_format($x['net_payable'],2).'&nbsp;&nbsp;'.$dbt.'</td>
					</tr>'; $i++;
			}


			
			if($i < 6) { for($i; $i <= 5; $i++) {
				echo '<tr bgcolor='.parent::initBackground($i).'>
							<td align=left class="grid" width="100%" colspan=9>&nbsp;</td>
					</tr>';
				}
			}
			
			echo '</table>';
		}
	}	

?>