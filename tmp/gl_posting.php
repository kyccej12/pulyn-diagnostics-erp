<?php
    include("../handlers/_generics.php");
    //set_time_limit(0);
   // ini_set("memory_limit",0);
    ini_set("max_execution_time",-1);

    //ini_set("display_errors","On");

    $con = new _init;
	
	$dtf = $con->formatDate($_GET['dtf']);
	$dt2 = $con->formatDate($_GET['dt2']);

    $first = $con->dbquery("delete from acctg_gl where doc_type = 'OR' and doc_date between '$dtf' and '$dt2';");
    $second = $con->dbquery("delete from acctg_gl where doc_type = 'SOA' and doc_date between '$dtf' and '$dt2';");

    //echo "SELECT soa_no AS doc_no, soa_date AS doc_date, date_format(soa_date,'%Y') as cy, customer_code, remarks, amount as amount_due FROM soa_header WHERE `status` = 'Finalized' AND soa_date BETWEEN '$dtf' AND '$dt2';";

    /* SOA POSTING */
    // echo "SELECT soa_no AS doc_no, soa_date AS doc_date, date_format(soa_date,'%Y') as cy, customer_code, remarks, amount as amount_due FROM soa_header WHERE `status` = 'Finalized' AND soa_date BETWEEN '$dtf' AND '$dt2';";
    
    if($second) {
        $sQuery = $con->dbquery("SELECT soa_no AS doc_no, soa_date AS doc_date, date_format(soa_date,'%Y') as cy, customer_code, remarks, amount as amount_due FROM soa_header WHERE `status` = 'Finalized' AND soa_date BETWEEN '$dtf' AND '$dt2';");
        while($sRow = $sQuery->fetch_array()) {
            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$sRow[cy]','$sRow[doc_no]','$sRow[doc_date]','SOA','$sRow[customer_code]','1','10102','$sRow[amount_due]','$sRow[doc_no]','$sRow[doc_date]','SOA','','');");
            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$sRow[cy]','$sRow[doc_no]','$sRow[doc_date]','SOA','$sRow[customer_code]','1','60105','$sRow[amount_due]','$sRow[doc_no]','$sRow[doc_date]','SOA','','');");
            echo "POSTING SOA # $sRow[doc_no] -> DATD # $sRow[doc_date]<br/>";
        }
    }

    if($first) {
        $b = $con->dbquery("SELECT doc_no, or_no, doc_date, DATE_FORMAT(doc_date,'%Y') AS cy, customer_code, sc_discount, cash_tendered-change_due as cash, ewt, amount_due, remarks FROM or_header WHERE `status` = 'Finalized' and doc_date between '$dtf' and '$dt2';");
        while($a = $b->fetch_array()) {

            /* DEBIT SIDE */
            if($a['amount_due'] > 0) {
                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','10102','$a[amount_due]','$a[or_no]','$a[doc_date]','OR','',');");
            }

            if($a['ewt'] > 0) {
                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','10403','$a[ewt]','$a[or_no]','$a[doc_date]','OR','','');");
            }

            if($a['sc_discount'] > 0) {
                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','60403','$a[sc_discount]','$a[or_no]','$a[doc_date]','OR','','');");
            }
            

            /* CREDIT SIDE */
            $c = $con->dbquery("SELECT a.doc_no, IF(b.rev_acct = '', '60101',b.rev_acct) AS rev_acct, SUM(amount_due) AS amount FROM or_details a LEFT JOIN services_master b ON a.code = b.code where a.doc_no = '$a[doc_no]' GROUP BY a.doc_no, b.rev_acct;");
            while($d = $c->fetch_array()) {
                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','$d[rev_acct]','$d[amount]','$a[or_no]','$a[doc_date]','OR','','');");
            }   

            /* Cost of Sales Side */
            $e = $con->dbquery("SELECT a.doc_no, a.code, b.with_subtests, sum(ROUND(a.qty * b.unit_cost,2)) as unit_cost FROM or_details a LEFT JOIN services_master b ON a.code = b.code WHERE a.doc_no = '$a[doc_no]' GROUP BY a.code, a.doc_no;");
            while($f = $e->fetch_array()) {

                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','10301','$f[unit_cost]','$a[or_no]','$a[doc_date]','OR','','');"); 
                $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','OR','$a[customer_code]','1','70101','$f[unit_cost]','$a[or_no]','$a[doc_date]','OR','','');"); 
                
            }

            echo "POSTING DOC # $a[doc_no] -> OR # $a[or_no]<br/>";

        }
    }  


   
    


?>