<?php
	
	//ini_set("display_errors","On");
	require_once "_generics.php";
	
	class myJV extends _init {
		
		function genTraceno(){
			$flag = true;
			while($flag){
				list($trace_no) = parent::getArray("SELECT LEFT(MD5(RAND()),32) trace_no;");
				list($traceCount) = parent::getArray("SELECT COUNT(trace_no) FROM journal_header a WHERE a.trace_no = '$trace_no';");
				if($traceCount>0){
					$flag = true;
				}else{
					$flag = false;
				}
			}
			return $trace_no;
		}
		
		function setHeaderControls($status,$lock,$j_no,$uid,$dS,$linked,$urights) {
			$headerControls = '';
			if($lock != 'Y') {
				switch($status) {
					case "Posted":
						if($urights == "admin") {
							$headerControls = "<a href=\"#\" class=\"topClickers\" onclick=\"javascript: reopenJV('$j_no');\"><img src='images/icons/edit.png' align=absmiddle width=16 height=16 />&nbsp;Set this Document to Active Status</a>&nbsp;";
							if($linked == "Y") {
								$headerControls = $headerControls . "<a href=\"#\" class=\"topClickers\"onclick=\"javascript: unlinkJV('$j_no');\"><img src='images/icons/disconnect.png' align=absmiddle width=16 height=16 />&nbsp;Un-link Document</a>&nbsp;";
							}
						}
						$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printJV('$j_no','$_SESSION[userid]','N');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Print Journal Voucher</a>&nbsp;";
					break;
					case "Active": default:
						$headerControls = "<a href=\"#\" class=\"topClickers\" onClick=\"javascript:finalizeJV('$j_no','$_SESSION[userid]');\"><img src=\"images/icons/ok.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Finalize Journal Voucher</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:saveJVHeader();\"><img src=\"images/save.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Save Changes</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:applyDocuments();\"><img src=\"images/icons/attach-icon.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Apply to Posted Documents</a>&nbsp;&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:encodeInvoices();\"><img src=\"images/icons/invoice.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Direct Purchases Invoice</a>&nbsp;&nbsp;";
						if($urights == "admin" && $dS != 1) {
							$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onclick=\"javascript:cancelJV('$j_no');\"><img src=\"images/icons/cancel.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Cancel this Document</a>&nbsp;";
						}
					break;
				}
			} else {
				$headerControls = $headerControls . "&nbsp;<a href=\"#\" class=\"topClickers\" onClick=\"javascript:parent.printJV('$j_no','$_SESSION[userid]','Y');\"><img src=\"images/icons/print.png\" width=16 height=16 border=0 align=\"absmiddle\">&nbsp;Re-Print Journal Voucher</a>";
			}
			echo $headerControls;
		}
		
		function setNavButtons($j_no) {
			
			$nav = '';
			
			list($fwd) = parent::getArray("select j_no from journal_header where j_no > $j_no and branch = '1' limit 1;");
			list($prev) = parent::getArray("select j_no from journal_header where j_no < $j_no and branch = '1' order by j_no desc limit 1;");
			list($last) = parent::getArray("select j_no from journal_header where branch='1' order by j_no desc limit 1;");
			list($first) = parent::getArray("select j_no from journal_header where branch='1' order by j_no asc limit 1;");
			
			if($prev)
				$nav = $nav . "<a href=# onclick=\"parent.viewJV('$prev');\"><img src='images/resultset_previous.png'  title='Previous Record' /></a>";
			
			if($fwd) 
				$nav = $nav . "<a href=# onclick=\"parent.viewJV('$fwd');\"><img src='images/resultset_next.png' 'title='Next Record' /></a>";
			
			echo "<a href=# onclick=\"parent.viewJV('$first');\"><img src='images/resultset_first.png' title='First Record' /><a>" . $nav . "<a href=# onclick=\"parent.viewJV('$last');\"><img src='images/resultset_last.png' title='Last Record' /></a>";
		}
		
		function JVDETAILS($j_no) {
			$i = 1; $t = 0;
			
			list($status) = parent::getArray("select status from journal_header where j_no = '$j_no' and branch = '1';");
			$details = parent::dbquery("select record_id as line_id, ref_no, if(ref_date='0000-00-00','',date_format(ref_date,'%m/%d/%Y')) as rd8, ref_type, concat(ref_no,ref_type) as xchecker, client as myclient, if(client='0','',lpad(client,3,0)) as xclient, client, acct, acct_desc, debit, credit, if(cost_center='','...',cost_center) as cost_center from journal_details where j_no = '$j_no' and branch='1' order by debit, acct_desc;");
			
			echo '<table width=100% cellspacing=0 cellpadding=0>';
			while($x = $details->fetch_array(MYSQLI_BOTH)) {

				if(($status == 'Active' || $status == '') && $lock != 'Y') { 
					if($x['cost_center'] != '...') { $xcostcenter = parent::identCostCenter($x['cost_center']); } else { $xcostcenter = "..."; }
					if($x['ref_type'] == 'SI') { $dbt = '<a href="#" onclick="javascript: deleteInvoice(\''.$x['ref_no'].'\',\''.$x['ref_type'].'\');" title="Delete Invoice From  Voucher"><img src="images/icons/delete.png" width=16 height=16 style="vertical-align: middle;" /></a>'; } else { $dbt = '<a href="#" onclick="javascript: deleteLine(\''.$x['line_id'].'\',\''.$j_no.'\');" title="Delete Line Entry"><img src="images/icons/delete.png" width=16 height=16 style="vertical-align: middle;" /></a>'; }
					$center = "<a href=\"#\" style=\"text-decoration: none; color: black;\" onclick=\"javascript: changeCostCenter($x[line_id],'$x[cost_center]');\">".$xcostcenter."</a>";
				} else {
					$dbt = ""; 
					$center = parent::identCostCenter($x['cost_center']);
				}
				if($x['client'] != "" || $x['client'] != 0) { $title = parent::getContactName($x['myclient']); } else { $title = ""; }
				if($ox != $x['xchecker']) { $ref_no = $x['ref_no']; $ref_date = $x['rd8']; $ref_type = $x['ref_type']; } else { $ref_no = ''; $ref_date = ''; $ref_type = ''; }
				echo '<tr bgcolor="'.parent::initBackground($i).'">
						<td align=center class="grid" width="7%"><b>'.$ref_no.'</b></td>
						<td align=center class="grid" width="7%"><b>'.$ref_date.'</b></td>
						<td align=center class="grid" width="7%">'.$x['ref_type'] .'</td>
						<td align=center class="grid" width="7%" title="'.$title.'">'.$x['xclient'] .'</td>
						<td align=center class="grid" width="7%" style="padding-left: 10px;">'.$x['acct'].'</td>
						<td align=left class="grid" width="30%" style="padding-left: 10px;">'.$x['acct_desc'].'</td>
						<td align=center class="grid" width="10%" id="c_'.$x['line_id'].'">'.$center.'</td>
						<td class="grid" width="7%" align=right style="padding-right: 1%;">'.number_format($x['debit'],2).'</td>
						<td class="grid" width="7%" align=right style="padding-right: 1%;">'.number_format($x['credit'],2).'</td>
						<td align=center class="grid" width="8%">&nbsp;</td>
						<td align=center class="grid">'.$dbt.'</td>
					</tr>'; $i++; $ox = $x['xchecker'];
			}


		
			if($i < 8) { for($i; $i <= 8; $i++) {
				echo '<tr bgcolor='.parent::initBackground($i).'>
							<td align=left class="grid" width="100%" colspan=11>&nbsp;</td>
					</tr>';
				}
			}
			
			echo '</table>';
		}
		
	}

?>