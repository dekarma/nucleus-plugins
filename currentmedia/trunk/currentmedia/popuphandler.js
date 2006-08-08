	// Version 0.5 1/4/2005 2:24 AM
	function updateParent(item, t) { // formerly (t)
		var asinvalue, titlevalue, byvalue, imagevalue, i, html;

		control = document.forms.selectCurrent.elements['asin' + item];
			asinvalue = control.value;
		control = document.forms.selectCurrent.elements['title' + item];
			titlevalue = control.value;
		control = document.forms.selectCurrent.elements['by' + item];
			byvalue = control.value;
		control = document.forms.selectCurrent.elements['image' + item];
			imagevalue = control.value;

		// Set up HTML for Add Item page
		html = createHTML(t, asinvalue, titlevalue, byvalue, imagevalue);

		// Fill the hidden form fields; these fields will be inserted into the database
		window.opener.document.getElementById("cmtype").value = t;
		window.opener.document.getElementById("cmasin").value = asinvalue;
		window.opener.document.getElementById("cmtitle").value = titlevalue;
		window.opener.document.getElementById("cmby").value = byvalue;
		window.opener.document.getElementById("cmimage").value = imagevalue;

		// Display the HTML block on the Add Item page; displays the selected media to user
		window.opener.document.getElementById("zone1").style.display = "block";
		window.opener.document.getElementById("htmloutput").innerHTML = html;

		// Close popup
		self.close();
	}

	function createHTML(t, asin, title, by, image) {
		var type, word, html;

		switch(t) {
			case 3:
			case 0:
				type = "Watching";
				word = "starring";
				break;
			case 1:
				type = "Reading";
				word = "by";
				break;
			case 2:
				type = "Listening";
				word = "by";
				break;
			case 4:
				type = "Playing";
				word = "by";
				break;
		}

		html = "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">";
		html += "<tr>\n <td valign=\"top\">\n";
		html += "<img src=\"" + image + "\" /> <br /> \n";
		html += "<a href=\"#\" onClick=\"resetHTML(); return false;\">reset</a> \n";
		html += "</td>\n<td valign=\"top\">\n";
		html += "Currently " + type + ": " + title + "<br />\n";
		html += word + " " + by + "<br />\n";
		if (t == "2") {
			html += "Track Name (optional): ";
			html += "<input type=\"text\" size=\"20\" name=\"cmtrack\" value=\"\" />\n";
		}
		html += "</td>\n </tr>\n </table>\n";

		return html;
	}