		// Version 0.5 1/4/2005 2:24 AM
		function popUp(URL) {
			childWindow = window.open(URL, 'cm','toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=400,height=500,left=312,top=184');
			if (childWindow.opener == null)
				childWindow.opener = self;
		}

		function resetHTML() {
			var html;

			html = "";
			document.getElementById("zone1").style.display = "none";
			document.getElementById("htmloutput").innerHTML = html;

			document.getElementById("cmaction").value = "none";
			document.getElementById("cmtype").value = "";
			document.getElementById("cmasin").value = "";
			document.getElementById("cmtitle").value = "";
			document.getElementById("cmby").value = "";
			document.getElementById("cmimage").value = "";
		}

		function deleteItem() {
			document.getElementById("zone1").style.display = "none";
			document.getElementById("cmaction").value = "delete";
		}

		function hideItem() {
			document.getElementById("zone1").style.display = "none";
			document.getElementById("zone0").style.display = "inline";
		}

		function addItem() {
			document.getElementById("cmaction").value = 'add';
		}

		function changeItem() {
			document.getElementById("cmaction").value = "update";
		}