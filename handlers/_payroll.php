<?php
	//ini_set("display_errors","On");
	require_once 'initDB.php';
	class payroll extends myDB {
	
		public $cutoff;
		public $ptype;
		public $ndays;
		public $restDays;
		public $baseDays;
		public $dtf;
		public $dt2; 
		public $foy;
		public $bleep;
		public $wom;
		public $dtrCount;
		public $reportingMonth;
		public $reportingYear;
		public $semiRate;
		public $dailyRate;
		public $hrate;
		public $minRate;
		
		public $def_ins;
		public $def_ins_min;
		public $def_ins_max;
		public $def_oas;
		public $def_oas_min;
		public $def_oas_max;
		public $def_ips;
		public $def_ips_min;
		public $def_ips_max;
		public $def_ops;
		public $def_ops_min;
		public $def_ahrs;
		public $def_phrs;
		
		public $ins;
		public $oas;
		public $ips;
		public $ops;
		
		public $late;
		public $ut;
		public $twork;
		public $overtime;
		public $restday;
		public $premium;
		public $htype;
		
		public $vl;
		public $sl;
		public $sil;
		public $absences;
		public $sss_premium;
		public $sss_premium_er;
		public $pg_premium;
		public $pg_premium_er;
		public $ph_premium;
		public $ph_premium_er;
		public $previousTaxable;
		public $onRestDayHoliday;
		
		public function __construct($pid) {
			$a = parent::getArray("select period_start, period_end, date_format(period_end,'%d') as bleep, weekOfMonth, reportingMonth, reportingYear from omdcpayroll.pay_periods where period_id = '$pid';");
			$this->cutoff = $pid;
			$this->dtf = $a['period_start'];
			$this->dt2 = $a['period_end']; 
			$this->bleep = $a['bleep'];
			$this->wom = $a['weekOfMonth'];
			$this->reportingMonth = $a['reportingMonth'];
			$this->reportingYear = $a['reportingYear'];
			
			list($this->foy) = parent::getArray("select date_format('".$this->dt2."','%Y-01-01');");
			list($this->ndays) = parent::getArray("select datediff('".$this->dt2."','".$this->dtf."') + 1;");
		
		}
			
		public function _toHrs($_x) {
			return ROUND($_x / 3600,2);
		}
		
		public function checkHoliday($date,$shift,$area) {
			if($shift == 0) {
				$this->htype = 'RD'; return true;
			} else {
				list($type) = parent::getArray("SELECT DISTINCT `type` FROM (SELECT `date`,IF(`type`=1,'LH','SH') AS `type` FROM omdcpayroll.pay_holiday_nat WHERE `date` = '$date' UNION SELECT `date`,'SH' AS `type` FROM omdcpayroll.pay_holiday_local WHERE `date` = '$date' and `area` = '$area') a limit 1;");
				if($type != '') {
					$this->htype = $type; return true;
				} else { $this->htype = 'NA'; return false; }
			}
		}
		
		public function checkRestDay($day) {
			if($day == "Sun") {	$this->htype = "RD"; return true; } else { $this->htype = "NA"; return false; }
		}
		
		public function getRates($ptype,$rate) {
			if($ptype == 2) {
				$this->dailyRate = $rate;
				$this->hrate = ROUND($this->dailyRate/8,2);
				$this->minRate = ROUND($this->hrate/60,2);
			} else {
				$this->semiRate = ROUND($rate/2,2);
				$this->dailyRate = ROUND(($rate * 12) / 314,2);
				$this->hrate = ROUND((($rate * 12) / 314)/8,2);
				$this->minRate = ROUND($this->hrate/60,2);
			}
		}
		
		public function getTimeDefaults($shift) {
		
			$sqlTxt = "SELECT TIME_TO_SEC(clockin) as def_in, TIME_TO_SEC(clockout) as def_out FROM omdcpayroll.emp_shifts WHERE shift_id = '$shift';";
			
			$a = parent::getArray($sqlTxt);
			$this->def_ins = $a['def_in'];
			$this->def_ins_min = $a['def_in'] - 7200;
			$this->def_ins_max = $a['def_in'] + 7200;
			
		
			$this->def_ops = $a['def_out'];
			$this->def_ops_min = $a['def_out'] - 7200;
		}
		
		public function computeTimeSheets($eid,$date,$shift,$in,$out) {
		
			$late = 0;
			$undertime = 0;

			$this->twork = 0; 
			$this->overtime = 0; 
			$this->premium = 0; 
			$this->late = 0; 
			$this->ut = 0;
			
			if($shift == 0) {
				list($shiftPrevious) = parent::getArray("select shift from omdcpayroll.emp_dtrfinal where `date` < '$date' and emp_id = '$eid' order by `date` desc limit 1;");
				$this->getTimeDefaults($shiftPrevious);
			} else {
				$this->getTimeDefaults($shift);
			}

			
			/* AM Working Hours */
			if($in > 0) {
				if($in < $this->def_ins) { $in = $this->def_ins; }
				if($in > $this->def_ins) { 	$late = $this->_toHrs(($in - $this->def_ins)); } 
			}
			
			/* Compute Overtime */
			if($out > $this->def_ops && $out <= 86400) {
				$this->overtime = $this->_toHrs($out - $this->def_ops);
				if($out > 79200) { $this->premium = $this->_toHrs($out - 79200); } 
			}
				
			/* Compute if Time out is past midnight */
			if($out >= 60 && $out <= 25200) {
				$this->premium = $this->_toHrs(($out+86400) - 64800);
			}					
			
			/* Compute Undertime */
			if($out < $this->def_ops) {
				if($out <= ($this->def_ins + 14400)) {
					$u1 = 4 - ($this->_toHrs($out - $this->def_ins));
					$u2 = 8 - ($this->_toHrs($out - $this->def_ins));
				} else {
					$u1 = 0;
					$u2 = $this->_toHrs($this->def_ops - $out);
				}
				
			}
			
		
			$this->twork = 8 - $late - $u2; 
			if($shift != '0') { $this->late = $late; $this->ut = $u1; }
		}
		
		public function getEmployeeRestDays($eid,$area) {
			
			$this->restDays = 0; $this->onRestDayHoliday = 0;
			
			$rdQuery = parent::dbquery("select `DATE` FROM omdcpayroll.emp_dtrfinal where emp_id = '$eid' and `DATE` between '".$this->dtf."' and '".$this->dt2."' and SHIFT = 0;");
			while($rdRow = $rdQuery->fetch_array()) {
				list($rdCount) = parent::getArray("SELECT COUNT(*) FROM (SELECT `date` FROM omdcpayroll.pay_holiday_nat WHERE `date` = '$rdRow[0]' AND `type` = '2' UNION ALL SELECT DISTINCT `date` FROM omdcpayroll.pay_holiday_local WHERE `date` = '$rdRow[0]' AND `area` = '$area' UNION ALL SELECT DATE FROM omdcpayroll.pay_holiday_nat WHERE `date`= '$rdRow[0]' AND `type` = '1') a;");
				$this->restDays++;
				$this->onRestDayHoliday+=$rdCount;
			}
			
			
			$this->baseDays = $this->ndays - $this->restDays;
		}
		
		public function countHolidays($eid,$area) {
			$this->sholiday = 0; $this->lholiday = 0; $this->holidayCount = 0;
			
			$regQuery = parent::dbquery("select `DATE` FROM omdcpayroll.emp_dtrfinal where emp_id = '$eid' and `DATE` between '".$this->dtf."' and '".$this->dt2."' and SHIFT != 0;");
			while($regRow = $regQuery->fetch_array()) {
				list($sholiday) = parent::getArray("select count(*) FROM (SELECT `date` FROM omdcpayroll.pay_holiday_nat WHERE `date` = '" . $regRow[0] . "' AND `type` = '2' UNION ALL SELECT `date` FROM omdcpayroll.pay_holiday_local WHERE `date`= '" . $regRow[0] . "' AND `area` = '$area') a;");
				list($lholiday) = parent::getArray("select count(*) from omdcpayroll.pay_holiday_nat where `date` = '". $regRow[0] . "' and `type` = '1';");
				$this->holidayCount += ($sholiday + $lholiday);
				$this->sholiday += $sholiday;
				$this->lholiday += $lholiday;
			}
		}
		
		public function getAbsences($eid,$area) {	
			
			$this->countHolidays($eid,$area);
			$this->getEmployeeRestDays($eid,$area);
			
			list($wholeDay) = parent::getArray("SELECT COUNT(*) FROM omdcpayroll.emp_dtrfinal WHERE TOT_WORK > 4 AND EMP_ID = '$eid' AND `DATE` BETWEEN '". $this->dtf ."' AND '". $this->dt2 ."' AND SHIFT != 0;");
			list($halfDay) = parent::getArray("SELECT COUNT(*) / 2 FROM omdcpayroll.emp_dtrfinal WHERE TOT_WORK > 0 AND TOT_WORK <= 4 AND EMP_ID = '$eid' AND `DATE` BETWEEN '". $this->dtf ."' AND '". $this->dt2 ."' AND SHIFT != 0;");
			
			$this->dtrCount = $wholeDay + $halfDay;
			list($sil) = parent::getArray("SELECT ifnull(SUM(`length`),0) FROM omdcpayroll.pay_loa WHERE date_from >= '" . $this->dtf . "' AND date_to <= '" . $this->dt2 . "' AND w_pay = 'Y' AND emp_id = '$eid' and file_status != 'Deleted';");
			$myabsences = $this->baseDays - $sil - $this->dtrCount - $this->holidayCount;
			if($myabsences > 0) { $this->absences = $myabsences; } else { return $this->absences = 0; }	
			
		}
		
		public function checkVL($eid,$credits) {
			list($prev_vl) = parent::getArray("SELECT ifnull(SUM(`length`),0) AS sil FROM omdcpayroll.pay_loa WHERE emp_id = '$eid' and date_to < '" . $this->dtf . "' and date_to >= '". date('Y-01-01') ."' and w_pay = 'Y' and leave_type in ('1','2') and file_status != 'Deleted';");
			if($prev_vl < $credits) {
				$vlBalance = $credits - $prev_vl;	
				list($cur_vl) = parent::getArray("SELECT ifnull(SUM(`length`),0) AS sl FROM omdcpayroll.pay_loa WHERE emp_id = '$eid' and date_to >= '". $this->dtf. "' AND date_to <= '" . $this->dt2 . "' and w_pay = 'Y' and leave_type in ('1','2') and file_status != 'Deleted';");
				if($vlBalance > $cur_vl) { $this->vl = $cur_vl; } else { $this->vl = $vlBalance; }
			} else { $this->vl = 0; }
		}
		
		public function checkSIL($eid) {
			list($sil) = parent::getArray("SELECT ifnull(SUM(`length`),0) AS sil FROM omdcpayroll.pay_loa WHERE emp_id = '$eid' and date_to >= '". $this->dtf. "' AND date_to <= '" . $this->dt2 . "' and w_pay = 'Y' and leave_type not in (1,2) and file_status != 'Deleted';");
			$this->sil = $sil;
		}
		
		public function myPremiums($rate,$eid,$etype,$hdmf,$wSSS,$wPH,$wHDMF) {	
			$this->pg_premium = 0; $this->pg_premium_er = 0; $this->ph_premium = 0; $this->ph_premium_er = 0; $this->sss_premium = 0; $this->sss_premium_er = 0;
			
			if($rate > 1000) {
				if($this->wom == 2) { 
					if($wSSS == 'Y') {
						list($this->sss_premium,$this->sss_premium_er) = parent::getArray("select (ee-mpf_ee) as ee, ((er+ec)-mpf_er) as er from omdcpayroll.sss_table where $rate >= ms_range1 and $rate <= ms_range2;");
					}
					if($wPH == 'Y') { 
						if($rate <= 10000) { $this->ph_premium = '150.00'; } else { if($rate >= 60000) { $this->ph_premium = "900.00"; } else { $this->ph_premium = ROUND((($rate * 0.03)/2),2); }}
						$this->ph_premium_er = $this->ph_premium;
					}
				} else {
					if($wHDMF == 'Y') {	$this->pg_premium_er = 100; if($hdmf == 0 ) { $this->pg_premium = 100;	} else { $this->pg_premium = $hdmf; }}
					
				}
			}
		}
		
		public function getPreviousPays($eid) {
			list($lastPID) = parent::getArray("select period_id from omdcpayroll.pay_periods where reportingMonth='".$this->reportingMonth."' and reportingYear = '".$this->reportingYear."' and weekOfMonth = '1';");
			list($a) = parent::getArray("select gross_pay from omdcpayroll.emp_payslip where emp_id = '$eid' and period_id = '$lastPID';");
			$this->previousTaxable = $a;
		}
		
		
		public function loadLoans($eid,$etype,$area,$dept,$ptype) {
			$r = parent::dbquery("select record_id as loan_id,loan_type, if(dedu_type=3,semi_amrtz,monthly_amrtz) as amrtz, date_loan from omdcpayroll.emp_loanmasterfile where emp_id = '$eid' and '". $this->dtf ."' <= date_add(effective_date,INTERVAL loan_terms MONTH) and '". $this->dt2 ."' >= effective_date and file_status != 'Deleted' and `active` = 'Y' and dedu_type in ('". $this->wom . "','3');");
			if($r) {
				while(list($lid,$ltype,$samt,$d8) = $r->fetch_array(MYSQLI_BOTH)) {
					parent::dbquery("insert ignore into omdcpayroll.emp_deductionmaster (period_id,emp_type,pay_type,emp_id,type,area,dept,ref_id,ref_date,ref_type,amount,posted_by,posted_on) values ('". $this->cutoff ."','$etype','$ptype','$eid','L','$area','$dept','$lid','$d8','$ltype','$samt','$_SESSION[userid]',now());");
			
					list($tLoanApplied) = parent::getArray("select sum(amount) from omdcpayroll.emp_deductionmaster where ref_id = '$lid' and emp_id = '$eid' and `type` = 'L';");
					parent::dbquery("update ignore omdcpayroll.emp_loanmasterfile set amt_paid = 0$tLoanApplied, balance = loan_amt - 0$tLoanApplied where record_id = '$lid' and emp_id = '$eid]';");
				}
			}
		}

		public function getLoans($eid,$pid,$type) {
			$amt = 0;
			list($amt) = parent::getArray("select sum(amount) from omdcpayroll.emp_deductionmaster where period_id = '$pid' and emp_id='$eid' and type = 'L' and ref_type = '$type';");
			return $amt;
		}
		
		public function getOtherLoans($eid,$pid) {
			$amt = 0;
			list($amt) = parent::getArray("select sum(amount) from omdcpayroll.emp_deductionmaster where period_id = '$pid' and emp_id='$eid' and type = 'L' and ref_type not in (1,2,4,7,8,10);");
			return $amt;
		}
		
	}
	
?>