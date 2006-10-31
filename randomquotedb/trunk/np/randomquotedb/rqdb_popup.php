<?PHP

$strRel = '../../../';
include($strRel . 'config.php');
if (!$member->isLoggedIn())
	doError('You\'re not logged in.');

echo "<HEAD>";
echo "<TITLE>Nucleus: Random QuoteDB Plugin</TITLE>";
echo "</HEAD>";
echo "<BODY>";
echo "<DIV ID=\"content\">";


// To have safe mode enabled in php is a good idea. But I think nucleus isn't ready for this...
if (isset($HTTP_POST_VARS['quote'])) $quote = $HTTP_POST_VARS['quote'];
if (isset($HTTP_POST_VARS['quoteby'])) $quoteby = $HTTP_POST_VARS['quoteby'];

if ($_GET['mode'] == "add")
{
	// Add a quote
	if ($_GET['set'] == "true")
	{
		// Do we already have the quote?
		echo "Submitting quote to database. Please wait...<BR>";
		$quote = nl2br($quote);
		$quoteby = nl2br($quoteby);
		$query = "INSERT INTO " . sql_table('plugin_randomquotedb') . " (rqdb_quote, rqdb_quoteby) VALUES ('$quote', '$quoteby')";
                mysql_query($query);
		if (mysql_errno() != 0)
		{
			echo mysql_errno() . ": " . mysql_error() . "<BR>\n";
		}
		else
		{
			echo "Submitted quote to database. Done.<BR>";
			
		}
		echo "<INPUT TYPE=\"button\" VALUE=\"Close window\" onClick=\"window.close()\">";
	}
	else
	{
		// We don't have a quote, so let the user make his quote
        	print "<form action='rqdb_popup.php?mode=add&set=true' method='post'>";
       		print "Quote:<br>";
        	print "<textarea name='quote' rows='20' cols='50'></textarea><br>";
       		print "Quote by:<br>";
        	print "<textarea name='quoteby' rows='2' cols='50'></textarea><br>";
        	print "<input type='submit' name='add' value='add quote'></form>";
	}

}

elseif ($_GET['mode'] == "del")
{
	// Delete a quote
        if ($_GET['set'] == "true")
        {
                // Do we already have a delete command 
		$id = $_POST['id'];
		$query = "DELETE FROM " . sql_table('plugin_randomquotedb') . " WHERE rqdb_id LIKE '$id'";
		mysql_query($query);
                if (mysql_errno() != 0)
                {
                        echo mysql_errno() . ": " . mysql_error() . "<BR>\n";
                }
                else
                {
                        echo "Deleted quote from database. Done.<BR>";

                }
                echo "<INPUT TYPE=\"button\" VALUE=\"Close window\" onClick=\"window.close()\">";

        }
        else
        {
                // We don't have a delete command so let the user choose
		$query="SELECT * FROM " . sql_table('plugin_randomquotedb');
		$queryresult = mysql_query($query);
		if (mysql_errno() != 0)
                {
                        echo mysql_errno() . ": " . mysql_error() . "\n";
			echo "<INPUT TYPE=\"button\" VALUE=\"OK\" onClick=\"window.close()\">";
			die();
                }
                else
                {
			while($quotes=mysql_fetch_array($queryresult))
			{
				print "<B>Quote:</B><BR>\n";
				print "$quotes[rqdb_quote]<BR>\n";
				print "<B>Quote by:</B><BR>\n";
				print "$quotes[rqdb_quoteby]<BR>\n";
				print "<FORM ACTION='rqdb_popup.php?mode=del&set=true' method='post'>\n";
				print "<INPUT TYPE='hidden' NAME='id' value='$quotes[rqdb_id]'>\n";
				print "<INPUT TYPE='submit' NAME='delete' VALUE='Delete quote'><br>\n";
				print "</FORM>";
				print "<HR>";
			}
		}
        }

}

elseif ($_GET['mode'] == "chg")
{
	// Change a quote
	// Has the user already considered a quote to change?
	if ($_GET['set'] == "true")
	{
		$id = $_POST['id'];
		$quote = $_POST['quote'];
		$quoteby = $_POST['quoteby'];
		// Show edit fields
                print "<form action='rqdb_popup.php?mode=chg&set=save' method='post'>";
                print "Quote:<br>";
                print "<textarea name='quote' rows='20' cols='50'>$quote</textarea><br>";
                print "Quote by:<br>";
                print "<textarea name='quoteby' rows='2' cols='50'>$quoteby</textarea><br>";
		print "<INPUT TYPE='hidden' NAME='id' value='$id'>\n";
                print "<input type='submit' name='add' value='add quote'></form>";
	}
	elseif ($_GET['set'] == "save")
	{
               $id = $_POST['id'];
               $quote = $_POST['quote'];
               $quoteby = $_POST['quoteby'];
               echo "Submitting quote to database. Please wait...<BR>";
               $quote = nl2br($quote);
               $quoteby = nl2br($quoteby);
               $query = "UPDATE " . sql_table('plugin_randomquotedb') . " SET rqdb_quote ='$quote', rqdb_quoteby = '$quoteby' WHERE rqdb_id = '$id'";
               mysql_query($query);
               if (mysql_errno() != 0)
               {
                       echo mysql_errno() . ": " . mysql_error() . "<BR>\n";
               }
               else
               {
                       echo "Submitted quote to database. Done.<BR>";

               }
                echo "<INPUT TYPE=\"button\" VALUE=\"Close window\" onClick=\"window.close()\">";
	}	
	else
	{
		// Let the user choose which quote he wants to change
		$query="SELECT * FROM " . sql_table('plugin_randomquotedb');
		$queryresult = mysql_query($query);
		               if (mysql_errno() != 0)
                {
                        echo mysql_errno() . ": " . mysql_error() . "\n";
                        echo "<INPUT TYPE=\"button\" VALUE=\"OK\" onClick=\"window.close()\">";
                        die();
                }
                else
                {
                        while($quotes=mysql_fetch_array($queryresult))
                        {
                                print "<B>Quote:</B><BR>\n";
                                print "$quotes[rqdb_quote]<BR>\n";
                                print "<B>Quote by:</B><BR>\n";
                                print "$quotes[rqdb_quoteby]<BR>\n";
                                print "<FORM ACTION='rqdb_popup.php?mode=chg&set=true' method='post'>\n";
                                print "<INPUT TYPE='hidden' NAME='quote' value='$quotes[rqdb_quote]'>\n";
                                print "<INPUT TYPE='hidden' NAME='quoteby' value='$quotes[rqdb_quoteby]'>\n";
                                print "<INPUT TYPE='hidden' NAME='id' value='$quotes[rqdb_id]'>\n";
                                print "<INPUT TYPE='submit' NAME='delete' VALUE='Edit quote'><br>\n";
                                print "</FORM>";
                                print "<HR>";
			}
		}
	}
}
else
{
	// Set here a fallback if someone manipulated our variables
	die ("You entered an invalid specification.");
}
?>
</div>
</body>
</html>
