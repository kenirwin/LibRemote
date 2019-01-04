<? 
date_default_timezone_set("America/New_York");
include ("/docs/lib/remote/include/adodb-time.inc.php"); // pre-unix date/time fns

//if (is_array($_SERVER)) { $_SERVER['SCRIPT_NAME'] = $_SERVER['REQUEST_URI']; }// version of PHP doesn't include a PATH_INFO environment variable, but since that var is used all over the place, I'm defining it here. 

//////////////////////////////////////////////////////////////////////////
// Functions:
// 
// array_search_recursive: returns an array of array_search result keys
// ArrayToTable: return ($array[$row][$col] = $cell) as HTML table
//               with or without numeric totals
// Breadcrumb: manages site navigation breadcrumbs at bottom of each page
// Breadcrumb2010: compatible with 2010 university site design
// BuildWhereQuery: builds a Boolean SQL query from search,fields,&stopwords
//                  long function, so kept in another file: build_sql.php
// Debug: invoke the error_reporting settings (default E_ALL)
// DehyphenateISSN: removes hyphens from ISSNs in a mysql table
// EZRA_Locations: returns array $ezra associating loc codes to text
// EZRA_Status_By_Call: returns availability info for call # in EZRA
// EZRA_Status_By_BibRecord: returns availability info for bib# in EZRA
// GetAllColors: returns an array of hex colors ok to read on white background
// GetIncludeContents: returns included file contents, instead of including
// GetDBList: takes array of #s, returns list of databases & desc in dt/dd fmt
// GetJournalByISSN: takes an ISSN (with or w/o hyphen); returns journal title
// GetJournalByURL : takes a URL as an argument, returns journal title
// GetCampusLocation: give user's campus location based on IP address
// GetOrdinal: take integer/text number & return as "21st","13th", etc
// Gooduser : confirm lastname v. barcode and ptype
// JoinWithAnd: return an array as "thing one, thing two, and thing three"
// InCallOrder : returns true if two arguements are in LC call # order
// IsEven: returns true if argument is an even number
// IsOdd:  returns true if argument in an odd number
// MysqlResultsTable: returns an HTML table with the results of a query
// OnCampus: returns true if user has an IP in the range 136.227.*.*
// Print_rr: puts print_r() in [pre] tags for screen readability
// PurifyFile: convert HTML character entities to UTF-8, and other stuff
// SafeStrToTime: unix timestamps for 1900-1969
// SendSMS: send a text message
// SortCall: sorts call# array; call as: usort ($array, "SortCall");
//    SortCall is deprecated; use SortLC instead
// Sql2Adodb: pre-Unix-epoch sql-dates reformatted in date() format
// StackedGraph: use JQuery/Tufte-Graph to create a stack histogram
// StripslashesArray: strip slashes from all array elements
// SubjectDecode: translates subject code in to words (geog => Geography)
// SubjectPulldown: creates an HTML pulldown element of subject codes and
//                  their associated subject areas
//
// Statistical Scripts: N, Sum, Mean, Median, Mode, StdDev, Histogram,
//                      DescriptiveStats
// ToUTF8: return html-encoded string as UTF-8 chars suitable for SQL
// ThisFolder: Returns the name of current folder
// Viewport: Print mobile-friendly viewport
//////////////////////////////////////////////////////////////////////////



function array_search_recursive($needle, $haystack, $a=0, $nodes_temp=array()){
  /* copied from php.net: 
     http://us2.php.net/manual/en/function.array-search.php#79640
     this script improves upon the array_search() function
     by returning an array of results, rather than just one result
  */
  global $nodes_found;
  $a++;
  foreach ($haystack as $key1=>$value1) {
    $nodes_temp[$a] = $key1;
    if (is_array($value1)){   
      array_search_recursive($needle, $value1, $a, $nodes_temp);
    }
    else if ($value1 === $needle){
      $nodes_found[] = $nodes_temp[$a];
    }
  }
  return $nodes_found;
}

//////////////////////////////////////////////////////////////////////////
//                           ArrayToTable
//////////////////////////////////////////////////////////////////////////

function ArrayToTable ($array, $total=0) {
  /* expects an array in this format:
     $array[$row_name][$col_name] = $cell_value;
     if $total = 1, display line and column totals 
  */

  /* on the first pass, learn all the column names */

  $colheads = array();
  foreach ($array as $row => $cols) { 
    foreach ($cols as $colhead => $data) {
      if (! in_array($colhead, $colheads)) {
	array_push ($colheads, $colhead);
      } // end if not in array
    } //end foreach col
  } //foreach row
  
  //  print_r($colheads);
  
  $printable = "<table>\n<tr class=\"colheads\"><th>&nbsp;</th>";
  foreach ($colheads as $colhead) {
    $printable .= "<th>$colhead</th>\n";
  }
  if ($total)
    $printable .= "<th>Total</th>\n";
  $printable .= "</tr>\n";

  /* second pass, extract data for printing */
  foreach ($array as $row => $cols) { 
    $row_head = "<th class=\"rowhead\">$row</th>";
    $line_data_cell = $line_sum = $line_data = ""; // reset
    foreach ($colheads as $colhead) {
      $temp_data = "";
      if ($array[$row][$colhead]) {
	$temp_data = $array[$row][$colhead];
	$line_data .= "<td class=\"data\">$temp_data</td>\n";
	if (is_numeric($temp_data)) {
	  $line_sum += $temp_data;
	  $col_sum[$colhead] += $temp_data;
	}
      } //end if data present
      else 
	$line_data .= "<td class=\"data\" class=\"empty\">&nbsp;</td>\n";
    } //end foreach colhead
    if ($total) 
      $line_data_cell = "<td class=\"line_total\">$line_sum</td>\n"; 
    else
      $line_data_cell = "";
    $printable .= "<tr>$row_head\n$line_data\n$line_data_cell</tr>\n\n";
  } //foreach row

  // get column sums if any
  if ($total) {
    foreach ($colheads as $colhead) { 
      $col_sums .= "<td class=\"col_total\">$col_sum[$colhead]</td>\n";
      $grand_total += $col_sum[$colhead];
    } //end foreach colhead
    $printable .= "<tr><th class=\"row_head\">TOTAL</th>\n$col_sums\n<td class=\"grand_total\">$grand_total</td></tr>\n";
  } //end if total requested

  $printable .= "</table>\n";
  return($printable);
} // end function ArrayToTable





//////////////////////////////////////////////////////////////////////////
//                           Breadcrumb
//////////////////////////////////////////////////////////////////////////

function Breadcrumb ($pathinfo) {
  if (strlen($pathinfo)<1) { $pathinfo = $REQUEST_URI; } 
  $divider = ">>";
  print "<p id=\"breadcrumbs\" style=\"clear: both; margin-left: 8ex; text-indent:-8ex\">\n";
  $pre_path = "/docs";
  $more_path= "/";
  $path = split ("/", $pathinfo);
  print "Return to: <a href=\"/lib/\">Library Homepage</a>";
  
  $count = count($path);
  $less = $count-1;
  
  if (preg_match("/^index\./","$path[$less]",$matches)) {
    array_pop($path);
    //    $count = $count-2;
    $count = count($path);
  } 
  /*
    print "<!-- COUNT ME: $count -->";
    print "<!--";
    print_r($path);
    print "-->";
  */

  for ($i=1; $i<$count; $i++) {
    $dir = $path[$i];
    $fn = "$pre_path$more_path$dir/breadcrumb.data";
    if (file_exists($fn)) {
      if ($file = fopen("$fn", "r")) {
	$line=fgetss($file,255);
	if (preg_match("/\"(.+)\"/",$line,$matches)) { 
	  $line = preg_replace("/\s+/","&nbsp;",$matches[1]); 
	}
	print " $divider <a href=\"$more_path$dir/\">$line</a>";
	fclose($file);
      } // end if file opens
    } //end if file exists
    $more_path .= "$dir/";
  } // end for
} // end function



//////////////////////////////////////////////////////////////////////////
//                           Breadcrumb2010
//////////////////////////////////////////////////////////////////////////

function Breadcrumb2010 () {
$pathinfo = $_SERVER[REQUEST_URI];
$divider = ">";
$pre_path = "/docs";
$more_path= "/";
$pathinfo = chop ($pathinfo, "/");
$path = split ("/", $pathinfo);
  print "<span class=\"crumb\"><a href=\"/lib/\">Library Homepage</a></span>";
  $count = count($path);
  $less = $count-1;

  
if (preg_match("/^index\./","$path[$less]",$matches))  
    {
    array_pop($path);
    //    $count = $count-2;
    $count = count($path);
  } 

  for ($i=1; $i<$count; $i++) {
    $dir = $path[$i];
    $fn = "$pre_path$more_path$dir/breadcrumb.data";
    if (file_exists($fn)) {
      if ($file = fopen("$fn", "r")) {
	$line=fgetss($file,255);
	if (preg_match("/\"(.+)\"/",$line,$matches)) { 
	  $line = preg_replace("/\s+/","&nbsp;",$matches[1]); 
	}
	print " $divider <span class=\"crumb\"><a href=\"$more_path$dir/\">$line</a></span>";
	fclose($file);
      } // end if file opens
    } //end if file exists
    $more_path .= "$dir/";
  } // end for
} // end function Breadcrumb2010

//////////////////////////////////////////////////////////////////////////
//                         Debug
//////////////////////////////////////////////////////////////////////////

function Debug($level = E_ALL) {
  error_reporting($level);
  ini_set("display_errors", true);
}

//////////////////////////////////////////////////////////////////////////
//                         DehyphenateISSN
//////////////////////////////////////////////////////////////////////////

// by default, it looks for a field called "issn", but you 
// can give it another table field to look in

function DehyphenateISSN ($table_name, $issn_field = "issn") {
  print $issn_field;
  $q = "UPDATE `$table_name` SET $issn_field = concat( substr( $issn_field, 1, 4 ), substr( $issn_field, 6, 4 ) ) WHERE $issn_field LIKE  '%-%'";
  if (mysql_query($q)) { return true; }
  else return false;
} //end function DehyphenateISSN


//////////////////////////////////////////////////////////////////////////
//                         EZRA_Locations
//////////////////////////////////////////////////////////////////////////

function EZRA_Locations() {
    // mysql_pconnect removed (lib)

  $results = mysql_query ("select * from ezra_loc_codes",$db);
  while ($myrow = mysql_fetch_row($results)) {
    $loc_code = $myrow[0];
    $location = $myrow[1];
    $ezra[$loc_code] = $location;
  }
  return $ezra;
}

/**************************************************************************
 **                 Function: EZRA_Status_By_Call($call)
 *************************************************************************/

function EZRA_Status_By_Call($call) {
  $call = preg_replace("/ +/", "+",$call);
  if (preg_match("/CD|DVD|VHS|CT|LP/",$call)) {$type ="f"; }
  else { $type = "c"; }
  
  $url = "http://ezra.wittenberg.edu/search/$type?SEARCH=$call";
  if (! $file = fopen ($url,"r")) { print "could not open file"; }
  else { 
  while (!(feof($file))) {
    $line =  fgetss($file,1056);
    if (preg_match("/(AVAILABLE|MISSING|NEW BOOK SHELF|Recently Returned|EXTINCT|DUE\s+[-\d]+)/", $line, $m)){
      $status = $m[1];
    }
  }
  if ($status == "EXTINCT") {
    global $_SERVER;
    $env = print_r($_SERVER, TRUE);
    $message = "There is an EXTINCT item from EZRA that appears in the AV search engine. It should be removed, and please get that record suppressed too.\n\n$env";
    mail("kirwin@wittenberg.edu","EXTINCT item visible in AV browse catalog",$message);
  } // end if extinct item
  }
  if (! $status) { $status = "STATUS UNCERTAIN, <a href=\"$url\">PLEASE CHECK</a>"; }
  return ($status);
} // end function EZRA_Status_By_Call
/**************************************************************************
 **                 Function: EZRA_Status_By_Call($call)
 *************************************************************************/

function EZRA_Status_By_BibRecord($bib) {
  if (! preg_match("/b/",$bib)) { $bib = "b" .$bib; }
  $url = "http://ezra.wittenberg.edu/record=". substr($bib,0,8);
  if (! $file = fopen ($url,"r")) { print "could not open file"; }

  while (!(feof($file))) {
    $line =  fgetss($file,1056);
    if (preg_match("/(AVAILABLE|MISSING|NEW BOOK SHELF|Recently Returned|EXTINCT|DUE\s+[-\d]+)/", $line, $m)){
      $status = $m[1];
    }
    if (preg_match ("/Connect to resource/",$line,$m)) {
      //      $856url = $m[1];
      $status = "AVAILABLE ONLINE: $line";
    } //end else if URL 
  } //end while not EOF

  if ($status == "EXTINCT") {
    global $_SERVER;
    $env = print_r($_SERVER, TRUE);
    $message = "There is an EXTINCT item from EZRA that appears in the AV search engine. It should be removed, and please get that record suppressed too.\n\n$env";
    mail("kirwin@wittenberg.edu","EXTINCT item visible in AV browse catalog",$message);
  } // end if extinct item

  if (! $status) { $status = "STATUS UNCERTAIN, <a href=\"$url\">PLEASE CHECK</a>"; }
  return ($status);
} // end EZRA_Status_By_BibRecord


///////////////////
// GetAllColors
///////////////////


function GetAllColors () {
  $colors = array ("#00FFFF","#000000","#0000FF","#8A2BE2","#A52A2A","#DEB887","#5F9EA0","#7FFF00","#D2691E","#FF7F50","#6495ED","#00FFFF","#00008B","#008B8B","#B8860B","#A9A9A9","#006400","#BDB76B","#8B008B","#556B2F","#FF8C00","#9932CC","#8B0000","#E9967A","#8FBC8F","#483D8B","#2F4F4F","#2F4F4F","#00CED1","#9400D3","#FF1493","#00BFFF","#696969","#696969","#1E90FF","#B22222","#228B22","#FF00FF","#FFD700","#DAA520","#808080","#808080","#008000","#ADFF2F","#FF69B4","#CD5C5C","#4B0082","#7CFC00","#ADD8E6","#F08080","#E0FFFF","#D3D3D3","#90EE90","#FFB6C1","#FFA07A","#20B2AA","#87CEFA","#778899","#778899","#B0C4DE","#00FF00","#32CD32","#FF00FF","#800000","#66CDAA","#0000CD","#BA55D3","#9370D8","#3CB371","#7B68EE","#00FA9A","#48D1CC","#C71585","#191970","#FFE4E1","#FFE4B5","#000080","#808000","#6B8E23","#FFA500","#FF4500","#DA70D6","#EEE8AA","#98FB98","#AFEEEE","#D87093","#FFDAB9","#CD853F","#FFC0CB","#DDA0DD","#B0E0E6","#800080","#FF0000","#BC8F8F","#4169E1","#8B4513","#FA8072","#F4A460","#2E8B57","#A0522D","#C0C0C0","#87CEEB","#6A5ACD","#708090","#708090","#00FF7F","#4682B4","#D2B48C","#008080","#D8BFD8","#FF6347","#40E0D0","#EE82EE","#F5DEB3","#FFFFFF","#F5F5F5","#FFFF00","#9ACD32");
  return($colors);
} //end GetAllColors



//////////////////////////////////////////////////////////////////////////
//                         GetDBList
//////////////////////////////////////////////////////////////////////////

// GetDBList: takes array of #s, returns list of databases & desc in dt/dd fmt


//////////////////////////////////////////////////////////////////////////
//                         GetIncludeContents
//////////////////////////////////////////////////////////////////////////

function GetIncludeContents($filename) {
  if (is_file($filename)) {
    ob_start();
    include $filename;
    $contents = ob_get_contents();
    ob_end_clean();
    return $contents;
  }
  return false;
}

//////////////////////////////////////////////////////////////////////////
//                         GetURLContents
//////////////////////////////////////////////////////////////////////////

function GetURLContents($url) {
  $handle = fopen($url, "rb");
  $contents = '';
  while (!feof($handle)) {
    $contents .= fread($handle, 8192);
  }
  fclose($handle);
  return $contents;
}

//////////////////////////////////////////////////////////////////////////
//                         GetJournalByISSN
//////////////////////////////////////////////////////////////////////////

// takes an ISSN (with or w/o hyphen) as an argument; returns journal title

function GetJournalByISSN($issn) {
    // mysql_pconnect removed (lib)

  if (preg_match ("/(\d\d\d\d)(\d\d\d.)/", $issn, $matches)) {
    $issn = "$matches[1]-$matches[2]";
  }
  $result = mysql_query ("SELECT title FROM phil WHERE issn = '$issn'",$db);

  while ($myrow = mysql_fetch_row($result)) {
    $title = $myrow[0];
  }
  return "$title";
}


//////////////////////////////////////////////////////////////////////////
//                         GetJournalByURL
//////////////////////////////////////////////////////////////////////////

// takes a URL as an argument; returns journal title

function GetJournalByURL($url) {
    // mysql_pconnect removed (lib)

  $result = mysql_query ("SELECT title FROM phil WHERE url = '$url'",$db);

  while ($myrow = mysql_fetch_row($result)) {
    $title = $myrow[0];
  }

  // sometimes the UMI URLs can be a little haphazard, but as long as 
  // a Publication ID is present, we can just match on that: 
  // note that UMI is gone now, so we use the OLD phil table "phil_min"
  // to match on old titles

  if (! $title) {
    if (preg_match("/Pub=(\d+)/",$url,$matches)) {
      $result = mysql_query ("SELECT title FROM phil_min WHERE url like '%$matches[1]%'",$db);
      while ($myrow = mysql_fetch_row($result)) {
	$title = $myrow[0];
      } // end while myrow
    } // end if a UMI PUB id
  } // end if no title found on first try

  return "$title";
}

//////////////////////////////////////////////////////////////////////////
//                         GetCampusLocation
//////////////////////////////////////////////////////////////////////////

function GetCampusLocation($ip) {
  // takes IP
  // returns $location,$subloc,$host
  if (preg_match("/^136\.227\.(\d+)\./", $ip, $matches)) {
    $subnet = $matches[1];
  }
  $query = "SELECT name,subloc FROM ip_subnets where subnet='$subnet'";
  //print "<P>$query</P>\n";
  $return = mysql_query($query);
  while ($myrow = mysql_fetch_row($return)) {
    $location=$myrow[0];
    $subloc=$myrow[1];
  }
  if ($subloc && (! $subloc == "NULL")) { $location .= ":$subloc"; }
  if (($location == "NULL") || (! $location)) { $location = "Off Campus"; }
  return $location;
} // end GetLocation


//////////////////////////////////////////////////////////////////////////
//                        GetOrdinal
//////////////////////////////////////////////////////////////////////////

// liberally hijacked from:
// http://www.talkphp.com/tips-tricks/204-tutorial-getting-th-st-rd-ordinal-suffixes-numbers-dates.html

function GetOrdinal($num) {
  // first convert to string if needed
  $the_num = (string) $num;
  // now we grab the last digit of the number
  $last_digit = substr($the_num, -1, 1);
  // if the string is more than 2 chars long, we get
  // the second to last character to evaluate
  if (strlen($the_num)>1) {
    $next_to_last = substr($the_num, -2, 1);
  } else {
    $next_to_last = "";
  }
  // now iterate through possibilities in a switch
  switch($last_digit) {
  case "1":
    // testing the second from last digit here
    switch($next_to_last) {
    case "1":
      $the_num.="th";
      break;
    default:
      $the_num.="st";
    }
    break;
  case "2":
    // testing the second from last digit here
    switch($next_to_last) {
    case "1":
      $the_num.="th";
      break;
    default:
      $the_num.="nd";
    }
    break;
    // if last digit is a 3
  case "3":
    // testing the second from last digit here
    switch($next_to_last) {
    case "1":
      $the_num.="th";
      break;
    default:
      $the_num.="rd";
    }
    break;
    // for all the other numbers we use "th"
  default:
    $the_num.="th";
  }

  // finally, return our string with it's new suffix
  return $the_num;
} //end GetOrdinal



//////////////////////////////////////////////////////////////////////////
//                         Gooduser
//////////////////////////////////////////////////////////////////////////

/* How to use Gooduser

The following code segment works:

$gooduser = Gooduser($barcode,$lastname,$ptypes,$rank);
if ($gooduser == 1) { print "true"; } // print true if Gooduser
else { print "$gooduser"; } // display explanitory error msg if not Gooduser


If the "rank" argument is set to "ignore", the script will just check for a valid ptype and will not ask for a specific rank match. (So the script will run even if the rank is not provided by the user.)


Here are the patron types and associated ranks:

#"0" => "student",
#"1" => "faculty",
#"2" => "staff",
#"3" => "family",
#"4" => "upward_bound",
#"5" => "student",               #SCE student
#"6" => "HS_scholar",
#"7" => "community",
#"8" => "temp"


*/

function Gooduser($barcode,$lastname,$ptypes,$rank) { 
  ob_start();
  passthru("/cgi-bin/lib/Confirm/confirm-exe.pl barcode=$barcode lastname=$lastname ptypes=\"$ptypes\" rank=$rank | egrep -v '^$'");
  $line = ob_get_contents();
  ob_end_clean();
  if (preg_match("/gooduser/",$line,$matches)) {
    return true;
  }
  else {
    return "$line";
  }
} // end Gooduser

/* OLDER VERSION DIDN'T WORK

function Gooduser($barcode,$lastname,$ptypes,$rank) { 
  $line =  exec ("/docs/lib/include/confirm/confirm.sh $barcode $lastname \"$ptypes\" $rank",$output,$return);
  //print "$line";
    if (preg_match("/gooduser/",$return,$matches)) {
      return true;
    }
    else {
      return "$output";
    }
} // end Gooduser

*/

//////////////////////////////////////////////////////////////////////////
//                           JoinWithAnd
//////////////////////////////////////////////////////////////////////////

function JoinWithAnd ($glue, $array) {
  /*
    takes and array and glues it together in "human list format":
    "apple","banana","pear" returns as
    "apple, banana, and pear"
    
    two-element arrays will return as "one and two" 
    ignoring any glue (glue may be set to "")
    
    NOTE: does not auto-add spaces after glue -- include it yourself!
  */

  if (sizeof($array) == 2)
    return ("$array[0] and $array[1]");

  else { 
    $last = array_pop($array);
    $string = join ($glue, $array);
    $string .= $glue.'and '.$last;
    return($string);
  } //end else if more than two elements
} // end function JoinWithAnd


//////////////////////////////////////////////////////////////////////////
//                           InCallOrder
//////////////////////////////////////////////////////////////////////////

function InCallOrder ($a, $b) {

  if (SortLC($a,$b) > 0) {return false;}
  else {return true;} 

} // end InCallOrder


//////////////////////////////////////////////////////////////////////////
//                         isEven 
//////////////////////////////////////////////////////////////////////////

function isEven ($input_number) {
   if (round($input_number/2) == ($input_number/2)) {
     return true;
   }
   else { return false; }
} // end function isEven


//////////////////////////////////////////////////////////////////////////
//                         isOdd
//////////////////////////////////////////////////////////////////////////

function isOdd ($input_number) {
   if (round($input_number/2) == ($input_number/2)) {
     return false;
   }
   else { return true; }
} // end function isOdd


//////////////////////////////////////////////////////////////////////////
//                    MysqlResultsTable
//////////////////////////////////////////////////////////////////////////

function MysqlResultsTable ($mysql_results, $table_id='') {
  while ($myrow = mysql_fetch_assoc($mysql_results)) {
    if (! ($headers))
      $headers = array_keys($myrow);
    $rows .= " <tr>\n";
    foreach ($headers as $k)
      $rows .= "  <td class=$k>$myrow[$k]</td>\n";
    $rows .= " </tr>\n";
  } // end while myrow
  $header = join("</th><th>",$headers);
  $header = "<tr><th>$header</th></tr>\n";
  if ($table_id != '') { $id = ' id="'.$table_id.'"'; }
  $rows = "<table$id>$header$rows</table>\n";
  return ($rows);
} //end function MysqlResultsTable


//////////////////////////////////////////////////////////////////////////
//                    JournalSubjectPulldown
//////////////////////////////////////////////////////////////////////////

function JournalSubjectPulldown($field_code,$curr_subj_code, $opt="") {
    // mysql_pconnect removed (lib)
 if (preg_match("/dept_only/",$opt)) { $where = "where not (liaison = '')"; }
 else { $where = ""; }
$result = mysql_query("SELECT subj_code,subject FROM subjects $where order by subject", $db);

while ($myrow = mysql_fetch_row($result)) {
  $subj_code = $myrow[0];
  $subject = $myrow[1];
  if ($curr_subj_code == $subj_code) { 
    $checked = "SELECTED";
    $curr_subject = $subject;
  }
  else { $checked = ""; }
  $options .= "<option value=$subj_code $checked>$subject</option>\n";
} #end while myrow

  if (preg_match("/witt.*sem/",$opt)) {
    if ($curr_subj_code == "wittsem")
      $checked = "SELECTED";
    $options.= "<option value=wittsem $checked>WittSem</option>\n";
  }
print "<select name=$field_code><option value=\"\">----- Select a Subject -----</option>\n";
print $options;
print "</select>\n";
}




//////////////////////////////////////////////////////////////////////////
//                         onCampus
//////////////////////////////////////////////////////////////////////////

function onCampus($REMOTE_ADDR) {
  if (preg_match ("/^136\.227/", "$REMOTE_ADDR", $matches)) {
    return true;
  }
  else { return false; }
}

function print_a($arr) {
  print "<pre>\n";
  foreach ($arr as $key => $value) {
    print "[\"$key\"] => \"$arr[$key]\"\n";
  }
  print "</pre>\n";
} 

//////////////////////////////////////////////////////////////////////////
//                              print_rr
//////////////////////////////////////////////////////////////////////////

function print_rr($array, $return=false) { // print really readable/web readable
  $lines = "<pre>";
  $lines .= print_r($array,true);
  $lines .= "</pre>\n";
  if ($return) { return $lines; }
  else { print $lines; }
}

//////////////////////////////////////////////////////////////////////////
//                              PurifyFile
//////////////////////////////////////////////////////////////////////////

function PurifyFile ($filename) {
  /* uses HTMLPurifier suite to "purify" a file -- overwriting it 
     with a purified version.
     I'm using this to convert HMTL character entity references to 
     UTF-8 standard in PHIL; could do other stuff too.
  */

  require_once '/docs/lib/include/htmlpurifier-4.0.0-standalone/HTMLPurifier.standalone.php';

  /*inititalize purifier */
  $purifier = new HTMLPurifier();

  $filesize = filesize($filename);

  print "\n$filesize bytes\n\n";

  /*get impure data*/
  $handle = fopen("$filename", "rb");
  $tempfile = fopen("puretemp-$filename", "w");
  $contents = '';
  while (!feof($handle)) {
    $contents .= fread($handle, 8192);
    if (strlen($contents) > 900000) {
      // purify and empty the buffer to temp before proceeding
      $clean_html = $purifier->purify($contents);
      fwrite($tempfile, $clean_html);
      $contents = '';
    } // end if $contents is over 9M bytes
  } //end while reading file
  
    /*purify remaining data and output to temp*/
  $clean_html = $purifier->purify($contents);
  fwrite($tempfile, $clean_html);

  fclose($handle);
  fclose($tempfile);   

  /*overwrite original file with pure data from tempfile*/
  copy("puretemp-$filename","$filename");
  unlink("puretemp-$filename");
} // end function PurifyFile


function PurifyFile_Simple ($filename) {
  /* uses HTMLPurifier suite to "purify" a file -- overwriting it 
     with a purified version.
     I'm using this to convert HMTL character entity references to 
     UTF-8 standard in PHIL; could do other stuff too.
  */

  require_once '/docs/lib/include/htmlpurifier-4.0.0-standalone/HTMLPurifier.standalone.php';

  /*inititalize purifier */
  $purifier = new HTMLPurifier();

  /*get impure data*/
  $handle = fopen("$filename", "rb");
  $contents = '';
  while (!feof($handle)) {
    $contents .= fread($handle, 8192);
  }
  fclose($handle);
  
  /*purify data*/
  $clean_html = $purifier->purify($contents);
  
  /*overwrite file with pure data*/
  $overwrite = fopen("$filename", "w");
  fwrite($overwrite, $clean_html);
  fclose($overwrite);   
} // end function PurifyFile_Simple






//////////////////////////////////////////////////////////////////////////
//                        safeStrToTime
//////////////////////////////////////////////////////////////////////////

function safestrtotime($strInput)
  /* 
     Takes a date between 1900 and 1969 and converts it to a usable,
     negative UNIX time stamp. doesn't work on pre-1900 dates.

     http://nosheep.net/story/php-strtotime-limitation/
  */

{
  $iVal = -1;
  for ($i=1900; $i<=1969; $i++)
    {
      // Check for this year string in date
      $strYear = (string)$i;
      if (!(strpos($strInput, $strYear)===false))
	{
	  $replYear = $strYear;
	  $yearSkew = 1970 - $i;
	  $strInput = str_replace($strYear, '1970', $strInput);
	}
    }
  $iVal = strtotime($strInput);
  if ($yearSkew> 0)
    {
      $numSecs = (60 * 60 * 24 * 365 * $yearSkew);
      $iVal = $iVal - $numSecs;
      $numLeapYears = 0;  // determine number of leap years in period
      for ($j=$replYear; $j<=1969; $j++)
	{
	  $thisYear = $j;
	  $isLeapYear = false;
	  // Is div by 4?
	  if (($thisYear % 4) == 0)
	    {
	      $isLeapYear = true;
	    }
	  // Is div by 100?
	  if (($thisYear % 100) == 0)
	    {
	      $isLeapYear = false;
	    }
	  // Is div by 1000?
	  if (($thisYear % 1000) == 0)
	    {
	      $isLeapYear = true;
	    }
    if ($isLeapYear == true)
      {
	$numLeapYears++;
      }
	}
      $iVal = $iVal - (60 * 60 * 24 * $numLeapYears);
    }
  return $iVal;
} // end function safeStrToTime


//////////////////////////////////////////////////////////////////////////
//                              SendSMS
//////////////////////////////////////////////////////////////////////////

/* 
   lives in ./sendsms.php 
 */

//////////////////////////////////////////////////////////////////////////
//                              SortCall 
//////////////////////////////////////////////////////////////////////////
// deprecated function; use SortLC instead (included at top of this file)

function DefunctSortCall ($left, $right) { 
  // call as: usort ($array, "SortCall");
  // where $array is a simple array of call numbers
  // returns negative if numbers are in LC order


  if (preg_match("/\s*([a-zA-Z]+)\s*(\d+\.*\d*)\s*\.*(([A-Z]+)\s*(([0-9]+)\s*(.*))*)*/i",$left,$a));
  if (preg_match("/\s*([a-zA-Z]+)\s*(\d+\.*\d*)\s*\.*(([A-Z]+)\s*(([0-9]+)\s*(.*))*)*/i",$right,$b));

  for ($i=0; $i<sizeof($a); $i++) { 
    $a[$i] = strtoupper($a[$i]);
  }

  for ($i=0; $i<sizeof($b); $i++) { 
    $b[$i] = strtoupper($b[$i]);
  }

  //  print_r($a); print_r($b);
  if (strcmp($a[1],$b[1]) != 0) // The class letters
    {
      return (strcmp($a[1],$b[1]));
    }
  elseif ($a[2] != $b[2]) //The classnumbers
    {
      return ($a[2] - $b[2]);
    }
  elseif (strcmp($a[3],$b[3]) != 0)# The cutter letters
    {
      return (strcmp($a[3],$b[3]));
    }
  elseif ($a[4] != $b[4])# The cutter numbers
    {
      return ($a[4] - $b[4]);
    }
  elseif (strcmp($a[5],$b[5]) != 0)# The leftovers
    {
      return (strcmp($a[5],$b[5]));
    }
  else
    {
      return 0;
    }
}

//////////////////////////////////////////////////////////////////////////////
//                              Sql2Adodb
//////////////////////////////////////////////////////////////////////////////

function Sql2Adodb($date_format, $date) {
  /* 
     Uses the adodb library to take a date from SQL date format
     and applies the PHP date() formatting to it.
     Returns the formatted date.
     
     Could be improved by adding an optional support for Y-m-d H:i:s input.
  */

  if (preg_match("/(\d{3,4})-(\d\d)-(\d\d)/",$date, $m)) {
    $year = $m[1];
    $month = $m[2];
    $day = $m[3];
  } //
  $timestamp = adodb_mktime(0,0,0,$month,$day,$year);
  $formatted_date = adodb_date($date_format,$timestamp);
  return ($formatted_date);
} //end function Sql2Adodb()

//////////////////////////////////////////////////////////////////////////
//                          StackedGraph
//////////////////////////////////////////////////////////////////////////


function StackedGraph ($data, $colors) {
/*

Example input values
$data = array (
	       "[[1027, 144.89], {label: 'July'}]",
	       "[[1086, 303.43], {label: 'Sept'}]",
	       "[[1335, 167.26], {label: 'Oct'}]"
	       );
$colors = "'tan','green'";
*/
  jQueryPlugins ("tufte-graph");
?>
    <script type="text/javascript">
      $(document).ready(function () {
        jQuery('#stacked-graph').tufteBar({
	  colors: [<?=$colors;?>],
	      data: [ <?=join(",", $data);?>
          ],
          barLabel:  function(index) {
            amount = ($(this[0]).sum()).toFixed(0);
            return '$' + $.tufteBar.formatNumber(amount);
          },
          axisLabel: function(index) { return this[1].label },
          legend: {
            data: ["Wholesale","Profit Margin"],

		},
        });
      });
    </script>

      <div id='stacked-graph' class='graph' style='width: 270px; height: 200px;'></div>
	
<?
} //end function





//////////////////////////////////////////////////////////////////////////
//                         StripslashesArray 
//////////////////////////////////////////////////////////////////////////

function StripslashesArray($arr) {
  /* 
     from php.net/slipslashes
     russ@zerotech.net
     28-Jun-2001 09:07
     Very useful for using on all the elements in say $HTTP_POST_VARS, or
     elements returned from an database query.  (to addslashes, just change
     strip to add =)
  */

  $rs = array();

  foreach ($arr as $key => $val) {
    $rs[$key] = stripslashes($val);
  }

  return $rs;
}



//////////////////////////////////////////////////////////////////////////
//                         SubjectDecode 
//////////////////////////////////////////////////////////////////////////

function SubjectDecode($subj_code) {
    //mysql_pconnect (lib)

  $result = mysql_query("SELECT subject FROM subjects where subj_code = '$subj_code'", $db);

  while ($myrow = mysql_fetch_row($result)) {
    $subject = $myrow[0];
  }
  return $subject;
}

//////////////////////////////////////////////////////////////////////////
//                        SubjectPulldown
//////////////////////////////////////////////////////////////////////////

function SubjectPulldown($field_code,$js="0",$opt="") {
  // if $js is set to "1", the script will submit OnChange

    //mysql_pconnect (lib)

if ($opt == "db_list") {
  $where = "WHERE db_list = 'Y'";
}
elseif ($opt == "reg_list") {
  $where = "WHERE registrar_list = 'Y'";
}
elseif ($opt == "liaison") {
  $where = "WHERE liaison != ''";
}
else {
  $where = "WHERE journ_only = 'N'";
}

$q = "SELECT subj_code,subject FROM subjects $where order by subject"; 

$result = mysql_query($q);

while ($myrow = mysql_fetch_row($result)) {
  $subj_code = $myrow[0];
  $subject = $myrow[1];
  if ($curr_subj_code == $subj_code) { 
    $checked = "SELECTED";
    $curr_subject = $subject;
  }
  else { $checked = ""; }
  $options .= "<option value=$subj_code $checked>$subject</option>\n";
} #end while myrow

  if ($js) {  $javascript = "onChange=\"this.form.submit()\""; }

print "<select name=$field_code $javascript><option value=\"\">----- Select a Subject -----</option>\n";
print $options;
print "</select>\n";
} //end SubjectPulldown

function Viewport () {
  print '<meta name="viewport" content="width=device-width"/>';
}


//////////////////////////////////////////////////////////////////////////
///                      Statistical Scripts                           ///
//////////////////////////////////////////////////////////////////////////
/*
N: returns the size of an array
Sum: returns the sum of values in an array
Mean: returns the mean (sum/n) of values in an array
Median: returns the median of values in an array
Mode: returns the mode (or modes) of values (separated by commas)
StdDev: returns the Standard Deviation of an array (only meaningful if a regular distribution
Histogram: creates a simple vertical histogram from array
*/

function N ($array) {
  return sizeof($array);
}

function Sum ($array) {
  foreach ($array as $item) {
    $sum += $item;
  } // end foreach
  return $sum;
} //end Sum

function Mean ($array) {
  return (Sum($array)/N($array));
} // end Mean

function Mode ($array) {
  foreach ($array as $item) {
    $occur[$item]++;
    if ($occur[$item] > $mode_count) {$mode_count = $occur[$item]; $mode = $item; }
    if ($occur[$item] == $mode_count) { 
      if (!(preg_match("/$item/",$mode))) {$mode = "$mode, $item"; }
    }
  } // end foreach
  return $mode;
}

function Median ($array) {
  $count = sizeof($array);
  sort($array);
  if (IsOdd($count)) {
    $median_index = (($count-1)/2);
    $median = $array[$median_index];
  }
  else {
    $mi1 = $count/2 -1;
    $mi2 = $mi1 + 1;
    $median = ($array[$mi1] + $array[$mi2])/2;
  }
  return $median;
}

/* 

*/
function StdDev($array) {
  $mean = Mean($array);
  $n = N($array);
  foreach ($array as $item) {
    $dif = $mean - $item;
    $sqr = $dif * $dif;
    $sum += $sqr;
  }
  $sd = sqrt ($sum/($n-1));
  return $sd;
}

function DescriptiveStats ($array, $print = true) {
  $stats = array();
  $stats[n] = sizeof($array);
  $stats[mean] =  Mean($array);
  $stats[median] = Median($array);
  $stats[mode] = Mode($array);
  $stats[sd] = StdDev($array);
  $printout .= "<li>N: $stats[n]</li>\n";
  $printout .= "<li>Mean: $stats[mean]</li>\n";
  $printout .=  "<li>Median: $stats[median]</li>\n";
  $printout .= "<li>Mode: $stats[mode]</li>\n";
  $printout .= "<li>StdDev: $stats[sd]</li>\n";
  if ($print) { print $printout; }
  return ($stats);
}


function Histogram ($array, $multiplier=10, $title_width=0, $columns=array()) {
  if ($title_width > 0) { $ti_width = " width=\"$title_width%\""; }
  foreach ($array as $item) {
    $occur[$item]++;
  }
  ksort ($occur);


  if ($columns) {
    for ($i=0; $i<sizeof($columns); $i++) {
      $thead .= "<th>$columns[$i]</th>"; 
    } //end foreach column
  } //end if columns
  
  else { 
    //    $thead = "<th>Key</th><th>Value</th><th>Graph</th>\n";
  }


  print "<table id=\"histogram\">";
  print "<thead><tr>$thead</tr></thead>\n";
  print "<tbody>\n";
  foreach ($occur as $k=>$v) {
    $width = $multiplier * $v;
    print "<tr><td $ti_width>$k</td> <td>$v</td> <td><img src=\"/lib/images/redblock.gif\" height=8 width=$width alt=\"$k: $v occurences\"></td></tr>\n";
  } // end foreach
  print "</tbody>\n";
  print "</table>\n";
}



function HistogramPlus($array, $multiple=10) {
  print "<LINK REL=StyleSheet HREF=\"/lib/style.css\" TYPE=\"text/css\">\n";
  $min = min($array);
  $max = max($array);
  $span = $max - $min;
  $width = round (100/$span);
  foreach ($array as $item) {
    $count[round($item)]++;
  }
  for ($i = min($array); $i<=max($array); $i++) {
    $height = $count[$i] * $multiple;
    if ($count[$i] >0) {
      $top .= "<td class=histogram><img src=\"/lib/images/redblock.gif\" width=\"100%\" height=$height alt=\"$i: $count[$i] entries\"></td>\n";
    }
    else { $top .= "<td>&nbsp;</tdL>\n"; }
    if ($span > 40) {
      if (($i % 5) == 0) { 
	$display = $i;
      }
      else {$display = "|"; }
    }
    else { $display = $i; }
    $bottom .= "<td class=histogram width=\"$width%\">$display</td>\n";
  } // end for each whole number encompassed by the array
  print "<table class=histogram>\n<tr>$top</tr>\n<tr>$bottom</tr>\n</table>\n";
} // end HistogramPlus


function ToUTF8 ($str) {
    return html_entity_decode($str, ENT_QUOTES, "utf-8");
}

function ThisFolder () {
  if (preg_match("/(.*\/)[^\/]+/", $_SERVER[SCRIPT_NAME], $m)) {
    $folder = $m[1];
    return $folder;
  }
} //end function ThisFolder



?>
