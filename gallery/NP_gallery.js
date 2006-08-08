// Detect if the browser is IE or not.
// If it is not IE, we assume that the browser is NS/mozilla.
var IE = document.all?true:false

// If NS -- that is, !IE -- then set up for mouse movement and click capture
if (!IE) document.captureEvents(Event.MOUSEMOVE)
if (!IE) document.captureEvents(Event.MOUSEDOWN)

// Temporary variables to hold mouse x-y pos.s
var tempX = 0
var tempY = 0
var check = 0
var Pos1x = 0
var Pos1y = 0
var Pos2x = 0
var Pos2y = 0
var RelX = 0
var RelY = 0

//start is the trigger that starts the box creation.
function start() {
	//assign click as the event handler if there is a click.
	document.onmousedown = click
	return true
}

function click(e){
			if (check == 2){return;//prevent multiple box drawing, or catching a third click.
				}
			if (check == 0) { //first click
				if (IE) { // grab the x-y pos.s if browser is IE
						// have to use document.documentElement instead of document.body 
						// because for some reason IE6 reassigns when in standards complient mode
						// http://www.quirksmode.org/js/doctypes.html
 				   tempX = event.clientX + document.documentElement.scrollLeft
 				   tempY = event.clientY + document.documentElement.scrollTop
				  } else {  // grab the x-y pos.s if browser is NS/mozilla
 				   tempX = e.pageX
 				   tempY = e.pageY
 				 }  
				 //write temporary position 1(top left corner)
				Pos1x = tempX
				Pos1y = tempY
				//getting the check variable ready for the second click
				check = check + 1 
				//create the drawingbox and insert it into the DOM tree at the first mouse click location
				createbox()
				//run the function that updates the mouse postion and update the box size on mousemove
				document.onmousemove = getMouseXY
				return false;
			}
			//second click
			if (check == 1) {
				//save second click location(bottem right) to submit to php for calculation
				Pos2x = tempX
				Pos2y = tempY
				check = check + 1 // makes check = 2 to prevent additional boxes being drawn, or variables being written
				//disable mouseevents
				document.onmousedown = null
				document.onmousemove = null
				//create the box for the caption
				createcaptionbox()
				return false;
			}		
	return false;
}

// Main function to retrieve mouse x-y pos.s and update box size.
function getMouseXY(e) {
  if (IE) { // grab the x-y pos.s if browser is IE
    // have to use document.documentElement instead of document.body 
	// because for some reason IE6 reassigns when in standards complient mode
	// http://www.quirksmode.org/js/doctypes.html
 	tempX = event.clientX + document.documentElement.scrollLeft
 	tempY = event.clientY + document.documentElement.scrollTop
  } else {  // grab the x-y pos.s if browser is NS/mozilla
    tempX = e.pageX
    tempY = e.pageY
  }  
  // catch possible negative values in NS4
  if (tempX < 0){tempX = 0}
  if (tempY < 0){tempY = 0}  
  //while the mouse is moving,create and update the size of the drawingbox
  var box = document.getElementById('drawingbox')
  //update the size of the box everytime the mouse moves a pixel
  boxwidth =(tempX - Pos1x) //these calculations have to be 
  boxheight = (tempY - Pos1y) //seperate because IE chokes
  box.style.width = boxwidth + 'px' //update box width and height
  box.style.height = boxheight + 'px'
  return true;
}

function createcaptionbox() {
	//the insert caption box is really a form inside a div
	//these lines generate a form, and puts in inputs 
	//Pos1x,Pos1y,Po2x,Pos2y,RelX,RelY,pictureid,description
	//the form has to be created here because IE can not accept
	//two forms with the same name (firefox does)
	//otherwise it would be easier to generate the 
	//form from the nucleus template
	var captionformdiv = document.createElement('div')
	var captionform = document.createElement('form')
	captionform.setAttribute('name','Show')
	captionform.setAttribute('method','POST')
	captionform.setAttribute('action','/action.php?action=plugin&name=gallery&type=tagaccept')
	captionform.style.border
	captionform.style.font
	captionform.style.padding
	var inputPos1x = document.createElement('input')
	inputPos1x.setAttribute('name','Pos1x')
	inputPos1x.setAttribute('value',Pos1x)
	inputPos1x.setAttribute('type','hidden')
	captionform.appendChild(inputPos1x)
	var inputPos1y = document.createElement('input')
	inputPos1y.setAttribute('name','Pos1y')
	inputPos1y.setAttribute('value',Pos1y)
	inputPos1y.setAttribute('type','hidden')
	captionform.appendChild(inputPos1y)
	var inputPos2x = document.createElement('input')
	inputPos2x.setAttribute('name','Pos2x')
	inputPos2x.setAttribute('value',Pos2x)
	inputPos2x.setAttribute('type','hidden')
	captionform.appendChild(inputPos2x)
	var inputPos2y = document.createElement('input')
	inputPos2y.setAttribute('name','Pos2y')
	inputPos2y.setAttribute('value',Pos2y)
	inputPos2y.setAttribute('type','hidden')
	captionform.appendChild(inputPos2y)
	var inputRelX = document.createElement('input')
	inputRelX.setAttribute('name','RelX')
	inputRelX.setAttribute('value',RelX)
	inputRelX.setAttribute('type','hidden')
	captionform.appendChild(inputRelX)
	var inputRelY = document.createElement('input')
	inputRelY.setAttribute('name','RelY')
	inputRelY.setAttribute('value',RelY)
	inputRelY.setAttribute('type','hidden')
	captionform.appendChild(inputRelY)
	var inputdesc = document.createElement('input')
	inputdesc.setAttribute('name','desc')
	inputdesc.setAttribute('value','enter caption')
	inputdesc.setAttribute('size','30')
	inputdesc.style.fontSize = '10px'
	inputdesc.style.border = '0px'
	inputdesc.setAttribute('onClick','erasedesc();')
	captionform.appendChild(inputdesc)
	var inputpictureid = document.createElement('input')
	inputpictureid.setAttribute('name','pictureid')
	inputpictureid.setAttribute('value',pictureid )
	inputpictureid.setAttribute('type','hidden')
	captionform.appendChild(inputpictureid)
	var inputsubmit = document.createElement('input')
	inputsubmit.setAttribute('name','Submit')
	inputsubmit.setAttribute('type','submit')
	inputsubmit.style.fontFamily = 'Verdana'
	inputsubmit.style.fontSize = '10px'
	inputsubmit.style.backgroundColor = '#cccccc'
	inputsubmit.style.border ='1px'
	inputsubmit.setAttribute('value','Tagit!')
	captionform.appendChild(inputsubmit)
	//give the div that wraps the form some attributes.
	captionformdiv.style.position = 'absolute'
	captionformdiv.style.left = Pos1x  +'px'
  	captionformdiv.style.top = Pos2y  +'px'
	captionformdiv.style.width =  tempX - Pos1x - 5 +'px'
	captionformdiv.style.backgroundColor = '#FFFFFF'
	captionformdiv.style.display = 'block'
	captionformdiv.style.borderStyle = 'solid'
	captionformdiv.style.borderColor = '#FFFFFF'
	//put the captionform into the captionbox into the wrapper div
	captionformdiv.appendChild(captionform)
	document.getElementById('container').appendChild(captionformdiv)


}

function createbox(){
	// initialize the box with zero width and height, inserted at the first mouse click location (tempX,tempY)
	var drawingbox = document.createElement('div')
				drawingboxwidth = Pos2x - RelX
				drawingbox.style.position = 'absolute'
				drawingbox.setAttribute('id','drawingbox')
				drawingbox.setAttribute('class','drawingbox')
				drawingbox.style.borderStyle = 'solid'
				drawingbox.style.borderColor = '#FFFFFF'
				drawingbox.style.borderWidth = '1px'
				drawingbox.style.left = tempX  +'px'
  				drawingbox.style.top = tempY  +'px'
				drawingbox.style.height = "0px"
				drawingbox.style.width = "0px"
				drawingbox.style.display = 'block'
				drawingbox.style.zIndex = '5'
				//where to insert the box in the DOM tree.
				document.getElementById('container').appendChild(drawingbox);
	}

//find actual location of an element in the DOM tree(in this case the image). 
//this function is called as a onMouseover event from the browser. code from quirksmode.org
function findPosX(obj)
{
	var curleft = 0;
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	}
	else if (obj.x)
		curleft += obj.x;
	return curleft;
}

function findPosY(obj)
{
	var curtop = 0;
	var printstring = '';
	if (obj.offsetParent)
	{
		while (obj.offsetParent)
		{
			printstring += ' element ' + obj.tagName + ' has ' + obj.offsetTop;
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (obj.y)
		curtop += obj.y;
	window.status = printstring;
	return curtop;
}

// on mouseover the image, executes this to assign its absolute position and save it(RelX,RelY)
// for relative div position calculating purposes in the php tagaccept file.
function setLyr(obj,lyr)
{
	RelX = findPosX(obj);
	RelY = findPosY(obj);

}

//on mouseout (NS) or mouseleave (IE), hide the tooltip boxes
function hidetipdivs() {
	
	navRoot = document.getElementById("tooltip2");
	for (i=0; i<navRoot.childNodes.length; i++) {
		navRoot.childNodes[i].style.display = 'none' ;
   }
  }

//on mouseover (NS) or on mouseenter (IE) show tipdivs
function showtipdivs() {
navRoot = document.getElementById("tooltip2");
for (i=0; i<navRoot.childNodes.length; i++) {
  navRoot.childNodes[i].style.display = '' ;
   }
  }
  
function erasedesc(){
	document.Show.desc.value=null
	document.Show.desc.value=''
	}
  

//hide the boxes initially, to avoid confusion where the mouse is initially in the image, or not in the image.
//hidetipdivs(); 
// this had to be moved to after the picture loads, because for some reason
// if you insert javascript from an external file, IE6 loads the file AFTER everything else is loaded
// if it is a relative URL, and BEFORE everything is loaded if it is a absolute URL containing http://
// so IE6 tries to remove the hover captions but there are no hover captions to remove so IE6 crashes.

function startList() {
if (document.all && document.getElementById) { //check if its IE
navRoot = document.getElementById("tooltip2");
for (i=0; i<navRoot.childNodes.length; i++) {
  node = navRoot.childNodes[i];
  if (node.nodeName=="DIV") {
  node.onmouseover=function() {
  this.className+=" over";
    }
  node.onmouseout=function() {
  this.className=this.className.replace
      (" over", "");
   }
   }
  }
 }
}
window.onload=startList;




