<?php

set_include_path(get_include_path() . PATH_SEPARATOR . "/public/vhost/g/gutenberg/dev/private/lib/php");
include_once ("pgcat.phh");
include_once ("sqlform.phh");
include_once ("mn_relation.phh");

$db = $config->db ();
$db->logger = new logger ();

getint ("fk_books");
getstr ("fk_loccs");

$locc_name = "Unknown";
if ($db->Exec("select locc from loccs where pk = '$fk_loccs'")) {
#it will make life nicer if the message includes *what* LoC was linked.
  $locc_name =  $db->Get("locc");
 }

if (isupdatemode ("add")) {
  if ($db->Exec ("insert into mn_books_loccs (fk_books, fk_loccs) " . 
		 "values ($fk_books, '$fk_loccs')")) {
    $msg = "msg=LoCC '$fk_loccs : $locc_name' linked!";
  } else {
    $msg = "errormsg=Could not link LoCC '$fk_loccs : $locc_name'!";
  }
}

if (isupdatemode ("delete")) {
  if ($db->Exec ("delete from mn_books_loccs " . 
		 "where fk_books = $fk_books and fk_loccs = '$fk_loccs'")) {
    $msg = "msg=LoCC '$fk_loccs : $locc_name' unlinked!";
  } else {
    $msg = "errormsg=Could not unlink LoCC '$fk_loccs : $locc_name'!";
  }
}

if (isupdate ()) {
  header ("Location: book?mode=edit&fk_books=$fk_books&$msg");
  return;
}

$db->Exec("select text from attributes where fk_books = $fk_books " .
	  "and fk_attriblist=245");
$book_name=$db->Get("text");

pageheader ($caption = MNCaption ("LoC Class", "'$book_name'"));

class ListLoccsTable extends ListTable {
  function __construct () {
    global $fk_books;
    $this->AddColumn ("<a href=\"mn_books_loccs?mode=add&step=update&" . 
		      "fk_books=$fk_books&fk_loccs=#pk#\">Link</a>", "", null, "1%");
    $this->AddColumn("#pk#", "Code", "narrow");
    $this->AddSimpleColumn ("locc", "LoC Class");
  }
}

if (isfirstmode ("add")) {
  $f->OutputFormHeader ();
  echo ("  <p>Please enter the first few characters of " . 
	"the LoC class description (at least one). Use * as wildcard.</p>\n" .
	"  <input type=\"text\" name=\"filter\" value=\"$filter\"/>\n");
  form_submit ("Search");
  
  form_relay ("mode");
  form_relay ("fk_books");
  form_relay ("fk_loccs");
  form_close ();

  if ($filter != "") {
    $filter = preg_replace ('/\*/', '%', $filter);
    $db->exec ("select * from loccs where locc like '$filter%' order by locc;");
    $table = new ListLoccsTable ();
    $table->PrintTable ($db, $caption);
  }
}

if (isfirstmode ("delete")) {

  $f->Hidden ("fk_books");
  $f->Hidden ("fk_loccs");
  $f->SubCaption ("You are about to unlink LoCC '$fk_loccs : $locc_name' from " .
		  "'$book_name'.");
  $f->SubCaption ("Press the '$caption' button to continue or " . 
		  "hit the back button on your browser to dismiss.");
  $f->Output ($caption, $caption);
} 

pagefooter ();

?>
