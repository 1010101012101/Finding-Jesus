<?php
	session_start();
	class URL{
		private $name;
		public function __construct($temp){	
			$this->name = $temp;
		}
		public function getName(){
			return $this->name;
		}
	}
	function highlightWords($text, $words)
	{
		foreach ($words as $word)
		{
			$word = preg_quote($word);
			$text = preg_replace("/\b($word)\b/i", '<span class="highlight_word">\1</span>', $text);
		}
		return $text;
	}
?>
<!-- 
	Neil Guzman: 021-428-107
	BTI320A
	Last PHP Milestone
	10/30/2011
-->
<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US" xml:lang="en-US">
<head>
	<title>KJV Search</title>
	<link href="kjv.css" rel="stylesheet" type="text/css">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
	<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
	<script>
		$(document).ready(function() {
			$("#accordion").accordion();
		});
		$(function() {
			$('#activator').click(function(){
				$('#overlay').fadeIn('fast',function(){
					$('#box').animate({'top':'160px'},100);
				});
			});
			$('#boxclose').click(function(){
				$('#box').animate({'top':'-9999px'},100,function(){
					$('#overlay').fadeOut('fast');
				});
			});

		});
	</script>
</head>
<body>
<?php
	$host = "127.0.0.1";
	$username = "root";
	$password = "";
	$dbname = "bible";	
	
	$i = 0; // counter variable
	$j = 0; // counter variable
	$resultsPP = 10; // # of results per page
	$totalPages = 0;  // # of pages in total after culling from db
	$numRowsSearched  = 0; // # of results
	$limit = ""; // will specify LIMIT
	$searched = $_GET['phrase'];
	$offset = 0; // where to start in when culling from db
	$showLeft = 3; // # of pages to show at the left side of current page selected
	$showRight = 3; // # of pages to show at the right side of current page selected
	$pageNow = ($_GET['page']==0)?1:$_GET['page']; // returns 1 if 0
	$whichBook = $_GET['book']; // figures out which book was selected
	$mysqli = new mysqli($host,$username,$password,$dbname); 
	if ($mysqli->connect_error)
	{
		die("Connect Error (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	}	
	$sect_names = array
	(
		'E' => 'Entire Bible',
		'O' => 'Old Testament',
		'N' => 'New Testament'
	);
	$searchResultsPP_opt = array
	(
		'10' => '10',
		'20' => '20',
		'50' => '50',
		'100' => '100',
	);
	$select_resultsPP = array
	(
		$_GET['searchResultsPP'] => "selected =\"selected\""
	);
	$searchOrder_opt = array
	(
		'R' => 'Relevance',
		'N' => 'Natural Book Order',
		'A' => 'Alphabetical Order'
	);
	$select_order = array
	(
		$_GET['searchOrder'] => "selected =\"selected\""
	);
	$select_book = array
	(
		$_GET['book'] => "selected =\"selected\""
	);
	$count = array
	(
		$_GET['count_only'] => "checked =\"checked\""
	);
	$bool = array
	(
		$_GET['bool'] => "checked =\"checked\""
	);
	if ($_GET['book'] == 'E')
	{
		$and = "";
	}
	else if ($_GET['book'] == 'O')
	{
		$and = "AND bsect = 'O'";
	}
	else if ($_GET['book'] == 'N')
	{
		$and = "AND bsect = 'N'";
	}
	else if ($whichBook != NULL)
	{
		$and = "AND $whichBook = bnum";
	}
	$orderBy = "ORDER BY relevance";
	
	if($_GET['searchOrder'] == 'N')
	{
		$orderBy = "ORDER BY bnum ";
	}
	else if($_GET['searchOrder'] == 'A')
	{
		$orderBy = "ORDER BY bname ASC ";
	}
	if($_GET['searchResultsPP'])
	{
		$resultsPP = $_GET['searchResultsPP'];
	}
	$split  = str_split($_GET['phrase']);
	$words = "";
	foreach ($split as $key=>$val)
	{
		if ($val >= 'a' && $val <= 'z' || $val >= 'A' && $val <= 'Z' || $val == ' ')
			$words .= $val;
	}
	if (!$_GET['bool'])
	{
		$searched = $words;
	}
	else
	{
		$searched = $_GET['phrase'];
	}
	$words = explode(" ", $words);
	if ($_GET['cnum'] && $_GET['book'])
	{
		$book = $_GET['book'];
		$and .= " AND $book = bnum ";
		$cnum = $_GET['cnum'];
		$and .= " AND $cnum = cnum ";
	}
	$rowsSearched = $mysqli->query("SELECT bsect, bname, bnum, cnum, vnum, vtext, MATCH(bname, vtext) AGAINST ('$searched' IN BOOLEAN MODE) AS relevance FROM kjv WHERE MATCH (bname, vtext) AGAINST ('$searched' IN BOOLEAN MODE) $and $orderBy");
	$numRowsSearched = $rowsSearched->num_rows;
	$totalPages = ceil($numRowsSearched/$resultsPP);
	$offset = (($pageNow-1)*$resultsPP);
	$limit = "LIMIT $offset, $resultsPP";
	$qstring = '?phrase='.urlencode($searched).'&book='.urlencode($_GET['book']).'&cnum='.$cnum.'&choice=Search&searchOrder='.urlencode($_GET['searchOrder']).'&searchResultsPP='.urlencode($_GET['searchResultsPP']).'&count_only='.urlencode($_GET['count_only']).'&bool='.urlencode($_GET['bool']).'&page=';
	if (!$_GET['cnum'])
	{
		if (isset($ob))
			$ob = null;
		$ob = new URL($_SERVER['REQUEST_URI']);
		$_SESSION['url'] = $ob->getName();
	}
?>
<div id = "container">
	<div id = "header">
		<?php
			print ("<a href=\"{$_SERVER['PHP_SELF']}\">King James Bible Search Form</a>");
		?>
	</div>
	<div id = "search-row">
		<form id="form" method="get" action="" enctype="multipart/form-data">
			<table class="table" id="table">
				<tr>
					<td><input type="text" name="phrase"  size="64" value='<?=$searched?>'/></td>
					<td>
						<select name="book"  id="book">
							<?php	
								$stmt = $mysqli->prepare("SELECT bsect, bname, bnum FROM kjv_bookmap WHERE bsect = ? ORDER BY bnum");
								$stmt2 = $mysqli->prepare("SELECT DISTINCT bsect FROM kjv_bookmap");
								$stmt2->execute();
								$stmt2->bind_result($ver);
								while ($stmt2->fetch())
								{
									$sections[$j] = $ver;
									$j++;
								}
								$stmt->bind_param('s', $bsect); 
								print ("<optgroup label=\"Section\">");
								foreach ($sect_names as $key=>$val)
								{
									print ("<option value=\"$key\" $select_book[$key]>$val</option>");
								}
								print ("</optgroup>");
								foreach ($sections as $val)
								{
									$bsect = $val;
									if ($sect_names[$val])
									{
										print ("<optgroup label=\"$sect_names[$bsect]\">");
									}
									else
									{
										print ("<optgroup label=\"$bsect\">");
									}
									$stmt->execute(); 
									$stmt->bind_result($bsect, $bname, $bnum);
									while ($stmt->fetch())
									{
										print ("<option value=\"$bnum\" $select_book[$bnum]>$bname</option>");
									}
									print ("</optgroup>");
								}
							?>
						</select> 
					</td>
					<td>
						<input id="search-button" type="submit" name="choice" value="Search" />
					</td>
				</tr> 
				<tr>
					<td>
							<p>Order results by</p>
						</td>
						<td>
							<select name="searchOrder" id="searchOrder">
								<?php
									foreach($searchOrder_opt as $key=>$val)
									{
										print ("<option value=\"$key\" $select_order[$key]>$val</option>");
									}
								?>
							</select>
						</td>
				</tr>
				<tr>
					<td>
							<p>Results per page </p>
						</td>
						<td>
							<select name="searchResultsPP" id="searchResultsPP">
								<?php
									foreach($searchResultsPP_opt as $key=>$val)
									{
										print ("<option value=\"$key\" $select_resultsPP[$key]>$val</option>");
									}
								?>
							</select>
						</td>
				</tr>
				<tr>
					<td>
						<p>Return count only:</p>
					</td> 
					<td>
						<?php
							if ($_GET['count_only'] != NULL)
							{
								print ("<label><input type=\"radio\" name=\"count_only\" value=\"1\" $count[1]/>yes</label> "); 
								print ("<label><input type=\"radio\" name=\"count_only\" value=\"0\" $count[0]/>no</label> "); 
							}
							else
							{
								print ("<label><input type=\"radio\" name=\"count_only\" value=\"1\" />yes</label> "); 
								print ("<label><input type=\"radio\" name=\"count_only\" value=\"0\" checked=\"checked\"/>no</label> "); 
							}
						?>
					</td>
				</tr>
				<tr>
					<td>
						<p>Boolean mode:</p>
					</td> 
					<td>
						<?php
							if ($_GET['bool'] != NULL)
							{
								print ("<label id=\"activator\"><input type=\"radio\" name=\"bool\" value=\"1\" $bool[1]/>yes</label> "); 
								print ("<label><input type=\"radio\" name=\"bool\" value=\"0\" $bool[0]/>no</label> "); 
							}
							else
							{
								print ("<label id=\"activator\"><input type=\"radio\" name=\"bool\" value=\"1\" ]/>yes</label> "); 
								print ("<label><input type=\"radio\" name=\"bool\" value=\"0\" checked=\"checked\"/>no</label> "); 
							}
						?>
						<div class="overlay" id="overlay" style="display:none;"></div>
						<div class="box" id="box">
						 <a class="boxclose" id="boxclose"></a>
						 <h1>Boolean Search Operators</h1>
						 <div class="accordion" id="accordion">
							<h3><a href="#">QUOTATION (" ")</a></h3>
							<div>
								<p>
									A phrase that is enclosed within double quote (�"�) characters matches only rows that contain the phrase literally, as it was typed.
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'"some words"'</code></li>
								</ul>
								<p>
									Find rows that contain the exact phrase �some words� (for example, rows that contain �some words of wisdom� but not �some noise words�).
								</p>
							</div>
							<h3><a href="#">AND (+)</a></h3>
							<div>
								<p>
									A leading plus sign indicates that this word must be present in each row that is returned. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'+apple +juice'</code></li>
								</ul>
								<p>
									Find rows that contain both words.
								</p>
							</div>
							<h3><a href="#">OR</a></h3>
							<div>
								<p>
									By default (when neither + nor - is specified) the word is optional, but the rows that contain it are rated higher. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'apple banana'</code></li>
								</ul>
								<p>
									Finds rows that contain at least one of the two words.
								</p>
							</div>
							<h3><a href="#">NOT (-)</a></h3>
							<div>
								<p>
									A leading minus sign indicates that this word must not be present in any of the rows that are returned. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'+apple -macintosh'</code></li>
								</ul>
								<p>
									Find rows that contain the word "apple" but not "macintosh"
								</p>
							</div>
							<h3><a href="#">RELEVANCE (< >)</a></h3>
							<div>
								<p>
									These two operators are used to change a word's contribution to the relevance value that is assigned to a row. The > operator increases the contribution and the < operator decreases it. See the example following this list. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'+(>[word1] <[word2])'</code></li>
								</ul>
								<p>
									Find rows that contain "word1" or "word2" but rank rows that contain "word1" higher than rows with "word2". (word1/word2 are to be replaced by any words and must not include square brackets)
								</p>
							</div>
							<h3><a href="#">TRUNCATION (*)</a></h3>
							<div>
								<p>
									The asterisk serves as the truncation (or wildcard) operator. Unlike the other operators, it should be appended to the word to be 	affected. Words match if they begin with the word preceding the * operator. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'apple*'</code></li>
								</ul>
								<p>
									Find rows that contain words such as �apple�, �apples�, �applesauce�, or �applet�. 
								</p>
							</div>
							<h3><a href="#">GROUPING (( ))</a></h3>
							<div>
								<p>
									Parentheses group words into subexpressions. Parenthesized groups can be nested. 
								</p>
								<br / >
								<p>
									For example:
								</p>
								<ul>
									<li><code>'+apple +(>[word1]  <[word2])'</code></li>
								</ul>
								<p>
									Find rows that contain the words �apple� and "word1", or �apple� and "word2" (in any order), but rank �apple word1" higher than �apple word2". (word1/word2 are to be replaced by any words and must not include square brackets)
								</p>
							</div>
						</div>
						</div>
					</td>
				</tr>
			</table>
		</form>
	</div>
	<?php
	if ($_GET['phrase']!=NULL){
		print ("<div id = \"results-header\">");
			print ("<p>About $numRowsSearched results for: $searched </p>");
		print ("</div>");
	}	
	if ($totalPages>1 && $_GET['count_only']!=1)
	{
		print ("<div id = \"results-paging\">");
		if ($pageNow != 1)
		{
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . 1 . "\">First</a>");
			$num = ($pageNow-1);
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $num . "\">Prev</a>");
		}
		for($page=$pageNow-$showLeft; $page < $totalPages+1 && $page<=$pageNow+$showRight; $page++)
		{
			if ($page > 0 && $page < $totalPages+1 )
			{
				$pageNumTxt = $page;
			}
			if($pageNow != $page && $page != 0 && $page < $totalPages+1 && $page>0)
			{
				$pageNumTxt = "<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $page . "\">$pageNumTxt</a>";
			}
			print (" $pageNumTxt ");
		}
		if ($pageNow != $totalPages)
		{
			$num = ($pageNow+1);
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $num . "\">Next</a>");
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $totalPages . "\">Last</a>");
		}
		print ("</div>");
	}
	if ($_GET['count_only']!=1)
	{
		if ($_GET['phrase']!=NULL)
		{
			$result = $mysqli->prepare("SELECT @rownum:=@rownum+1 rownum, bsect, bname, bnum, cnum, vnum, vtext, MATCH(bname, vtext) AGAINST ('$searched' IN BOOLEAN MODE) AS relevance FROM (SELECT @rownum:=0) row, kjv WHERE MATCH (bname, vtext) AGAINST ('$searched' IN BOOLEAN MODE) $and $orderBy $limit");
			$result->execute();
			$result->bind_result($rownum, $bsect, $bname, $bnum, $cnum, $vnum, $vtext, $match);
			print ("<div id=\"results\">");
			if ($_GET['cnum'] && $pageNow == 1)
			{	
				print ("<h2><a class=\"back\" href=\"". $_SESSION['url'] ."\"> Go back</a></h2>");
			}
			while ($result->fetch())
			{
					$verse = '?phrase='.urlencode($searched).'&book='. $bnum .'&cnum='.$cnum.'&choice=Search&searchOrder='.urlencode($_GET['searchOrder']).'&searchResultsPP='.urlencode($_GET['searchResultsPP']).'&count_only='.urlencode($_GET['count_only']).'&bool='.urlencode($_GET['bool']).'&page=' . 1;
					if ($_GET['cnum'] && $_GET['book'])
					{
						print ("<h3>$bname $cnum:$vnum</h3>");
						
					}
					else
					{
						print ("<h3><a href=\"".$_SERVER["PHP_SELF"].$verse."\">$bname $cnum:$vnum</a></h3>");
					}
					$vtext = highlightWords($vtext, $words);
					print ("<ul><li><em>$vtext</em></li></ul>");
			}
			print ("</div>");
		}
	}
	if ($totalPages>1 && $_GET['count_only']!=1)
	{
		print ("<div id = \"paging\">");
		if ($pageNow != 1)
		{
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . 1 . "\">First</a>");
			$num = ($pageNow-1);
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $num . "\">Prev</a>");
		}
		$pageNumTxt = NULL; 
		for($page=$pageNow-$showLeft; $page < $totalPages+1 && $page<=$pageNow+$showRight; $page++)
		{
			if ($page > 0 && $page < $totalPages+1 )
			{
				$pageNumTxt = $page;
			}
			if($pageNow != $page && $page != 0 && $page < $totalPages+1 && $page>0)
			{
				$pageNumTxt = "<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $page . "\">$pageNumTxt</a>";
			}
				print (" $pageNumTxt ");
		}
		if ($pageNow != $totalPages)
		{
			$num = ($pageNow+1);
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $num . "\">Next</a>");
			print ("<a href=\"".$_SERVER["PHP_SELF"]. $qstring . $totalPages . "\">Last</a>");
		}
		print ("</div>");
	}	
	?>
	<div id = "footer">
		<p>Comments or questions? Contact <a href="mailto:nbguzman@learn.senecac.on.ca?subject=KJVSearch">My Email</a></p>
		<?php
			$check = 0;
			$info = $_SERVER['HTTP_USER_AGENT'];
			$browsers = array (
						   'firefox' => 'Mozilla Firefox',
						   'chrome' => 'Google Chrome',
						   'opera' => 'Opera',
						   'MSIE' => 'Internet Explorer'
						);
			foreach ($browsers as $key=>$val)
			{
				if (preg_match("/$key/i",$info))
				{
					print ("<p class=\"FB\">You are using $val</p>");
					$check = 1;
				}
			}
			if ($check == 0)
			{
				print ("<p class=\"FB\">Your browser isn't listed in the array</p>");
			}
		?>
	</div>
</div>
</body>
</html>
