/* 
Joe Tan (joetan54@gmail.com)
http://tantannoodles.com/toolkit/flickr-dhtml-badge/

Version 0.1

This is a Javascript class library used to display thumbnails. This library is
based on the popular Flickr flash badge and is in no way endorsed by Flickr or Yahoo.

Copyright (C) 2005  Joe Tan

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

*/
/* commonly used functions, based on 1k DHTML API (http://www.dithered.com/javascript/1kdhtml/) */
d=document;l=d.layers;op=navigator.userAgent.indexOf('Opera')!=-1;px='px';
function gE(e,f){if(l){f=(f)?f:self;var V=f.document.layers;if(V[e])return V[e];for(var W=0;W<V.length;)t=gE(e,V[W++]);return t;}if(d.all)return d.all[e];return d.getElementById(e);}
function sZ(e,z){l?e.zIndex=z:e.style.zIndex=z;}
function sX(e,x){l?e.left=x:op?e.style.pixelLeft=x:e.style.left=x+px;}
function sX2(e,x){l?e.right=x:op?e.style.pixelright=x:e.style.right=x+px;}
function sY(e,y){l?e.top=y:op?e.style.pixelTop=y:e.style.top=y+px;}
function sY2(e,y){l?e.bottom=y:op?e.style.pixelBottom=y:e.style.bottom=y+px;}
function sW(e,w){l?e.clip.width=w:op?e.style.pixelWidth=w:e.style.width=w+px;}
function sH(e,h){l?e.clip.height=h:op?e.style.pixelHeight=h:e.style.height=h+px;}
function getElem(e, f) { return gE(e,f) }
function setZ(e, z) { return sZ(e,z) }
function setLeft(e, x) { return sX(e,x) }
function setRight(e, x) { return sX2(e,x) }
function setTop(e, y) { return sY(e,y) }
function setBottom(e, y) { return sY2(e,y) }
function setWidth(e, w) { return sW(e,w) }
function setHeight(e, h) { return sH(e,h) }


var Badge = function() {
    function Badge(e){
    }
    var self = Badge
    
    /*
       Public methods 
    */
    function addPhoto(src, link) {
        this.photos[this.photos.length] = src
        this.links[this.links.length] = link
        var img = new Image()
        img.src = src
        this.images[this.images.length] = img
        
    }
    function setSize(size) {
        this.size = size
    }
    function setMargin(margin) {
        this.margin = margin
    }
    function setDelay(delay) {
        this.delay = delay
    }
    function setLinkTarget(target) {
        this.target = target
    }
    
    function initialize(id) {
        this.badge = getElem(id)
        if (!this.size) this.size = 48
        if (!this.margin) this.margin = 2
        if (!this.delay) this.delay = 5000
        if (!this.target) this.target = "_top"
        
        this.previewSize = (this.size*2) +  this.margin; // previews are twice as big
        this.width = this.badge.offsetWidth // width of badge div
        this.height = this.badge.offsetHeight // height of badge div
        
        this.absWidth = Math.floor(this.width/this.size)*this.size // actual width of images
        this.absHeight = Math.floor(this.height/this.size)*this.size
        
        this.numPhotos = Math.floor(this.width/this.size)*Math.floor(this.height/this.size) // num photos contained in badge at one time
        
        // adjust with margins
        if ((this.absWidth + ((this.numPhotos-1)*this.margin)) > this.width) {
        }
        if ((this.absHeight + ((this.numPhotos-1)*this.margin)) > this.height) {
        }
        
        
        //alert(this.numPhotos)
        this.orderList = this.getOrderList(this.numPhotos, 10) // only loop thru photos 10 times
        this.photosList = this.getOrderList(this.photos.length, 10)
        
        var loading = document.createElement('div')
        loading.id=id+'-loading'
        loading.appendChild(document.createTextNode('loading...'))
        this.badge.appendChild(loading)
        
        this.layoutPictures()
    }
    
    
    /*
        Private methods - you shouldn't have to call any of these functions
    */
    function layoutPictures() {
        //var ahrefs = this.badge.getElementsByTagName('a');
        var x = 0;
        var y = 0;
        var ahref, img, imgTmp
        
        for (var i=0; i<this.numPhotos; i++) {
            imgTmp = document.createElement('img')
            imgTmp.id = 'badgeImage-tmp-'+i
            imgTmp.className = 'tmp'
            imgTmp.src = this.photos[this.photosList[i]]
            setWidth(imgTmp, this.size)
            setHeight(imgTmp,this.size)
            this.badge.appendChild(imgTmp)
            
        
            ahref = document.createElement('a')
            ahref.href = this.links[this.photosList[i]]
            ahref.id = 'badgeHref-'+i
            ahref.target = this.target
            //ahref.className = 'set'

            img = document.createElement('img')
            img.src = this.photos[this.photosList[i]]
            img.id = 'badgeImage-'+i
            setWidth(img, this.size)
            setHeight(img, this.size)
                        
            ahref.appendChild(img)
            this.badge.appendChild(ahref)
        
            if ((x + this.previewSize) >= this.width) {
                setRight(imgTmp, (this.width - (x + this.size)));
                setRight(ahref, (this.width - (x + this.size)));
            } else {
                setLeft(imgTmp, x);
                setLeft(ahref, x);
            }
            if ((y + this.previewSize) >= this.height) {
                setBottom(imgTmp, (this.height - (y + this.size)));
                setBottom(ahref, (this.height - (y + this.size)));
            } else {
                setTop(imgTmp, y);
                setTop(ahref, y);
            }

            x += this.size + this.margin;
            if ((x+this.size) > this.width) {
                x = 0;
                y += this.size + this.margin;
            }            
            // set variables 
            
            // show image
           // ahref.className = 'set'

        }


        //this.initAnimation(0, 0)
        var loading = getElem(this.badge.id + '-loading')
        loading.className='hidden'

        this.revealPhotosStart(0)
    }
    
    // fade in initial photo set
    function revealPhotosStart(inc) {
        inc = parseInt(inc)
        var which = this.orderList[inc]
        if (inc >= this.numPhotos) {
            setTimeout("Badge.initAnimation("+(inc)+", "+(this.numPhotos)+")", 2000)
        } else {
            this.revealPhotosStartHelp(which, 0)
            setTimeout("Badge.revealPhotosStart("+(inc+1)+")", 500)
        }
    }
    function revealPhotosStartHelp(which, opacity) {
        var badge = getElem('badgeHref-'+which);
        badge.className = 'set opacity'+opacity
        if (opacity < 100) {
            setTimeout("Badge.revealPhotosStartHelp("+which+", "+(opacity + 10)+")", 100);
        } else {
        }
        
    }

    // choose the next thumbnail to display, from a randomized list
    function initAnimation(inc, pic) {
        inc = parseInt(inc)
        var which = this.orderList[inc]
        if (pic >= this.photosList.length) {
            pic = 0;
        }
        
        this.animateThumb(which, pic);
        if (inc >= this.orderList.length) { 
            inc = 0;
        } else {
            setTimeout("Badge.initAnimation("+(inc+1)+", "+(pic+1)+")", this.delay);
        }
        
    }
    
    function animateThumb(imgLocation, pic) {
        var img = getElem('badgeImage-'+imgLocation)
        var href = getElem('badgeHref-'+imgLocation)
        var imgTmp = getElem('badgeImage-tmp-'+imgLocation)
        imgTmp.src = img.src
        imgTmp.className = 'tmp tmp2'
        img.src = this.photos[this.photosList[pic]]
        href.href = this.links[this.photosList[pic]]
        setZ(href, this._getNextZ())
        setWidth(img, this.previewSize)
        setHeight(img, this.previewSize)
        revealThumb(imgLocation, 0);
//        setTimeout("Badge.animateBadgeHelp('"+which+"', '"+this.size * (this.cols - 1)+"')", 1000)
    }

    function revealThumb(which, opacity) {
        var badge = getElem('badgeHref-'+which);
        badge.className = 'set opacity'+opacity
        if (opacity < 100) {
            setTimeout("Badge.revealThumb("+which+", "+(opacity + 10)+")", 100);
        } else {
            setTimeout("Badge.animateThumbHelp("+which+", "+this.previewSize+")", 750);
        }
    }
    
    function animateThumbHelp(which, size) {
        size = parseInt(size)
        var img = getElem('badgeImage-'+which)
        if (size > this.size) {
            setWidth(img, size-1);
            setHeight(img, size-1);
            setTimeout("Badge.animateThumbHelp('"+which+"', "+(size-2)+")", 1);
        } else {
            setWidth(img, this.size);
            setHeight(img, this.size);
        }
    }

    function _getNextZ() {
        return this._z++;
    }
    
    // determine the thumbnail order from which to randomly place the next thumbnail
    function getOrderList(max, times) {
        var rand = Array();
        var seed = Array();
        for (var i=0;i<max;i++) {
            seed[i] = i
        }
        for (var j=0;j<times;j++) {
            seed.sort(Badge.shuffle)
            for (var i=0;i<seed.length;i++) {
                rand[((j*seed.length)+i)] = seed[i]
            }    
        }

        return rand;
    }
    function shufflePhotos() {
    }
    
    // randomize shuffle
    function shuffle(list) {
        return (Math.round(Math.random())-0.5);
    }
    
    
    self.initialize = initialize
    self.layoutPictures = layoutPictures
    self.initAnimation = initAnimation
    self.revealThumb = revealThumb
    self.animateThumb = animateThumb
    self.animateThumbHelp = animateThumbHelp
    self.revealPhotosStart = revealPhotosStart
    self.revealPhotosStartHelp = revealPhotosStartHelp
    
    self.shuffle = shuffle
    
    self.addPhoto = addPhoto
    self.setSize = setSize
    self.setMargin = setMargin
    self.setDelay = setDelay
    self.setLinkTarget = setLinkTarget
    
    self.photos = Array()
    self.links = Array()
    self.images = Array()
    self.target = false
    
    self.badge = false
    self.orderList = false
    self.photosList = false

    self.getOrderList = getOrderList
    self._getNextZ = _getNextZ
    self._z = 5;
    
    return self;
}();