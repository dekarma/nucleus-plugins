/*
Last Modified: 25/11/06 20:39:27

AJS effects
    A very small library with a lot of functionality
AUTHOR
    4mir Salihefendic (http://amix.dk) - amix@amix.dk
LICENSE
    Copyright (c) 2006 Amir Salihefendic. All rights reserved.
    Copyright (c) 2005 Bob Ippolito. All rights reserved.
    Copyright (c) 2006 Valerio Proietti, http://www.mad4milk.net
    http://www.opensource.org/licenses/mit-license.php
VERSION
    3.5
SITE
    http://orangoo.com/AmiNation/AJS
**/
AJS.fx = {
    highlight: function(elm, options) {
        var base = new AJS.fx.Base();
        base.elm = AJS.$(elm);
        base.setOptions(options);
        base.options.duration = 600;

        AJS.update(base, {
            _shades: {0:'ff', 1:'ee', 2:'dd', 3:'cc', 4:'bb', 5:'aa', 6:'99'},

            increase: function(){
                if(this.now == 7)
                    elm.style.backgroundColor = 'transparent';
                else
                    elm.style.backgroundColor = '#ffff' + this._shades[Math.floor(this.now)];
            }
        });
        return base.custom(6, 0);
    },

    fadeIn: function(elm, options) {
        options = options || {};
        if(!options.from) options.from = 0;
        if(!options.to) options.to = 1;
        return this._fade(elm, options);
    },

    fadeOut: function(elm, options) {
        options = options || {};
        if(!options.from) options.from = 1;
        if(!options.to) options.to = 0;
        return this._fade(elm, options);
    },
    
    _fade: function(elm, options) {
        var base = new AJS.fx.Base();
        base.elm = AJS.$(elm);
        options.transition = AJS.fx.Transitions.linear;
        base.setOptions(options);
        AJS.update(base, {
            start: function() {
                return this.custom(this.options.from, this.options.to);
            },

            increase: function() {
                AJS.setOpacity(this.elm, this.now);
            }
        });
        return base.start();
    },

    setWidth: function(elm, options) {
        return this._setDimension(elm, 'width', options).start();
    },

    setHeight: function(elm, options) {
        return this._setDimension(elm, 'height', options).start();
    },

    _setDimension: function(elm, dim, options) {
        //Init
        var base = new AJS.fx.Base();
        base.elm = AJS.$(elm);
        base.setOptions(options);
        base.elm.style.overflow = 'hidden';
        base.dimension = dim;

        if(dim == 'height')
            base.show_size = base.elm.scrollHeight;
        else
            base.show_size = base.elm.offsetWidth;

        //Attach methods
        AJS.update(base, {
            _getTo: function() {
                if(this.dimension == 'height')
                    return this.options.to || this.elm.scrollHeight;
                else
                    return this.options.to || this.elm.scrollWidth;
            },

            start: function() {
                if(this.dimension == 'height') {
                    return this.custom(this.elm.offsetHeight, this._getTo());
                }
                else {
                    return this.custom(this.elm.offsetWidth, this._getTo());
                }
            },

            increase: function(){
                if(this.dimension == 'height')
                    AJS.setHeight(this.elm, this.now);
                else
                    AJS.setWidth(this.elm, this.now);
            }
        });
        return base;
    }
}


//From moo.fx
AJS.fx.Base = function() {
    AJS.bindMethods(this);
};
AJS.fx.Base.prototype = {

    setOptions: function(options){
        this.options = AJS.update({
                onStart: function(){},
                onComplete: function(){},
                transition: AJS.fx.Transitions.sineInOut,
                duration: 500,
                wait: true,
                fps: 50
        }, options || {});
    },

    step: function(){
        var time = new Date().getTime();
        if (time < this.time + this.options.duration){
            this.cTime = time - this.time;
            this.setNow();
        } else {
            setTimeout(AJS.$b(this.options.onComplete, this, [this.elm]), 10);
            this.clearTimer();
            this.now = this.to;
        }
        this.increase();
    },

    setNow: function(){
        this.now = this.compute(this.from, this.to);
    },

    compute: function(from, to){
        var change = to - from;
        return this.options.transition(this.cTime, from, change, this.options.duration);
    },

    clearTimer: function(){
        clearInterval(this.timer);
        this.timer = null;
        return this;
    },

    _start: function(from, to){
        if (!this.options.wait) this.clearTimer();
        if (this.timer) return;
        setTimeout(AJS.$p(this.options.onStart, this.elm), 10);
        this.from = from;
        this.to = to;
        this.time = new Date().getTime();
        this.timer = setInterval(this.step, Math.round(1000/this.options.fps));
        return this;
    },

    custom: function(from, to){
        return this._start(from, to);
    },

    set: function(to){
        this.now = to;
        this.increase();
        return this;
    }
};

//Transitions (c) 2003 Robert Penner (http://www.robertpenner.com/easing/), BSD License.
AJS.fx.Transitions = {
    linear: function(t, b, c, d) { return c*t/d + b; },
    sineInOut: function(t, b, c, d) { return -c/2 * (Math.cos(Math.PI*t/d) - 1) + b; }
};
