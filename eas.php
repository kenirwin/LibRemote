<?php
//extract ($_REQUEST);
include('./include/scripts_2019.php');
Debug();
require_once('./include/FlatfileSearch.class.php');
//include('./include/search_form_torch.php');
include ('./include/config.php');

$db = new FlatfileSearch($conf);
$table = 'eas_index';
$params = ['orderby' => 'year', 'direction' => 'DESC', 'limit'=>'0,1'];
$db->distinctValues('eas_index','year',$params);
$end_date = $db->rows[0];


include('./include/search_form_eas.php');

// echo "initial: $search<BR>";

/*KEN - START HERE */
$browse = $_REQUEST['browse'];
if (isset($browse)) {
    if (($browse == "author")|| ($browse == "year")|| ($browse=="genre")){
        $params = ['orderby' => $browse];
        if ($browse == 'year') {
            $params['direction'] = 'DESC';
        }
        $db->distinctValues('eas_index',$browse,$params);
        print_r($db->rows);
    }
}
    /*

    if ($browse == "year") { $direction = "DESC"; }
    else { $direction = 'ASC'; }
    $sql_conf['retrieve'] = [$browse];
    $sql_conf['orderby'] = $browse .' '.$direction;
    $db->booleanAnd('eas_index','',['year'],$sql_conf);
    print_r($db);
    $query = "SELECT DISTINCT $browse FROM eas_index order by $browse $direction";
  }
  elseif ($browse=="title") {
    $query = "SELECT title FROM eas_index order by title";
  }
  //  print "QUERY: $query";
  $results = mysql_query ("$query", $db);
  while ($myrow = mysql_fetch_row ($results)) {
    if (($browse == "author")|| ($browse == "year") || ($browse == "title")){
      $srch_str = preg_replace ("|[^A-Za-z0-9]+|", "+", "$myrow[0]");
      print "<BR><a href=\"$this_file?search=$srch_str&fields=$browse#results\">$myrow[0]</a>\n";
    } // end if browse = author || year
    elseif ($browse == "genre") {
      print "<BR><a href=\"$this_file?search=&genre[]=$myrow[0]#results\">$myrow[0]</a>\n";
    }
  }
}
    
    elseif (isset($search)||isset($genre)) {
        $search = split ("[ ]+", $search);
  if (isset($bool)) {} else { $bool="and"; }
  if (($fields == "any")||($fields=="")) { $fields = array ("title","author","year"); }
 else { $fields = array ("$fields"); }

 $size = sizeof($fields);
 $j = 0;
 foreach ($search as $item) { 
   for ($i=0; $i<$size; $i++) {
     $temp[$i] = "$fields[$i] like '%$item%'";
   } // End for each field
   $temp_str = join (" or ", $temp);
   $disp_terms = join (" $bool ", $search);
   $sub_clause[$j] = "($temp_str)";
   $j++;
 }

 $searchstring = join (" $bool ", $sub_clause);
 
 if ((sizeof($genre) == 0) || ($genre[0]== "any") || (! ($genre))) {
   $genre = array ("essay", "poem", "artwork", "fiction","play","review");
 }
 // print "GENRE: $genre<P>\n";
 
 $size = sizeof($genre);
 foreach ($genre as $item) {
   for ($i=0; $i<$size; $i++) {
     $gtemp[$i] = "genre like '%$genre[$i]%'";
   } // End for each field
   $genre_str = join (" or ", $gtemp);
 }
 $searchstring .= " and ($genre_str)";

 // print "SEARCHSTR: $searchstring<P>\n";

$search = "'%$search%'";
//echo "revised: $search";

$q = "SELECT * FROM eas_index where $searchstring ORDER BY year,author,title";
// $result = mysql_query("SELECT * FROM eas_index where $searchstring ORDER BY year,author,title",$db);
$result = mysql_query($q);
//print "<p>$q</p>\n";

 $count = mysql_num_rows($result);
 print "<h3>$count Results</h3>\n";
 if ($count > 0) {
 
 echo "<table border=0 cellspacing=10>\n";
 echo "<tr><th align=left>Author</th><th align=left>Title</th><th align=left>Genre</th><th align=left>Volume (Year)</th> <th>Pages</th>\n";
	while ($myrow = mysql_fetch_assoc($result)) {
	  extract($myrow);
	  echo "<tr><td>$author</td> <td>$title</td> <td>$genre</td> <td>$volume ($year)</td> <td>$page</td></tr>\n";
	}

	echo "</table>\n";
 } // end if countable results
} // end if search

	?>
    */




