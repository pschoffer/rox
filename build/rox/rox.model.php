<?php
/*

Copyright (c) 2007 BeVolunteer

This file is part of BW Rox.

BW Rox is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

BW Rox is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/> or 
write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330, 
Boston, MA  02111-1307, USA.

*/
/**
 * rox model
 *
 * @package rox
 * @author Felix van Hove <fvanhove@gmx.de>
 */
class Rox extends PAppModel {
    protected $dao;
    
    // supported languages for translations; basis for flags in the footer
	private $_langs = array();
    
	/**
	 * @see /htdocs/bw/lib/lang.php
	 */

    public function __construct() {
        parent::__construct();
        
        // TODO: it is fun to offer the members the language of the volunteers, i.e. 'prog',
        // so I don't make any exceptions here; but we miss the flag - the BV flag ;-)
        // TODO: is it consensus we use "WelcomeToSignup" as the decision maker for languages?
		// TODO : improvment this will be better not to query for WelcomeToSignup word at each time this is constructed
        $query = "SELECT `ShortCode` FROM `words`
		WHERE code = 'WelcomeToSignup'";
        $result = $this->dao->query($query);
        while ($row = $result->fetch(PDB::FETCH_OBJ)) {
            $this->_langs[] = $row->ShortCode;
        }   

		global $i_am_the_mailbot,$_SYSHCVOL;
		if ('auto' == PVars::getObj('db')->dbupdate &&
			!(isset($_SYSHCVOL['NODBAUTOUPDATE']) ? $_SYSHCVOL['NODBAUTOUPDATE'] : true) &&
			!(isset($i_am_the_mailbot) ? $i_am_the_mailbot : false)		) {
			
			require_once "../././htdocs/bw/lib/dbupdate.php";
			DBUpdateCheck();
//			die("<br />Please refresh again now, database has been updated") ;
		}
	
    }// end of __construct
    
    
    
    /**
     * set defaults
     * TODO: check: how do we replace the files base.php and page.php? do we need a
     * replacement at all?
     * @see loadDefault in /build/mytravelbook/mytravelbook.model.ctrl
     * @see __construct in /build/rox/rox.model.ctrl
     * @param
     * @return true
     */
    public function loadDefaults()
    {
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = 'en';
        }
        PVars::register('lang', $_SESSION['lang']);
        
        if (file_exists(SCRIPT_BASE.'text/'.PVars::get()->lang.'/base.php')) {
	        $loc = array();
	        require SCRIPT_BASE.'text/'.PVars::get()->lang.'/base.php';
	        setlocale(LC_ALL, $loc);
	        require SCRIPT_BASE.'text/'.PVars::get()->lang.'/page.php';
        }
        
        return true;
    }
    
    /**
     * @param string $lang short identifier (2 or 3 characters) for language
     * @return boolean if language is supported true, otherwise false
     */
    public function isValidLang($lang)
    {
        return in_array($lang, $this->_langs);
    }
    
    /**
     * @param
     * @return associative array mapping language abbreviations to 
     * 			long, English names of the language
     */
    public function getLangNames()
    {
        
        $l =  '';
		foreach ($this->_langs as $lang) {
		    $l .= '\'' . $lang . '\',';
		}
		$l = substr($l, 0, (strlen($l)-1));
		
        $query = '
SELECT `EnglishName`, `ShortCode`
FROM `languages`
WHERE `ShortCode` in (' . $l . ')
		';
        $result = $this->dao->query($query);
        
        $langNames = array();
        while ($row = $result->fetch(PDB::FETCH_OBJ)) {
            $langNames[$row->ShortCode] = $row->EnglishName;
        }
        return $langNames;
    }
    
    public function getNewMessagesNumber($_idUser)
    {
        $query = '
SELECT COUNT(*) AS n
FROM `messages`
WHERE `IdReceiver` = ' . $_idUser . '
AND `Status` = \'Sent\'
AND (NOT FIND_IN_SET(\'receiverdeleted\', `DeleteRequest`))
AND `WhenFirstRead` = 0';
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
		return $record->n;
    }


    
    /**
     * Returns the number of people due to be checked to become a member
     * of BW. The number depends on the scope of the person logged on.
     *
		 * $_AccepterScope="" is an optional value for accepter Scope which can be used for performance if it was already fetched from database
     * @return integer indicating the number of people waiting acceptance
     */
    public function getNumberPersonsToBeAccepted($_AccepterScope="")
    {
		
		 		if ($_AccepterScope!="") {
        		 $AccepterScope=$_AccepterScope ;
				}
				else {
        		 $R = MOD_right::get();
        		 $AccepterScope=$R->RightScope('Accepter');
				}
				if ($AccepterScope=="") return 0 ;

        if (($AccepterScope == "\"All\"") or ($AccepterScope == "All") or ($AccepterScope == "'All'")) {
           $InScope = " /* All countries */";
        } else {
          $InScope = "AND countries.id IN (" . $AccepterScope . ")";
        }
         $query = '
SELECT SQL_CACHE COUNT(*) AS cnt
FROM members, countries, cities
WHERE  members.Status=\'Pending\'
AND cities.id=members.IdCity
AND countries.id=cities.IdCountry ' . $InScope.' /* Model rox.model->getNumberPersonsToBeAccepted ' ;
		if (isset($_SESSION['Username'])) $query.=$_SESSION['Username'] ;
		$query.=' */';
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
        return $record->cnt;
    }
    
    /**
     * Returns the number of people due to be checked to problems or what.
     * The number depends on the scope of the person logged on.
     *
		 * $_AccepterScope="" is an optional value for accepter Scope which can be used for performance if it was already fetched from database
     * @return integer indicating the number of people in need to be checked
     */
    public function getNumberPersonsToBeChecked($_AccepterScope)
    {
		 		if ($_AccepterScope!="") {
        		 $AccepterScope=$_AccepterScope ;
				}
				else {
        		 $R = MOD_right::get();
        		 $AccepterScope=$R->RightScope('Accepter');
				}
				if ($AccepterScope=="") return 0 ;
        // FIXME: this if clause indicates a problem, doesn't it???
        // But you need database access to solve it.
        if (($AccepterScope == "\"All\"") or ($AccepterScope == "All") or ($AccepterScope == "'All'")) {
           $InScope = " /* All countries */";
        } else {
          $InScope = "AND countries.id IN (" . $AccepterScope . ")";
        }
        $query = '
SELECT SQL_CACHE COUNT(*) AS cnt
FROM pendingmandatory, countries, cities
WHERE pendingmandatory.Status=\'Pending\'
AND cities.id=pendingmandatory.IdCity
AND countries.id=cities.IdCountry ' . $InScope;
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
        return $record->cnt;
    }
    
    /**
     * Returns the number of people due to be checked to problems or what.
     * The number depends on the scope of the person logged on.
     *
     * @return integer indicating the number of people wiche need to be accepted 
		 * in a Group if the current member has right to accept them
     */
    public function getNumberPersonsToAcceptInGroup($GroupScope)
    {
        // FIXME: this if clause indicates a problem, doesn't it???
        // But you need database access to solve it.
				$where="" ;
				if ($GroupScope!='"All"') {
				 		 $tt=explode(",",$GroupScope) ;
						 $where="(" ;
						 foreach ($tt as $Scope) {
						 				 if ($where!="(") {
										 		$where.="," ;
										 }
										 $where=$where.$Scope;
						 }
						 $where=" and `groups`.`Name` in " .$where.")" ;
				}
        $query = 'SELECT SQL_CACHE COUNT(*) AS cnt FROM `membersgroups`,`groups` where `membersgroups`.`Status`="WantToBeIn" and `groups`.`id`=`membersgroups`.`IdGroup`'.$where ;
//	 die($query) ;
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
        if (isset($record->cnt)) {
					 return $record->cnt;
				}
				else {
					 return(0) ;
				}
    } // end of getNumberPersonsToAcceptedInGroup

    /**
     * Returns the number of messages, which should be checked.
     *
     */
    public function getNumberMessagesToBeChecked()
    {
        $query = '
SELECT COUNT(*) AS cnt
FROM messages
WHERE Status=\'ToCheck\'
AND messages.WhenFirstRead=\'0000-00-00 00:00:00\'';
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
        return $record->cnt;
    }
    
    /**
     * Returns the number of spam messages
     *
     */
    public function getNumberSpamToBeChecked()
    {
        $query = '
SELECT COUNT(*) AS cnt
FROM messages, members AS mSender, members AS mReceiver
WHERE mSender.id=IdSender
AND messages.SpamInfo=\'SpamSayMember\'
AND mReceiver.id=IdReceiver
AND mSender.Status=\'Active\'';
        $result = $this->dao->query($query);
        $record = $result->fetch(PDB::FETCH_OBJ);
        return $record->cnt;
    }

	
	/**
	* return lat/long for all cities with at least one member
	*
	**/
	public function getAllCityLatLong()
	{
	$query= ' 
		SELECT latitude,longitude
		FROM members, cities
		WHERE cities.id=members.IdCity
		AND members.Status=\'Active\'
		ORDER BY members.id desc limit 20';
	$s = $this->dao->query($query);
	if (!$s) {
	throw new PException('Could not retrieve lat/long for cities!');
	}
	$result = array();
	while ($row = $s->fetch(PDB::FETCH_OBJ)) {
		$result[] = $row;
	}
	return $result;		
	}
	
	
	    // * Retrieve the last accepted profile with a picture 
     // * COPIED FROM VISITS - MODULE
     // */
    // public function getMembersStartpage($limit = 0)
    // {
// // retrieve the last member
        // $query = '
// SELECT SQL_CACHE `members`.*,`membersphotos`.`FilePath` AS photo,`membersphotos`.`id` AS IdPhoto,`countries`.`Name` AS countryname 
// FROM 	`members`,`memberspublicprofiles`,`cities`
// WHERE `membersphotos`.`IdMember`=`members`.`id`
// AND `membersphotos`.`SortOrder`=0
// AND `members`.`Status`=\'Active\'
// AND `memberspublicprofiles`.`IdMember`= `members`.`id`
// AND `members`.`IdCity`=`cities`.`id`
// AND `countries`.`id`=`cities`.`IdCountry` 
// ORDER BY `members`.`id` desc limit '.(int)$limit
// ;
        // $s = $this->dao->query($query);
            // if (!$s) {
                 // throw new PException('Cannot retrieve last member with photo!');
            // }
        // $members = array();
        // while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            // array_push($members, $row);
        // }
        // return $members ;
    // } // end of	getMembersStartpage
	
// retrieve the number of members for each country
	public function getMembersPerCountry() {
		$query = 'select countries.Name 
		as countryname,count(*) 
		as cnt from members,countries,cities where members.Status="Active" 
		and members.IdCity=cities.id 
		and cities.IdCountry=countries.id group by countries.id  order by cnt desc';
		$s = $this->dao->query($query);
		if (!$s) {
			throw new PException('Could not retrieve number of members per Country!');
		}
		$result = array();
		$i=0;
		while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			if ($i<6) {
				$result[$row->countryname] = $row->cnt;
			}
			else {
				if (isset($result["Others"])) {
					$result["Others"] = $result["Others"] + $row->cnt;
				}
				else { 
					$result["Others"] = $row->cnt;
				}
			}
			$i++;
		}
		return $result;		
	}


//retrieve the last login date from the db


	public function getLastLoginRank() {
		$query = 'select TIMESTAMPDIFF(DAY,members.LastLogin,NOW()) AS logindiff, COUNT(*) AS cnt FROM members 
		WHERE TIMESTAMPDIFF(DAY,members.LastLogin,NOW()) >= 0
		GROUP BY logindiff 
		ORDER BY logindiff ASC';
		$s = $this->dao->query($query);
		if (!$s) {
			throw new PException('Could not retrieve last login listing!');
		}
		$result = array();
		while ($row = $s->fetch(PDB::FETCH_OBJ)) {
					$result[$row->logindiff] = $row->cnt;
		}
		return $result;		
	}
	
	public function getLastLoginRankGrouped() {
		$query = 'select TIMESTAMPDIFF(DAY,members.LastLogin,NOW()) AS logindiff, COUNT(*) AS cnt FROM members 
		WHERE TIMESTAMPDIFF(DAY,members.LastLogin,NOW()) >= 0
		GROUP BY logindiff 
		ORDER BY logindiff ASC';
		$s = $this->dao->query($query);
		if (!$s) {
			throw new PException('Could not retrieve last login listing!');
		}
		$result = array();

		$result['1 day'] = 0;
		$result['1 week'] = 0;
		$result['1-2 weeks'] = 0;
		$result['2-4 weeks'] = 0;
		$result['1-3 months'] = 0;
		$result['3-6 months'] = 0;		
		$result['longer'] = 0;		
		
		
		while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			if ($row->logindiff==1) {
					$result['1 day'] = $result['1 day'] + $row->cnt;
			} elseif ($row->logindiff<=7) {
					$result['1 week'] = $result['1 week'] + $row->cnt;
			} elseif ($row->logindiff<=14) {
					$result['1-2 weeks'] = $result['1-2 weeks'] + $row->cnt;
			} elseif ($row->logindiff<=30) {
					$result['2-4 weeks'] = $result['2-4 weeks'] + $row->cnt;
			} elseif ($row->logindiff<=90) {
					$result['1-3 months'] = $result['1-3 months'] + $row->cnt;
			} elseif ($row->logindiff<=182) {
					$result['3-6 months'] = $result['3-6 months'] + $row->cnt;
			} else {
					$result['longer'] =  $result['longer'] + $row->cnt;		
			}
		}
		return $result;		
	}	

	
// retrieve the stats from db - all time weekly average
	public function getStatsLogAll() {
		$query = 'select AVG(NbActiveMembers) AS NbActiveMembers,AVG(NbMessageSent) AS NbMessageSent,AVG(NbMessageRead) AS NbMessageRead,AVG(NbMemberWithOneTrust) AS NbMemberWithOneTrust,AVG(NbMemberWhoLoggedToday) AS NbMemberWhoLoggedToday,created,YEARWEEK(created) AS week  
		FROM stats
		GROUP BY week ';
		$s = $this->dao->query($query);
		if (!$s) {
			throw new PException('Could not retrieve statistics table!');
		}
		$result = array();
		while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			$result[] = $row;
		}
		return $result;		
	}
	
// retrieve the stats from db - daily for last 2months
	public function getStatsLog2Month() {
		$query = 'select * 
		FROM stats
		ORDER BY id DESC
		LIMIT 0,60';
		$s = $this->dao->query($query);
		if (!$s) {
			throw new PException('Could not retrieve statistics table!');
		}
		$result = array();
		while ($row = $s->fetch(PDB::FETCH_OBJ)) {
			$result[] = $row;
		}
		$result = array_reverse($result);
		return $result;		
	}	


    /**
     * Returns true if member belongs to group volunteer
     *
     */
	 
    public function isVolunteer($_idUser)
    {
        $query = '
SELECT *
FROM membersgroups
WHERE membersgroups.IdGroup = 17
AND membersgroups.Status="In" 
AND membersgroups.IdMember='. $_idUser;
        $result = $this->dao->query($query);
		$record = $result->fetch(PDB::FETCH_OBJ);
		if (!empty($record)) {
		return true;
		}
		else return false;
    }

    public static function getMember_userId($userId)
    {
        if ($row = self::_getDAO()->query(
            'SELECT SQL_CACHE Username '.
            'FROM members '.
            "WHERE id='$id' "
        )->fetch(PDB::FETCH_OBJ)) {
            return new MOD_member($id, $row->Username);
        } else {
            return 0;
        }
    }
    
    /**
		
     * Retrieve the last accepted profile with a picture 
     * COPIED FROM VISITS - MODULE
     */
    public function getMembersStartpage($limit = 0)
    {
// retrieve the last member
        $query = '
SELECT SQL_CACHE `members`.*,`membersphotos`.`FilePath` AS photo,`membersphotos`.`id` AS IdPhoto,`countries`.`Name` AS countryname 
FROM 	`members`,`memberspublicprofiles`,`membersphotos`,`cities`,`countries` 
WHERE `membersphotos`.`IdMember`=`members`.`id`
AND `membersphotos`.`SortOrder`=0
AND `members`.`Status`=\'Active\'
AND `memberspublicprofiles`.`IdMember`= `members`.`id`
AND `members`.`IdCity`=`cities`.`id`
AND `countries`.`id`=`cities`.`IdCountry` 
ORDER BY `members`.`id` desc limit '.(int)$limit
;
        $s = $this->dao->query($query);
            if (!$s) {
                 throw new PException('Cannot retrieve last member with photo!');
            }
        $members = array();
        while ($row = $s->fetch(PDB::FETCH_OBJ)) {
            array_push($members, $row);
        }
        return $members ;
    } // end of	getMembersStartpage
    
    /**
     * Returns an array with the mist of X latest donations (all donation in case the current user has Treasurer rights)
     *
     */    

    public function getDonations() {
		$TDonations = array() ;
        $R = MOD_right::get();
        $hasRight = $R->hasRight('Treasurer');
	  	if ($hasRight) {
		   $query = "select * from donations order by created desc" ;
		}
		else {
		   $query = "select * from donations order by created desc limit 10" ;
		}
        $result = $this->dao->query($query);
        while ($row = $result->fetch(PDB::FETCH_OBJ)) {
			  array_push($TDonations, $row);
		}
		return($TDonations) ;
    }
    
    /**
     * Returns true if member belongs to group volunteer
     *
     */
	 
    public function returnFromPayPal()
    {    
/*    
//The donation returns an url as the following
http://www.bewelcome.org/bw/donations2.php?action=done&tx=0ME24142PE152304A&st=Completed&amt=5.00&cc=EUR&cm=&item_number=&sig=hYUTlSOjBeJvNqfFqc%252fZbrBA4p6c%252fe6EErVp1w18eOBR96p6hzzenPysL%252bFVPZi8YEcONFovQmYn%252b6QF%252fBYoVhGMoaQJCxBQh%252bLAlC0TdgeScs1skk0%252bpY6SyoC%252fNCV1ou69zWRrhDrtsa4SUHibLD%252f1RwGg43iaZjPhB24I6lg%253d
*/
         // save the first immediate return values 		
         $tx=$tx_token = $_GET['tx'];
         $payment_amount=$_GET['amt'] ;
         $payment_currency=$_GET['cc'] ;

         // read the post from PayPal system and add 'cmd'
         $req = 'cmd=_notify-synch';

         $auth_token ="token is not set" ;
         if (isset($_SYSHCVOL['paypal_authtoken'])) {
            $auth_token =$_SYSHCVOL['paypal_authtoken'] ;
         }
         $req .= "&tx=$tx_token&at=$auth_token";

/*			 
         foreach ($_POST as $key => $value) {
                 $value = trim(urlencode(stripslashes($value)));
                 echo "_POST[", $key,"]=",$value,"<br />";
        }

         foreach ($_GET as $key => $value) {
                 $value = trim(urlencode(stripslashes($value)));
                 echo "_GET[", $key,"]=",$value,"<br />";
        }
*/
         // post back to PayPal system to validate
         $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
         $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
         $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
         $fp = fsockopen ('www.paypal.com', 80, $errno, $errstr, 30);
         // If possible, securely post back to paypal using HTTPS
         // Your PHP server will need to be SSL enabled
         // $fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
             

         if (!$fp) {
            MOD_log::get()->write("Failed to connect to paypal for return value while checking confirmation on paypal","donation") ;
            $error = "A problem occured while checking confirmation with paypal";
            return $error;
         } 
         else {
             fputs ($fp, $header . $req); // sending the query to paypal
             // read the body data 
             $res = '';
             $headerdone = false;
             while (!feof($fp)) { // while result not received
                $line = fgets ($fp, 1024); // reading the result
                if (strcmp($line, "\r\n") == 0) {
                    // read the header
                    $headerdone = true;
                }
                else if ($headerdone) {
                     MOD_log::get()->write("Requesting paypal for confirmation (\$tx_token=".$tx_token.") [".$line."]","donation") ;
                     // header has been read. now read the contents
                     $res .= $line;
                }
             }

             // parse the data to read the return variables by paypal
             $lines = explode("\n", $res);
             $keyarray = array();
             if (strcmp ($lines[0], "SUCCESS") == 0) {
                for ($i=1; $i<count($lines);$i++){ // Retrieve the parameters
                    if (strpos($lines[$i],"=")) {
                       list($key,$val) = explode("=", $lines[$i]);
                    }
                    $keyarray[urldecode($key)] = urldecode($val);
                }
                
                $ItsOK=true ;
                
                $txn_id = $keyarray['txn_id'];

                if ($payment_amount!=$keyarray['mc_gross']) { // If amount differs we will not continue
                   $ItsOK=false ;
                   MOD_log::get()->write("Problem for \$payment_amount expected=".$payment_amount." return par paypal confirmation=".$keyarray['mc_gross'],"donation") ;
                }
                if ($payment_currency!=$keyarray['mc_currency']) { // If currency differs we will not continue
                   $ItsOK=false ;
                   MOD_log::get()->write("Problem for \$payment_currency expected=".$payment_currency." return par paypal confirmation=".$keyarray['mc_currency'],"donation") ;
                }
                
                if ($keyarray['txn_id']!=$tx) { // If control code differs we will not continue
                   $ItsOK=false ;
                   MOD_log::get()->write("Problem for txn_id expected=".$tx." return par paypal confirmation=".$keyarray['txn_id'],"donation") ;
                }
                
                if (!$ItsOK) { 
                    $error = "We detected a problem while checking the success of your donation on paypal";
                    return $error;
                }
                
                $IdMember=0 ; $IdCountry=0 ; // This values will remain if the user was not logged
                if (isset($_SESSION["IdMember"])) {
                    $IdMember=$_SESSION["IdMember"] ;
                    $query = '
SELECT IdCountry
FROM  members,cities
WHERE members.id='.$IdMember.'
AND cities.id=members.IdCity';
                    $result = $this->dao->query($query);
            		$m = $result->fetch(PDB::FETCH_OBJ);
                    $IdCountry=$m->IdCountry ; 
                }

                $referencepaypal=  "ID #".$keyarray['txn_id']." payment_status=".$keyarray['payment_status'] ;
                if ($keyarray['mc_currency']=="USD") {
                   $payment_currency="$" ;
                }
                else if ($keyarray['mc_currency']=="EUR") {
                   $payment_currency="�" ;
                }
                else {
                   $payment_currency=$keyarray['mc_currency'] ;
                }
                
                $receiver_email=$keyarray['payer_email'] ;
                
                // now test if this donation was allready registrated
                $query = '
SELECT *
FROM  donations
WHERE IdMember='.$IdMember.'
AND referencepaypal LIKE "%'.$referencepaypal.'%"';
                $result = $this->dao->query($query);
        		$rr = $result->fetch(PDB::FETCH_OBJ);
        
                if (isset($rr->id)) { // If a previous version was already existing, it means a double signup
                    MOD_log::get()->write("Same Donation Submited several times for ".$keyarray['mc_gross'].$payment_currency." by ".$keyarray['first_name']." ".$keyarray['last_name']."/".$receiver_email." status=".$payment_status." [expected".$_SESSION["PaypalBW_key"]." received=".$tx."]","Donation") ;
                    $error = "Your donation is registrated only once , not need to submit twice ;-)";
                    return $error;
                }
        
                $memo="" ;
                if (isset($keyarray['memo'])) {
                   $memo=$keyarray['memo'] ;
                }
                $query = '
INSERT INTO `donations`
( `IdMember`,`Email`,`StatusPrivate`,`created`,`Amount`,`Money`,`IdCountry`,`namegiven`,`referencepaypal`,`membercomment`,`SystemComment` )
VALUES
('.$IdMember.',"'.$receiver_email.'","showamountonly",now(),'.$payment_amount.',"'.$payment_currency.'",'.$IdCountry.',"'.$keyarray["first_name"].' '.$keyarray["last_name"].'","'.$referencepaypal.'","","Via paypal'.' '.$keyarray["payment_status"].' '.$memo.'")
';
                $this->dao->exec($query);
                MOD_log::get()->write("donation ID #".$referencepaypal." recorded","donation") ;
                fclose($fp) ;
                return;
            } // end if verified
            MOD_log::get()->write("can't find verified in paypal return information for ID #".$tx." recorded","donation");
            $error = "not verified";
            return $error;
    	} // enf if fp
    }
    
}
?>
