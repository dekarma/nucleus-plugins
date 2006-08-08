<?php
  
  function _addAcronym($acro, $full) {
    $query = sql_query("SELECT `acro` FROM `".sql_table('plug_acronym')."` WHERE `acro`=\"$acro\"");
    $result = mysql_fetch_assoc($query);
    if ($result['acro'] != "") return(array(FALSE,"<p>You already have that acronym in the list!</p>"));
    else {
      //Add the acronym to the database
      $query = sql_query("INSERT INTO `".sql_table('plug_acronym')."` VALUES (\"$acro\",\"$full\")");
      return(array(TRUE,"<p>Acronym successfully added!</p>"));
    }
  }

  function _delAcronym($acro) {
    //Delete acronym
    $query = sql_query("DELETE FROM `".sql_table('plug_acronym')."` WHERE `acro`=\"$acro\"");
    if ($query) return(array(TRUE,"<p>Acronym successfully deleted.</p>"));
  }

  function _editAcronym($old, $acro, $full) {
    $query = sql_query("UPDATE `".sql_table('plug_acronym')."` SET `acro`=\"$acro\", `full`=\"$full\" WHERE `acro`=\"$old\"");
    if ($query) {
      return(array(TRUE,"<p>Acronym successfully edited.</p>"));
    }
  }
  
  function _makeForm($type, $acro) {
    switch ($type) {
      case "del":
        echo "<h3 style=\"padding-left: 0px\">Delete acronym</h3>";
        echo "<p>Do you really want to delete this acronym?</p>";
        echo "<form name=\"delete\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\"><input type=\"hidden\" name=\"acro\" value=\"$acro\" /><input type=\"hidden\" name=\"action\" value=\"delacro\" /><input type=\"submit\" name=\"Submit\" value=\"Confirm Deletion\" /></form>";
				break;
      case "add":
      case "edit":
        echo "<h3 style=\"padding-left: 0px\">";
        if ($type == "add") {
          echo "Add new acronym</h3>";
          echo "<form name=\"add\"";
        }
        else {
          echo "Edit acronym</h3>";
          echo "<form name=\"edit\"";
          $query = sql_query('SELECT * FROM `'.sql_table('plug_acronym').'` WHERE `acro`="'.$acro.'"');
          $result = mysql_fetch_assoc($query);
          $acro = $result['acro'];
          $full = stripslashes($result['full']);
        }
        echo " method=\"post\" action=\"\">";
        echo "<table><tbody>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Acronym</td><td><input name=\"acro\" type=\"text\" id=\"acro\" value=\"$acro\" size=\"20\" maxlength=\"20\"></td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
        echo "<td>Full text</td><td><input name=\"full\" type=\"text\" id=\"text\" value=\"$full\" size=\"50\" maxlength=\"255\"></td></tr>";
        echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'><td>&nbsp;</td><td>";
        echo "<input type=\"submit\" name=\"Submit\" value=\"";
        if ($type == "edit") echo "Edit";
        else echo "Add";
        echo " this acronym\" /></td></tbody></table>";
        if ($type == "edit") echo "<input type=\"hidden\" name=\"old\" value=\"".$_GET['acro']."\" /><input type=\"hidden\" name=\"action\" value=\"editacro\" />";
        else echo "<input type=\"hidden\" name=\"action\" value=\"addacro\" />";
        echo "</form>";
        break;
      }
  }
  function _listAcronyms () {
    $query = sql_query("SELECT * FROM `".sql_table('plug_acronym')."` ORDER BY `acro`");
    echo "<h3 style=\"padding-left: 0px\">Manage Acronyms</h3>";
    echo '<table><thead><tr><th>Acronym</th><th>Full text</th><th>Action</th></tr></thead><tbody>';
    while ($acronym = mysql_fetch_assoc($query)) {
      echo "<tr onmouseover='focusRow(this);' onmouseout='blurRow(this);'>";
      echo '<td>'.$acronym['acro'].'</td><td>'.$acronym['full'].'</td><td>';
      echo "<a href=\"".$_SERVER['PHP_SELF']."?action=editacro&acro=".$acronym['acro']."\" title=\"Edit this acronym\">edit</a> ";
      echo "<a href=\"".$_SERVER['PHP_SELF']."?action=delacro&acro=".$acronym['acro']."\" title=\"Delete this acronym\">delete</a>";
      echo '</td></tr>';
    }
    echo '</tbody></table>';
  }
  
  if ($_POST['action'] != "") {
    switch ($_POST['action']) {
      case "addacro": 
			  $error = _addAcronym($_POST['acro'], $_POST['full']);
				echo $error[1];
				_listAcronyms();
				_makeForm("add", "");
				break;
      case "delacro": 
			  $error = _delAcronym($_POST['acro']);
				echo $error[1];
				_listAcronyms();
				_makeForm("add", "");
				break;
      case "editacro":
			  $error = _editAcronym($_POST['old'], $_POST['acro'], $_POST['full']);
				echo $error[1];
				_listAcronyms();
				_makeForm("add", "");
  			break;
    }
  }

  elseif ($_GET['action'] != "") {
    switch ($_GET['action']) {
      case "delacro": _makeForm("del", $_GET['acro']); break;
      case "editacro": _makeForm("edit", $_GET['acro']); break;
    }
  }
  
  else {
    _listAcronyms();
    _makeForm("add", "");
  }

?>
