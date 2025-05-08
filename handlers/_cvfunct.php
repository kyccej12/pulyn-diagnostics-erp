<?php
	require_once "_generics.php";
	
	class myCV extends _init {
		function setHeaderControls($status,$locked,$cv_no,$uid,$dS,$urights) {
			$headerControls = '';
			if($locked != 'Y') {
				switch($status) {
					case "Posted":	
						list($posted_by,$posted_on) = parent::getArray("select fullname as name, date_format(updated_on,'%m/%d/%Y %p') as date_posted from cv_header a left join user_info b on a.updated_by=b.emp_id where a.cv_no='$cv_no' and branch = '$_SESSION[branchid]';");
						if($urights == "admin") {
							$headerControls = "<a href=\"#\" class=\"topClickers\" onclick=\"javascript: reopenCV('$cv_no');\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status</a>&nbsp;";
						}
						$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\"  onClick=\"javascript:parent.printCV('$cv_no','$_SESSION[userid]','N');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print Cash/Check Voucher</a>&nbsp;";
					break;
					case "Cancelled":
						if($urights == "admin") {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:reuseAP('$cv_no');\" ><img src=\"images/icons/refresh.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Recycle this Document</a>";	
						}
					break;
					case "Active": default:
						$headerControls = "<a href=\"#\" class=\"topClickers\" onClick=\"javascript:finalizeCV('$cv_no','$_SESSION[userid]');\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Post & Finalize Voucher</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:saveCVHeader();\"><img src=\"images/save.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:loadGRFP();\"><img src=\"images/icons/bill.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Apply to Petty Cash Request</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:loadRFP();\"><img src=\"images/icons/apv256.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Apply to Approved Request for Payment</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:encodeInvoices();\"><img src=\"images/icons/invoice.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Direct Purchases Invoice</a>&nbsp;&nbsp;";
						if($urights == "admin" && $dS != 1) {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelCV('$cv_no');\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel this Document</a>";
						}
					break;
				}
			} else {
				$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printCV('$cv_no','$_SESSION[userid]','Y');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Re-Print Cash/Check Voucher</a>";
			}
			echo $headerControls;
		}
		
		function setNavButtons($cv_no) {
			
			$nav = '';
			
			list($fwd) = parent::getArray("select cv_no from cv_header where cv_no > $cv_no and branch = '$_SESSION[branchid]' limit 1;");
			list($prev) = parent::getArray("select cv_no from cv_header where cv_no < $cv_no and branch = '$_SESSION[branchid]' order by cv_no desc limit 1;");
			list($last) = parent::getArray("select cv_no from cv_header where branch = '$_SESSION[branchid]' order by cv_no desc limit 1;");
			list($first) = parent::getArray("select cv_no from cv_header where branch = '$_SESSION[branchid]' order by cv_no asc limit 1;");
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewCV('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewCV('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			echo "<a href=# onclick=\"parent.viewCV('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewCV('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
		
		}
		
		function updateHeadAmount($cv_no,$cib) {
			$a = parent::dbquery("select ifnull(sum(credit),0) from cv_details where cv_no = '$cv_no' and acct in ('30101','30102') and branch = '$_SESSION[branchid]';");
			$b = parent::dbquery("select ifnull(sum(debit),0) from cv_details where cv_no = '$cv_no' and acct = '30208' and branch = '$_SESSION[branchid]';");
			$c = parent::dbquery("select ifnull(sum(credit),0) from cv_details where cv_no = '$cv_no' and acct = '$cib' and branch = '$_SESSION[branchid]';");
			list($ewt) = $a->fetch_array(); list($input) = $b->fetch_array(); list($ap) = $c->fetch_array();
			parent::dbquery("update ignore cv_header set amount = '$ap', vat = '$input', ewt_amount = '$ewt' where cv_no = '$cv_no' and branch = '$_SESSION[branchid]';");
		}

		
		function CVDETAILS($cv_no,$status,$lock) {
		
			$i = 1;
			$details = parent::dbquery("select record_id as line_id, ref_no, if(ref_type!='SI',concat(ref_type,'-',trim(LEADING '0' from ref_no)),ref_no) as xref, date_format(ref_date,'%m/%d/%Y') as rd8, if(ref_type = 'SI','RR/Invoice',ref_type) as ref_type, ref_type as xref_type, concat(ref_no,ref_type) as xchecker, if(acct_branch!='1',concat(acct,'-',lpad(acct_branch,2,0)),acct) as acct, acct_desc, debit, credit, if(cost_center='','...',cost_center) as cost_center from cv_details where cv_no = '$cv_no' and branch = '$_SESSION[branchid]' order by ref_no, ref_type, debit desc, acct_desc;");
			echo '<table width=100% cellspacing=0 cellpadding=0>';
			while($x = $details->fetch_array(MYSQLI_BOTH)) {
				if($status == "Active") {
					if($x['cost_center'] != '...') { $xcostcenter = parent::identCostCenter($x['cost_center']); } else { $xcostcenter = "..."; }
					if($x['xref_type'] == 'SI') { $dbt = '<a href="#" onclick="javascript: deleteInvoice(\''.$x['ref_no'].'\',\''.$x['xref_type'].'\');" title="Delete Invoice From Check Voucher"><img src="images/icons/delete.png" width=16 height=16 style="vertical-align: middle;" /></a>'; } else { $dbt = '<a href="#" onclick="javascript: deleteLine(\''.$x['line_id'].'\',\''.$cv_no.'\');" title="Delete Line Entry"><img src="images/icons/delete.png" width=16 height=16 style="vertical-align: middle;" /></a>'; }
					if($x['xref_type'] == "AP" || $x['xref_type'] == "AP-BB") {	$mydb = "<a href=\"#\" style=\"text-decoration: none; color: black;\" onclick=\"javascript: changeAPAmount($x[line_id],'$x[debit]');\" title=\"Click figure to change the amount.\">".number_format($x['debit'],2)."</a>"; } else { $mydb = number_format($x['debit'],2); }
					$center = "<a href=\"#\" style=\"text-decoration: none; color: black;\" onclick=\"javascript: changeCostCenter($x[line_id],'$x[cost_center]');\">".$xcostcenter."</a>";
				} else { $dbt = '&nbsp;'; $center = parent::identCostCenter($x['cost_center']); $mydb = number_format($x['debit'],2); }
				if($ox != $x['xchecker']) { $ref_no = $x['xref']; $ref_date = $x['rd8']; } else { $ref_no = ''; $ref_date = ''; $dbt = ''; }
				echo '<tr bgcolor="'.parent::initBackground($i).'">
						<td align=center class="grid" width="8%"><b>'.$ref_no.'</b></td>
						<td align=center class="grid" width="8%"><b>'.$ref_date.'</b></td>
						<td align=center class="grid" width="8%" style="padding-left: 10px;">'.$x['ref_type'] .'</td>
						<td align=left class="grid" width="10%" style="padding-left: 10px;">'.$x['acct'].'</td>
						<td align=left class="grid" width="31%">'.$x['acct_desc'].'</td>
						<td align=center class="grid" width="10%" id="c_'.$x['line_id'].'">'.$center.'</td>
						<td class="grid" width="8%" align=right style="padding-right: 20px;" id="xc_'.$x['line_id'].'">'.$mydb.'</td>
						<td class="grid" align=right width="8%" style="padding-right: 10px;">'.number_format($x['credit'],2).'</td>
						<td class="grid" align=right style="padding-right: 5px;">'.$dbt.'</td>
					</tr>'; $dbGT+=$x['debit']; $crGT+=$x['credit']; $i++; $ox = $x['xchecker'];
			}
			echo '<tr>
					<td class="grid" align=right colspan="6"><b>TOTAL AMOUNT &raquo;&nbsp;&nbsp;&nbsp;</b></td>
					<td class="grid" width="8%" align=right style="font-weight: bold;padding-right: 20px;"><span id=amtGT>'.number_format($dbGT,2) . '</span></td>
					<td class="grid" width="8%" align=right style="font-weight: bold;padding-right: 10px;"><span id=amtGT>'.number_format($crGT,2) . '</span></td>
					<td class="grid">&nbsp;</td>
				</tr>';

			
			if($i < 10) { for($i; $i <= 10; $i++) {
				echo '<tr bgcolor='.parent::initBackground($i).'>
							<td align=left class="grid" width="100%" colspan=10>&nbsp;</td>
					</tr>';
				}
			}
			echo '</table>';
			
		}
		
	
	}

?>