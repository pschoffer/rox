<?php
include "lib/dbaccess.php" ;
require_once "lib/FunctionsTools.php" ;
require_once "lib/FunctionsLogin.php" ;
require_once "layout/Error.php" ;

  if (isset($_GET['action'])) {
    $action=$_GET['action'] ;
  }
  if (isset($_POST['action'])) {
    $action=$_POST['action'] ;
  }

  if (isset($_GET['cid'])) {
      $IdMember=$_GET['cid'] ;
  }
  if (isset($_POST['cid'])) {
      $IdMember=$_POST['cid'] ;
  }
  if (isset($_POST['TextWhere'])) {
      $TextWhere=addslashes($_POST['TextWhere']) ;
  }
  if (isset($_POST['Commenter'])) {
      $TextFree=addslashes($_POST['Commenter']) ;
  }
  if (isset($_POST['Quality'])) {
      $Quality=$_POST['Quality'] ;
  }

	$max=count($_SYSHCVOL['LenghtComments']) ;
	$tt=$_SYSHCVOL['LenghtComments'] ;
	$LenghtComments="" ;
	for ($ii=0;$ii<$max;$ii++) {
	  $var=$tt[$ii] ;
    if (isset($_POST["Comment_".$var])) {
		  if ($LenghtComments!="") $LenghtComments=$LenghtComments."," ;
      $LenghtComments=$LenghtComments.$var ;
    }
	}

	switch($action) {
	  case "logout" :
		  Logout("Main.php") ;
			exit(0) ;
	  case "add" :
      $rWho=LoadRow("select * from members where id=".$IdMember) ;
      $str="select * from comments where IdToMember=".$IdMember." and IdFromMember=".$_SESSION["IdMember"] ; // if there is already a comment find it, we will be do an append
	    $qry=mysql_query($str) ;
	    $TCom=mysql_fetch_object($qry) ;
			$newdate="<font color=gray><font size=1>comment date ".date("F j, Y, g:i a")." (UTC)</font></font><br>" ;
			if (!isset($TCom->id)) {
			  $TextWhere=$newdate.$TextWhere ;
			  $str="insert into comments(IdToMember,IdFromMember,Lenght,Quality,TextWhere,TextFree,created) values (".$IdMember.",".$_SESSION['IdMember'].",'".$LenghtComments."','".$Quality."','".$TextWhere."','".$TextFree."',now())" ;
			}
			else {
			  $TextFree=addslashes($TCom->TextFree)."<hr>".$newdate.$TextWhere."<br>".$TextFree ;
			  $str="update comments set IdToMember=".$IdMember.",IdFromMember=".$_SESSION['IdMember'].",Lenght='".$LenghtComments."',Quality='".$Quality."',TextFree='".$TextFree."' where id=".$TCom->id ;
			}
	    $qry=mysql_query($str) or die("error<br>".$str) ;
			break ;
	}
	

// Try to load the Comments, prepare the layout data
// Try to load the member
	if (is_numeric($IdMember)) {
	  $str="select * from members where id=".$IdMember." and Status='Active'" ;
	}
	else {
		$str="select * from members where Username='".$IdMember."' and Status='Active'" ;
	}

	$m=LoadRow($str) ;

	if (!isset($m->id)) {
	  $errcode="ErrorNoSuchMember" ;
	  DisplayError(ww($errcode,$IdMember)) ;
//		die("ErrorMessage=".$ErrorMessage) ;
		exit(0) ;
	}

	$IdMember=$m->id ; // to be sure to have a numeric ID
  $str="select comments.*,members.Username as Commenter from comments,members where IdToMember=".$IdMember." and members.id=".$_SESSION["IdMember"] ;
	$qry=mysql_query($str) ;
	$TCom=mysql_fetch_object($qry) ;
	
  require_once "layout/AddComments.php" ;
  DisplayAddComments($TCom,$rWho->Username,$IdMember) ; // call the layout

?>