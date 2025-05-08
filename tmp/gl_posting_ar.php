<?php
    include("../handlers/initDB.php");
    set_time_limit(0);
    ini_set("memory_limit",0);

    $con = new myDB;

    $b = $con->dbquery("SELECT soa_no AS doc_no, soa_no AS or_no, soa_date AS doc_date, DATE_FORMAT(soa_date,'%Y') AS cy, customer_code, 0 AS sc_discount, amount, remarks FROM soa_header WHERE `status` = 'Finalized' AND soa_date BETWEEN '2022-07-01' AND '2022-07-22' and balance > 0;");
    while($a = $b->fetch_array()) {

        /* DEBIT SIDE */
        if($a['amount'] > 0) {
            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','SOA','$a[customer_code]','1','10201','$a[amount]','$a[or_no]','$a[doc_date]','OR','','".$con->escapeString($a['remarks'])."');");
        }

        /* CREDIT SIDE */
        $c = $con->dbquery("SELECT a.soa_no AS doc_no, IF(b.rev_acct = '', '60101',b.rev_acct) AS rev_acct, SUM(amount) AS amount FROM soa_details a LEFT JOIN services_master b ON a.code = b.code WHERE a.soa_no = '$a[doc_no]' GROUP BY a.soa_no, b.rev_acct;");
        while($d = $c->fetch_array()) {
            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','SOA','$a[customer_code]','1','$d[rev_acct]','$d[amount]','$a[or_no]','$a[doc_date]','OR','','".$con->escapeString($a['remarks'])."');");
        }   

        /* Cost of Sales Side */
        $e = $con->dbquery("SELECT a.soa_no AS doc_no, a.code, b.with_subtests, SUM(ROUND(a.qty * b.unit_cost,2)) AS unit_cost FROM soa_details a LEFT JOIN services_master b ON a.code = b.code WHERE a.soa_no = '$a[doc_no]' GROUP BY a.code, a.soa_no;");
        while($f = $e->fetch_array()) {

            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,credit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','SOA','$a[customer_code]','1','10301','$f[unit_cost]','$a[or_no]','$a[doc_date]','OR','','".$con->escapeString($a['remarks'])."');"); 
            $con->dbquery("INSERT IGNORE INTO acctg_gl (company,branch,cy,doc_no,doc_date,doc_type,contact_id,acct_branch,acct,debit,ref_no,ref_date,ref_type,cost_center,doc_remarks) VALUES ('1','1','$a[cy]','$a[doc_no]','$a[doc_date]','SOA','$a[customer_code]','1','70101','$f[unit_cost]','$a[or_no]','$a[doc_date]','OR','','".$con->escapeString($a['remarks'])."');"); 
            
        }

        echo "POSTING DOC # $a[doc_no] -> OR # $a[or_no]<br/>";


    }
    

?>