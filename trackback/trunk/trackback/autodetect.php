<?php

	$strRel = '../../../'; 
	include($strRel . 'config.php');
?>

	var xmlhttp = false;
	var inProgress = false;
	
	var TrackbackAction = "<?php echo $CONF['ActionURL'];?>";
	var TrackbackSource = new Array;
	var TrackbackName   = new Array;
	var TrackbackURL    = new Array;
	
	var LookupTable     = new Array;
	var Lookup			= '';
	
	var regexp = /href\s*=\s*([\"\'])(http:[^\"\'>]+)([\"\'])/ig;
		
		
	function tbParseLinks ()
	{
		oinputbody = document.getElementById('inputbody');
		oinputmore = document.getElementById('inputmore');
		full = oinputbody.value + ' ' + oinputmore.value;

		while (vArray = regexp.exec(full)) 
		{
			unused = true;
			
			if (Lookup == vArray[2])
				unused = false;

			for (var i = 0; i < LookupTable.length; i++)
				if (LookupTable[i] == vArray[2])
					unused = false;

			for (var i = 0; i < TrackbackSource.length; i++) 
				if (TrackbackSource[i] == vArray[2])
					unused = false;
			
			if (unused == true)
				LookupTable.push(vArray[2]);
		}
	}
	
	function tbAutoDetect()
	{
		if (LookupTable.length > 0)
		{
			tbBusy(true);

			if (!inProgress)
			{
				// We have something to do and the connection is free
				Lookup = LookupTable.shift();
				inProgress = true;
	
				// The reason we use GET instead of POST is because
				// Opera does not properly support setting headers yet,
				// which is a requirement for using POST.
				xmlhttp.open("GET", TrackbackAction + "?action=plugin&name=TrackBack&type=detect&tb_link=" + escape(Lookup), true);
				xmlhttp.onreadystatechange = tbStateChange;
				xmlhttp.send('');
			}
			else
			{
				// Still busy... simply wait until next turn
			}
		}
		else
		{
			// Nothing to do, check back later...
			if (Lookup == '')
				tbBusy(false);
		}
	}

	function tbStateChange ()
	{
		if (inProgress == true && xmlhttp.readyState == 4 && xmlhttp.status == 200) 
		{
			eval (xmlhttp.responseText);
			inProgress = false;
			Lookup = '';
		}
	}

	function tbBusy(toggle)
	{
		o = document.getElementById('tb_busy');
		
		if (o)
		{
			if (toggle)
				o.style.display = '';
			else
				o.style.display = 'none'
		}
	}

	function tbDone(source, url, name, type)
	{
		TrackbackSource.push(source);
		TrackbackURL.push(url);
		TrackbackName.push(name);
			
		if (url != '')
		{
			var parent = document.getElementById('tb_auto');
			var amount = document.getElementById('tb_url_amount');
			
			count = parseInt(amount.value);

			checkbox = document.createElement("input");
			checkbox.type = 'checkbox';
			checkbox.name = "tb_url_" + count;
			checkbox.id = "tb_url_" + count;
			checkbox.value = type+"#!#"+url;

			label =	document.createElement("label"); 
			label.htmlFor = "tb_url_" + count;
			label.title = source + " (" + type + ")";
			
			text = document.createTextNode(name + " (" + type + ")");
			label.appendChild(text);
			
			br = document.createElement("br"); 

			parent.appendChild(checkbox);
			parent.appendChild(label);
			parent.appendChild(br);

			amount.value = count + 1;
		}
	}

	function tbSetup() 
	{
		try 
		{
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		} 
		catch (e) 
		{
			try 
			{
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			} 
			catch (e) 
			{
				xmlhttp = false;
			}
		}

		if (!xmlhttp && typeof XMLHttpRequest!='undefined') 
		{
			xmlhttp = new XMLHttpRequest();
		}
		
		setInterval ('tbParseLinks();', 500);
		setInterval ('tbAutoDetect();', 500);
		
		if (window.onloadtrackback)
			window.onloadtrackback();				
	}

	if (window.onload)
		window.onloadtrackback = window.onload;

	window.onload = tbSetup;
	
