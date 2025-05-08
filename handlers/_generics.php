<?php
	//ini_set("display_errors","On");
	require_once "initDB.php";
	class _init extends myDB {
	
		public $pageNum;
		public $cpass;
		public $exception;
		public $age;
		public $ageDisplay;


		public function _toHrs($_x) {
			return ROUND($_x / 3600,2);
		}
		
		public function renew_timestamp($key,$time) {
			$v = parent::dbquery("update active_sessions set timestamp = '$time' where sessid = '$key';");
			if($v) { return true; }
		}
		
		function queryOffset($rowsPerPage,$page) {
			if($page > 1) { $this->pageNum = $page; } else { $this->pageNum = 1; }
			$offset = ($this->pageNum - 1) * $rowsPerPage;
			return $offset;
		}
		
		

		function paginate($rpage,$nrows,$jfunct,$stxt,$isDetails) {
			$hString = ''; $maxPage = ceil($nrows/$rpage);
			
			if($nrows > 0) {
				if ($this->pageNum > 1) { 
					$hString .= "<a href=\"javascript: parent.$jfunct('".($this->pageNum-1)."','$stxt','$isDetails');\" class=\"a_link\" title=\"Previous Page\"><span style=\"font-size: 18px;\">&laquo;</span></a>&nbsp;"; 
				}
				$hString .= "<span style=\"font-size: 12px;\">Page " . $this->pageNum . " of ". $maxPage . "</span>&nbsp;";
				if($this->pageNum != $maxPage) {
					$hString .= "<a href=\"javascript: parent.$jfunct('".($this->pageNum + 1) . "','$stxt','$isDetails');\" class=\"a_link\" title=\"Next Page\"><span style=\"font-size: 18px;\">&raquo;</span></a>&nbsp;&nbsp;";
				}
				if($maxPage > 1) {
					$hString .= "<span style=\"font-size: 12px;\">Jump To: </span><select id=\"jpage\" name=\"jpage\" style=\"width: 40px; padding: 0px;\" onchange=\"javascript: parent.$jfunct(this.value,'$stxt','$isDetails');\">";
					for ($x = 1; $x <= $maxPage; $x++) {
						$hString .= "<option value='$x' ";
						if($this->pageNum == $x) { $hString .= "selected"; }
						$hString .= ">$x</option>";
					}
					$hString .= "</select>";
				}
			}
			
			echo $hString;
		}	
		
		function generateRandomString($length = 64) {
			
			$string = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
			$block1 = substr($string,0,31);
			$tstamp = time();
			$block2 = substr($string,42,63);
			
			$hashKey = $block1 . $tstamp . $block2;
			
			//$string .= strtotime(date('Y-m-d H:i:s'));
			return $hashKey;
		}

		function getUname($uid) {
			list($name) = parent::getArray("select fullname from user_info where emp_id = '$uid';");
			echo $name;
		}
		
		function validateKey() {
			$tcur = time();
			
			list($_sess) = parent::getArray("select count(*) from active_sessions where sessid = '$_SESSION[authkey]';");
			if($_sess > 0) {
				list($tstamp) = parent::getArray("select `timestamp` from active_sessions where sessid = '$_SESSION[authkey]';");
				$life = $tcur - $tstamp;
				if($life > 7200) {
					$this->exception = 2;
					unset($_SESSION['userid']);
					unset($_SESSION['authkey']);
					unset($_SESSION['branchid']);
					unset($_SESSION['company']);
					session_destroy();
					parent::dbquery("delete from active_sessions where sessid = '$_SESSION[authkey]';");
				} else {
					if($this->renew_timestamp($_SESSION['authkey'],$tcur) == true) { $this->exception = 0; } else { $this->exception = 3; }
					list($this->cpass) = parent::getArray("select require_change_pass from user_info where emp_id = '$_SESSION[userid]';");
				}
			} else {
				$this->exception = 4;
			}
		}
		
		function updateTstamp($skey) {
			parent::dbquery("update active_sessions set `timestamp` = '" . time() . "' where sessid = '$skey';");

		}
		
		function trailer($module,$action) {
			parent::dbquery("insert ignore into traillog (user_id,`timestamp`,ipaddress,module,`action`) values ('$_SESSION[userid]',now(),'$_SERVER[REMOTE_ADDR]','$module','".mysql_real_escape_string($action)."');");
		}
		
		function initBackground($i) {
			if($i%2==0){ $bgC = "#ededed"; } else { $bgC = "#ffffff"; }
			return $bgC;
		}

		function highlight($text, $words) {
			$split_words = explode( " " , $words );
			foreach($split_words as $word)
			{
				$color = "#68dae8";
				$text = preg_replace("|($word)|Ui" ,
					"<span style=\"background:".$color.";\"><b>$1</b></span>" , $text );
			}
			return $text;
		}
				
		function createMenu($uid) {
			list($rcount) = parent::getArray("select count(*) from user_rights where UID='$uid';");
			if($rcount > 0) {
				echo "<div id=\"menu\" class=\"chromestyle\">\n<div id=\"submenu_1\">\n<ul>";
				$res = parent::dbquery("SELECT DISTINCT c.id, c.tab, c.iconf FROM user_rights a LEFT JOIN menu_sub b ON a.MENU_ID=b.submenu_id LEFT JOIN menu_main c ON b.parent_id=c.id WHERE a.UID='$uid' and c.id != '3' order by c.sort;");	
				while($main = $res->fetch_array(MYSQLI_BOTH)) {
					echo "<li><a href=\"#\" rel=\"dropmenu$main[0]\"><img src=\"images/icons/$main[iconf]\" width=20 height=20 align=absmiddle />&nbsp;$main[1]</a></li>\n";
				}
				echo "</ul>\n</div>\n</div>";
				$res2 = parent::dbquery("SELECT DISTINCT c.id, c.tab FROM user_rights a LEFT JOIN menu_sub b ON a.MENU_ID=b.submenu_id LEFT JOIN menu_main c ON b.parent_id=c.id WHERE a.UID='$uid' and b.subAsset = 'N' and c.id != '3';");	
				while($sub = $res2->fetch_array(MYSQLI_BOTH)) {
					$ms = parent::dbquery("SELECT menu_title, jfunct, icon_name FROM menu_sub a LEFT JOIN user_rights b ON a.submenu_id=b.MENU_ID WHERE parent_id='$sub[0]' AND b.UID='$uid' and a.subAsset = 'N' ORDER BY sort;");
					list($icount) = parent::getArray("select (count(*)-1) as icount from (SELECT menu_title, jfunct, icon_name FROM menu_sub a LEFT JOIN user_rights b ON a.submenu_id=b.MENU_ID WHERE parent_id='$sub[0]' AND b.UID = '$uid' and a.subAsset = 'N') a;");
					echo "<div id=\"dropmenu$sub[0]\" class=\"dropmenudiv\">";
					$cut = 0;
					while($menu = $ms->fetch_array(MYSQLI_BOTH)) {
						echo "<a href=\"#\" onClick=\"$menu[jfunct]\"><img src=\"images/icons/$menu[icon_name]\" width=20 height=20 align=absmiddle border=0 />&nbsp;&nbsp;$menu[menu_title]</a>\n";
						if($icount != 0) {
							if($cut < $icount) { echo "<hr width=95% align=center style=\"border-color: #ffffff;\"></hr>";	}
						}
						$cut++;
					}
					echo "</div>";
					
				}
				echo "<script type=\"text/javascript\">cssdropdown.startDROP(\"menu\")</script>";
			}
		}
		
		function _structInput($a,$b,$c,$d,$e,$f) {
			echo "<tr>
					<td width=$b><span class=\"spandix-l\">$a :</span></td>
					<td>
						<input type=\"text\" id=\"$c\" class=\"$d\" style=\"$e\" value = \"$f\" />
					</td>
				</tr>
			<tr><td height=4></td></tr>";
		}
		
		function _structAccount($a,$b,$c,$d,$e,$f,$g,$h) {
			echo "<tr>
					<td width=\"$b\"><span class=\"spandix-l\">$a :</span></td>
					<td>
						<select class=\"$d\" id=\"$c\" name=\"$c\" style=\"$e\">
					";
			
					if(isset($g) && $g!="") { $ext = " and acct_grp in ('$g') "; }
					if($f == 'Y') { echo "<option value=''> - All Accounts -</option>"; }
					if($h != '') { $exclude = " and acct_code not in ($h) "; }
					
					$ach = parent::dbquery("select acct_code, description from acctg_accounts where 1=1 $ext $exclude order by acct_code, description;");
					while($achrow = $ach->fetch_array(MYSQLI_BOTH)) {
						echo "<option value='$achrow[0]'>$achrow[1] ($achrow[0])</option>";
					}
					unset($ach);
					
				echo "</select></td>
			</tr>
			<tr><td height=4 colspan=2></td></tr>";
		}
		
		function _structMonths($a,$b,$c) {
			$string =  '<tr>
						<td width=35%><span class="spandix-l">Month :</span></td>
						<td>
							<select id="'.$a.'" name="'.$a.'"  class="'.$c.'" style="'.$b.'">
								<option value="01">January</option>
								<option value="02">February</option>
								<option value="03">March</option>
								<option value="04">April</option>
								<option value="05">May</option>
								<option value="06">June</option>
								<option value="07">July</option>
								<option value="08">August</option>
								<option value="09">September</option>
								<option value="10">October</option>
								<option value="11">November</option>
								<option value="12">December</option>
							</select>
						</td>
					</tr>
					<tr><td height=4></td></tr>
			    ';
			echo $string;
		}
		
		function _structYear($a,$b,$c,$d,$e) {
			echo '<tr>
					<td width='.$b.' valign=top><span class="spandix-l">'.$a.' :</span></td>
					<td>
						<select id="'.$c.'" class="'.$d.'" '.$e.'>';
							$cy = date('Y');
							for($x=$cy;$x>=2018;$x--){
								echo "<option value=$x>$x</option>";
							}							
					echo '</select>
					</td>
				</tr>
				<tr><td height=4></td></tr>';
		}

		function timify($el,$w) {
			if($w=='') { $w = '80px;'; }

			$time = "<select name='$el"."_hr' class='gridInput' style='width: $w'>";
			
			for($i=1;$i<=23;$i++) {
				$h = str_pad($i,2,'0',STR_PAD_LEFT);
				$curT = date('H');

				if($h === $curT) { $selected = "selected"; } else { $selected = ''; }
				$time .= "<option value='$h' $selected>$h</option>";

			}

			$time .= "</select> : <select name='$el"."_min' class='gridInput' style='width: $w'>";
			
			for($i=1;$i<=59;$i++) {
				$min = str_pad($i,2,'0',STR_PAD_LEFT);
				$curT = date('i');

				if($min === $curT) { $selected = "selected"; } else { $selected = ''; }
				$time .= "<option value='$min' $selected>$min</option>";

			}
			
			$curT = date('a');

			$time .= "</select>";

			echo $time;
		}
		
		function constructCostCenter() {
			$option = '';
			$uLoop = parent::dbquery("SELECT unitcode,costcenter from options_costcenter;");
			$option = "<select id='cost_center' name='cost_center' class='gridInput' style='width: 95%'><option value=''>- NA -</option>";
			while(list($pid,$pname) = $uLoop->fetch_array(MYSQLI_BOTH)) {
				$option = $option ."<option value='$pid'>[$pid] $pname</option>";
			}
			$option = $option . "</select>";
			return $option;
		}
		
		function options_project(){
			$opt = '';
			$proj_list = parent::dbquery("SELECT proj_id,proj_code,proj_name FROM options_project WHERE archived = 'N' ORDER BY proj_name;");
			while(list($proj_id,$proj_code,$proj_name) = $proj_list->fetch_array()){
				$opt .='<option value='.$proj_id.'  > '.$proj_name.' </option>';
			}
			return $opt;
		}
		
		function identCostCenter($id) {
			list($center) = parent::getArray("SELECT concat(costcenter,' [',unitcode,']') FROM options_costcenter a WHERE a.unitcode = '$id';");
			return $center;
		}
		
		function _month($dig) {
			switch($dig) {
				case "01": return "January"; break; case "02": return "February"; break; case "03": return "March"; break; case "04": return "April"; break;
				case "05": return "May"; break; case "06": return "June"; break; case "07": return "July"; break; case "08": return "August"; break;
				case "09": return "September"; break; case "10": return "October"; break; case "11": return "November"; break; case "12": return "December"; break;
			}
		}
		
		function getContactName($id) {
			list($cname) = parent::getArray("select tradename from contact_info where file_id = '$id';");
			return $cname;
		}
		
		function identUnit($abbrv) {
			list($unit) = parent::getArray("select UCASE(description) from options_units where unit = '$abbrv';");
			return $unit;
		}
		
		function formatDate($date) {
			$date = explode("/",$date);
			return $date[2]."-".$date[0]."-".$date[1];
		}
		
		function formatDigit($dig) {
			return preg_replace('/,/','',$dig);
		}

		function formatCY($date) {
			$date = explode("/",$date);
			return $date[2];
		}
		
		function getAcctDesc($acct,$company) {
			list($desc) = parent::getArray("select description from acctg_accounts where acct_code = '$acct';");
			return $desc;
		}
		
		function getAcctDesc2($acct,$company) {
			list($desc) = parent::getArray("select concat('[',acct_code,'] ',description) from acctg_accounts where acct_code = '$acct';");
			return $desc;
		}
		
		function applyBalanceNa($company,$branch,$doc_no,$type,$acct,$amount,$cust,$alid) {
			switch($type) {
				case "AP": case "APV":
					if($acct == '30101' || $acct == '30102') {
						parent::dbquery("update ignore apv_header set balance = balance - 0$amount, applied_amount = applied_amount + 0$amount where apv_no = '$doc_no' and branch = '$branch' and supplier = '$cust';");
					} else {
						parent::dbquery("update ignore apv_details set balance = balance - 0$amount, applied_amount = applied_amount + 0$amount where record_id = '$alid';");
					}
				break;
				case "AP-BB":
					parent::dbquery("update ignore apbeg_details set balance = balance - 0$amount, applied_amount = applied_amount + 0$amount where invoice_no = '$doc_no' and branch='$branch' and customer = '$cust';");
				break;
				case "SI":
					parent::dbquery("update ignore invoice_header set balance = balance - 0$amount, applied_amount = applied_amount + 0$amount where invoice_no = '$doc_no' and branch='$branch';");
				break;
				default:
					parent::dbquery("update ignore acctg_gl set applied_amount = applied_amount + 0$amount where acct = '$acct' and doc_type = '$type' and doc_no = '$doc_no' and contact_id = '$cust';");
				break;
			}
		}
		
		function revertBalance($company,$branch,$doc_no,$type,$acct,$amount,$cust,$alid) {
			switch($type) {
				case "AP": case "APV":
					if($acct == '30101' || $acct == '30102') {
						parent::dbquery("update apv_header set balance = balance + $amount, applied_amount = applied_amount - $amount where apv_no = '$doc_no' and branch = '$branch' and supplier = '$cust';");
					} else {
						parent::dbquery("update apv_details set balance = balance + 0$amount, applied_amount = applied_amount - 0$amount where record_id = '$alid';");
					}
				break;
				case "AP-BB":
					parent::dbquery("update apbeg_details set balance = balance + $amount, applied_amount = applied_amount - $amount where invoice_no = '$doc_no' and branch='$branch' and customer = '$cust';");
				break;
				case "SI":
					parent::dbquery("update invoice_header set balance = balance + 0$amount, applied_amount = applied_amount - 0$amount where invoice_no = '$doc_no' and branch='$branch';");
				break;
				default:
					parent::dbquery("update acctg_gl set applied_amount = applied_amount - 0$amount where record_id = '$alid';");
				break;
			}
		}
		
		function identSGroup($itemcode,$comp) {
			list($sgroup) = parent::getArray("select rev_acct from products_master where item_code = '$itemcode' and company = '$comp';");
			return $sgroup;
		}
		
		function identStockCode($itemcode) {
			list($icode) = parent::getArray("select indcode from products_master where item_code = '$itemcode';");
			return $icode;
		}
		
		function inWords($number) {
			$hyphen      = ' ';
			$conjunction = ' ';
			$separator   = ' ';
			$negative    = 'negative ';
			$decimal     = ' point ';
			$dictionary  = array(
				0                   => 'zero',
				1                   => 'one',
				2                   => 'two',
				3                   => 'three',
				4                   => 'four',
				5                   => 'five',
				6                   => 'six',
				7                   => 'seven',
				8                   => 'eight',
				9                   => 'nine',
				10                  => 'ten',
				11                  => 'eleven',
				12                  => 'twelve',
				13                  => 'thirteen',
				14                  => 'fourteen',
				15                  => 'fifteen',
				16                  => 'sixteen',
				17                  => 'seventeen',
				18                  => 'eighteen',
				19                  => 'nineteen',
				20                  => 'twenty',
				30                  => 'thirty',
				40                  => 'forty',
				50                  => 'fifty',
				60                  => 'sixty',
				70                  => 'seventy',
				80                  => 'eighty',
				90                  => 'ninety',
				100                 => 'hundred',
				1000                => 'thousand',
				1000000             => 'million',
				1000000000          => 'billion',
				1000000000000       => 'trillion',
				1000000000000000    => 'quadrillion',
				1000000000000000000 => 'quintillion'
			);
			
			if (!is_numeric($number)) {
				return false;
			}
			
			if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
				// overflow
				trigger_error(
					'inWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
					E_USER_WARNING
				);
				return false;
			}

			if ($number < 0) {
				return $negative . self::inWords(abs($number));
			}
			
			$string = $fraction = null;
			
			if (strpos($number, '.') !== false) {
				list($number, $fraction) = explode('.', $number);
			}
			
			switch (true) {
				case $number < 21:
					$string = $dictionary[$number];
					break;
				case $number < 100:
					$tens   = ((int) ($number / 10)) * 10;
					$units  = $number % 10;
					$string = $dictionary[$tens];
					if ($units) {
						$string .= $hyphen . $dictionary[$units];
					}
					break;
				case $number < 1000:
					$hundreds  = $number / 100;
					$remainder = $number % 100;
					$string = $dictionary[$hundreds] . ' ' . $dictionary[100];
					if ($remainder) {
						$string .= $conjunction . self::inWords($remainder);
					}
					break;
				default:
					$baseUnit = pow(1000, floor(log($number, 1000)));
					$numBaseUnits = (int) ($number / $baseUnit);
					$remainder = $number % $baseUnit;
					$string = self::inWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
					if ($remainder) {
						$string .= $remainder < 100 ? $conjunction : $separator;
						$string .= self::inWords($remainder);
					}
					break;
			}
			
			if (null !== $fraction && is_numeric($fraction)) {
				$string .= $decimal;
				$words = array();
				foreach (str_split((string) $fraction) as $number) {
					$words[] = $dictionary[$number];
				}
				$string .= implode(' ', $words);
			}
			
			return strtoupper($string);
		}
		
		function formatNumber($num, $dec) {
			if($num=='') { $num = 0; }
			if($num < 0) {
				return '('.number_format(abs($num),$dec).')';
			} else {
				return number_format($num,$dec);
			}
		}
		
		 function convert2Short($n) {
			$n = (0+str_replace(",", "", $n));
			if (!is_numeric($n)) return false;
			
			if($n < 0) { $xn = $n * -1; } else { $xn = $n; }
			
			if ($xn > 1000000000000) $xn = round(($xn/1000000000000), 2).'T';
			elseif ($xn > 1000000000) $xn = round(($xn/1000000000), 2).'B';
			elseif ($xn > 1000000) $xn = round(($xn/1000000), 2).'M';
			elseif ($xn > 1000) $xn = round(($xn/1000), 2).'K';
			
			if($n < 0) {
				return '('.$xn.')';
			} else { return $xn; }

		}
		
		function getPerformanceValueVsLastYear($val1,$val2,$mod) {
			if($val1 > 0) {
				if($mod == "Exp") { $upImage = "purchases-up.png"; $downImage = "purchases-down.png"; } else { $upImage = "uptrend.png"; $downImage = "downtrend.php"; }
				$variance = $val1 - $val2;
				$pct = ROUND(($variance / $val1) * 100);
				if($variance > 0) { 
					$title = "Up by ".$this->convert2short($variance)." or $pct% vs. Last Year's Budget of (&#8369;".$this->convert2short($val2).")"; 
				} else { $title = "Down by ".$this->convert2short($variance)." or $pct% vs. Last Year's Budget of (&#8369;".$this->convert2short($val2).")";  }
				return "&nbsp;<img src='../images/icons/$upImage' width=10 height=10 align=absmiddle title = \"$title\" />";
			}
		}

		function calculateAge($dob){
			$today = date('Y-m-d');
			list($age) = parent::getArray("SELECT FLOOR(ROUND(DATEDIFF('$today','$dob') / 364.25,2));");
			return $age;
			
		}

		function calculateAge2($orderdate,$dob) {
			
			$bday = new DateTime($dob); // Your date of birth
			$today = new Datetime($orderdate);

			$diff = $today->diff($bday);
			$ageInYears = $diff->y;
			
			if($ageInYears > 0) {
				$ageDisplay = $ageInYears . "y/o";
			} else {
				if($diff->m < 1) {
					$ageDisplay  = $diff->d . "D";
				} else {
					$ageDisplay = $diff->m . "M";
				}
			}	

			$this->age = $ageInYears;
			$this->ageDisplay = $ageDisplay;

		}

		function validateResult($table,$sono,$code,$serialno,$bid,$uid) {
			switch($code) {
				case "L007":
				case "L015":
					parent::dbquery("update $table set verified = 'Y', verified_by = '$uid', verified_on = now() where so_no = '$sono' and branch = '$bid' and serialno = '$serialno' and code = '$code';");
				break;
				default:
					parent::dbquery("update $table set verified = 'Y', verified_by = '$uid', verified_on = now() where so_no = '$sono' and branch = '$bid' and serialno = '$serialno';");
				break;
			}
			
			

		}

		function updateLabSampleStatus($so,$code,$sn,$stat,$bid,$uid) {
			parent::dbquery("update lab_samples set status = '$stat', updated_by = '$uid', updated_on = now() where so_no = '$so' and branch = '$bid' and code = '$code' and serialno = '$sn';");
		}

		function updateSOStatus($so,$bid) {
			
			/* Total Laboratory Request for the Service Order */
			list($requestCount) = parent::getArray("SELECT count(*) from lab_samples WHERE so_no = '$so' AND branch = '$bid';");

			/* Check if Results are either partially of fully available */
			list($resultCount) = parent::getArray("SELECT count(*) FROM lab_samples WHERE so_no = '$so_no' AND branch = '$bid' AND result_available = 'Y';");
			
			if($resultCount < $requestCount) {
				$status = 6; /* Partially Available */
			} else {
			
				list($extractCount) = parent::getArray("SELECT count(*) FROM lab_samples where so_no = '$so' and branch = '$bid';");

				if($extractCount == $requestCount) {
					$status = '5';
				} else {
					if($extractCount > 0 && $extractCount < $requestCount) {
						$status = '4'; //Partially Extracted 
					}
				}
			}
			
			parent::dbquery("update ignore so_header set cstatus = '$status' where so_no = '$so' and branch = '$bid';");

		}

		function checkCBCValues($age,$gender,$attr,$result,$machine) {

			switch($machine) {
				case "STAC":
					$fstring = " and `mach` = 'STAC'";
				break;
				case "YUMIZEN":
					$fstring = " and `mach` = 'YUMIZEN'";
				break;
				default:
					$fstring = " and `mach` = 'EAHEALTH'";
				break;

			}

			$att = parent::getArray("SELECT * FROM lab_cbc_defvalues where attribute = '$attr' $fstring;");

			if($age <= 16) {

				if($result < $att['p_min_value']) { return "<font color=red><b>L</b></font>"; }
				if($result > $att['p_max_value']) { return "<font color=red><b>H</b></font>"; }
			
			} else {
				if($gender == 'M') {
					if($result < $att['min_value']) { return "<font color=red><b>L</b></font>"; }
					if($result > $att['max_value']) { return "<font color=red><b>H</b></font>"; }
				} else {
					if($result < $att['f_min_value']) { return "<font color=red><b>L</b></font>"; }
					if($result > $att['f_max_value']) { return "<font color=red><b>H</b></font>"; }
				}
			}
			
		}

		function checkChemValues($age,$gender,$code,$result) {
			
			if($result > 0) {
				$att = parent::getArray("SELECT * FROM lab_testvalues where `code` = '$code';");
				
				//if($age > 0) {

					if($age <= 17) {
						if($result < $att['p_min_value']) { return "<font color=red><b>L</b></font>"; }
						if($result > $att['p_max_value']) { return "<font color=red><b>H</b></font>"; }
					
					} else {
						if($gender == 'M') {
							if($result < $att['min_value']) { return "<font color=red><b>L</b></font>"; }
							if($result > $att['max_value']) { return "<font color=red><b>H</b></font>"; }
						} else {
							if($result < $att['f_min_value']) { return "<font color=red><b>L</b></font>"; }
							if($result > $att['f_max_value']) { return "<font color=red><b>H</b></font>"; }
						}
					}
					
				/* } else {

					if($result < $att['p_min_value']) { return "<font color=red><b>L</b></font>"; }
					if($result > $att['p_max_value']) { return "<font color=red><b>H</b></font>"; }

				} */

				//return $age;
			}
		}

		function getAttribute($code,$age,$gender) {
			
			$att = parent::getArray("SELECT unit,`min_value`,`max_value`,f_min_value,f_max_value,p_min_value,p_max_value FROM lab_testvalues WHERE `code` = '$code';");
			if($age <= 17) {
				if($att['p_min_value'] != '' || $att['p_max_value'] !='') {
					if($att['p_min_value'] == 0) {
						$testAttribute = "< " . $att['p_max_value'] . " " . $att['unit'];	
					} else {
						$testAttribute = $att['p_min_value']	. " - " . $att['p_max_value'] . " " . $att['unit'];	
					}
				} else {
					if($att['min_value'] == 0) {
						$testAttribute = "< " . $att['max_value'] . " " . $att['unit'];	
					} else {
						$testAttribute = $att['min_value']	. " - " . $att['max_value'] . " " . $att['unit'];
					}
				}
			} else {
				if($gender == 'M') {
					if($att['min_value'] == 0) {
						$testAttribute = "< " . $att['max_value'] . " " . $att['unit'];
					} else {
						$testAttribute = $att['min_value']	. " - " . $att['max_value'] . " " . $att['unit'];
					}
				} else {
					if($att['f_min_value'] == 0) {
						$testAttribute = "< " . $att['f_max_value'] . " " . $att['unit'];
					} else {
						$testAttribute = $att['f_min_value']	. " - " . $att['f_max_value'] . " " . $att['unit'];	
					}
				}
			}

			return $testAttribute;
		}

	}
?>