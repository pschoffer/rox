<?php
require_once "lib/init.php";
include "layout/index.php";

if (GetParam("IndexMicha","yes")=="") {
  DisplayIndex();
  exit(0) ;
} 

if (IsLoggedIn()) {
  DisplayIndexLogged($_SESSION["Username"]);
}
else {
  DisplayNotLogged();
}
?>
