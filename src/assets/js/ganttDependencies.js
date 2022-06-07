(function ($) {
    $.extend($.fn, {
        livequery: function (type, fn, fn2) {
            var self = this, q;
            if ($.isFunction(type)) {
                fn2 = fn, fn = type, type = undefined;
            }
            $.each($.livequery.queries, function (i, query) {
                if (self.selector == query.selector && self.context == query.context && type == query.type && (!fn || fn.$lqguid == query.fn.$lqguid) && (!fn2 || fn2.$lqguid == query.fn2.$lqguid)) {
                    return (q = query) && false
                }
            });
            q = q || new $.livequery(this.selector, this.context, type, fn, fn2);
            q.stopped = false;
            q.run();
            return this
        }, expire: function (type, fn, fn2) {
            var self = this;
            if ($.isFunction(type)) {
                fn2 =
                    fn, fn = type, type = undefined;
            }
            $.each($.livequery.queries, function (i, query) {
                if (self.selector == query.selector && self.context == query.context && (!type || type == query.type) && (!fn || fn.$lqguid == query.fn.$lqguid) && (!fn2 || fn2.$lqguid == query.fn2.$lqguid) && !this.stopped) {
                    $.livequery.stop(
                        query.id)
                }
            });
            return this
        }
    });
    $.livequery = function (selector, context, type, fn, fn2) {
        this.selector = selector;
        this.context = context;
        this.type = type;
        this.fn = fn;
        this.fn2 = fn2;
        this.elements = [];
        this.stopped = false;
        this.id = $.livequery.queries.push(this) -
            1;
        fn.$lqguid = fn.$lqguid || $.livequery.guid++;
        if (fn2) {
            fn2.$lqguid = fn2.$lqguid || $.livequery.guid++;
        }
        return this
    };
    $.livequery.prototype = {
        stop: function () {
            var query = this;
            if (this.type) {
                this.elements.unbind(this.type, this.fn);
            } else {
                if (this.fn2) {
                    this.elements.each(function (i, el) {
                        query.fn2.apply(el)
                    });
                }
            }
            this.elements = [];
            this.stopped = true
        }, run: function () {
            if (this.stopped) {
                return;
            }
            var query = this;
            var oEls = this.elements, els = $(this.selector, this.context), nEls = els.not(oEls);
            this.elements = els;
            if (this.type) {
                nEls.bind(this.type,
                    this.fn);
                if (oEls.length > 0) {
                    $.each(oEls, function (i, el) {
                        if ($.inArray(el, els) < 0) {
                            $.event.remove(el, query.type, query.fn)
                        }
                    })
                }
            } else {
                nEls.each(function () {
                    query.fn.apply(this)
                });
                if (this.fn2 && oEls.length > 0) {
                    $.each(oEls, function (i, el) {
                        if ($.inArray(el, els) < 0) {
                            query.fn2.apply(el)
                        }
                    })
                }
            }
        }
    };
    $.extend($.livequery, {
        guid: 0, queries: [], queue: [], running: false, timeout: null, checkQueue: function () {
            if ($.livequery.running && $.livequery.queue.length) {
                var length = $.livequery.queue.length;
                while (length--) {
                    $.livequery.queries[$.livequery.queue.shift()].run()
                }
            }
        },
        pause: function () {
            $.livequery.running = false
        }, play: function () {
            $.livequery.running = true;
            $.livequery.run()
        }, registerPlugin: function () {
            $.each(arguments, function (i, n) {
                if (!$.fn[n]) {
                    return;
                }
                var old = $.fn[n];
                $.fn[n] = function () {
                    var r = old.apply(this, arguments);
                    $.livequery.run();
                    return r
                }
            })
        }, run: function (id) {
            if (id != undefined) {
                if ($.inArray(id, $.livequery.queue) < 0) {
                    $.livequery.queue.push(id)
                }
            } else {
                $.each($.livequery.queries, function (id) {
                    if ($.inArray(id, $.livequery.queue) < 0) {
                        $.livequery.queue.push(id)
                    }
                });
            }
            if ($.livequery.timeout) {
                clearTimeout($.livequery.timeout);
            }
            $.livequery.timeout = setTimeout($.livequery.checkQueue, 20)
        }, stop: function (id) {
            if (id != undefined) {
                $.livequery.queries[id].stop();
            } else {
                $.each($.livequery.queries, function (id) {
                    $.livequery.queries[id].stop()
                })
            }
        }
    });
    $.livequery.registerPlugin("append", "prepend", "after", "before", "wrap", "attr", "removeAttr", "addClass",
        "removeClass", "toggleClass", "empty", "remove", "html");
    $(function () {
        $.livequery.play()
    })
})(jQuery);

jQuery.fn.extend({
    everyTime: function (interval, label, fn, times, belay) {
        return this.each(function () {
            jQuery.timer.add(this, interval, label, fn, times, belay);
        });
    },
    oneTime: function (interval, label, fn) {
        return this.each(function () {
            jQuery.timer.add(this, interval, label, fn, 1);
        });
    },
    stopTime: function (label, fn) {
        return this.each(function () {
            jQuery.timer.remove(this, label, fn);
        });
    }
});

jQuery.extend({
    timer: {
        guid: 1,
        global: {},
        regex: /^([0-9]+)\s*(.*s)?$/,
        powers: {
            // Yeah this is major overkill...
            'ms': 1,
            'cs': 10,
            'ds': 100,
            's': 1000,
            'das': 10000,
            'hs': 100000,
            'ks': 1000000
        },
        timeParse: function (value) {
            if (value == undefined || value == null) {
                return null;
            }
            var result = this.regex.exec(jQuery.trim(value.toString()));
            if (result[2]) {
                var num = parseInt(result[1], 10);
                var mult = this.powers[result[2]] || 1;
                return num * mult;
            } else {
                return value;
            }
        },
        add: function (element, interval, label, fn, times, belay) {
            var counter = 0;

            if (jQuery.isFunction(label)) {
                if (!times) {
                    times = fn;
                }
                fn = label;
                label = interval;
            }

            interval = jQuery.timer.timeParse(interval);

            if (typeof interval != 'number' || isNaN(interval) || interval <= 0) {
                return;
            }

            if (times && times.constructor != Number) {
                belay = !!times;
                times = 0;
            }

            times = times || 0;
            belay = belay || false;

            if (!element.$timers) {
                element.$timers = {};
            }

            if (!element.$timers[label]) {
                element.$timers[label] = {};
            }

            fn.$timerID = fn.$timerID || this.guid++;

            var handler = function () {
                if (belay && this.inProgress) {
                    return;
                }
                this.inProgress = true;
                if ((++counter > times && times !== 0) || fn.call(element, counter) === false) {
                    jQuery.timer.remove(element, label, fn);
                }
                this.inProgress = false;
            };

            handler.$timerID = fn.$timerID;

            if (!element.$timers[label][fn.$timerID]) {
                element.$timers[label][fn.$timerID] = window.setInterval(handler, interval);
            }

            if (!this.global[label]) {
                this.global[label] = [];
            }
            this.global[label].push(element);

        },
        remove: function (element, label, fn) {
            var timers = element.$timers, ret;

            if (timers) {

                if (!label) {
                    for (label in timers) {
                        this.remove(element, label, fn);
                    }
                } else {
                    if (timers[label]) {
                        if (fn) {
                            if (fn.$timerID) {
                                window.clearInterval(timers[label][fn.$timerID]);
                                delete timers[label][fn.$timerID];
                            }
                        } else {
                            for (var fn in timers[label]) {
                                window.clearInterval(timers[label][fn]);
                                delete timers[label][fn];
                            }
                        }

                        for (ret in timers[label]) {
                            break;
                        }
                        if (!ret) {
                            ret = null;
                            delete timers[label];
                        }
                    }
                }

                for (ret in timers) {
                    break;
                }
                if (!ret) {
                    element.$timers = null;
                }
            }
        }
    }
});

jQuery(window).one("unload", function () {
    var global = jQuery.timer.global;
    for (var label in global) {
        var els = global[label], i = els.length;
        while (--i) {
            jQuery.timer.remove(els[i], label);
        }
    }
});

/*
 Copyright (c) 2012-2017 Open Lab
 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


// works also for IE8 beta
var isExplorer = navigator.userAgent.toUpperCase().indexOf("MSIE") >= 0 || !!navigator.userAgent.match(
    /Trident.*rv\:11\./);
var isMozilla = navigator.userAgent.toUpperCase().indexOf("FIREFOX") >= 0;
var isSafari = navigator.userAgent.toLowerCase().indexOf("safari") != -1 && navigator.userAgent.toLowerCase().indexOf(
    'chrome') < 0;

//Version detection
var version = navigator.appVersion.substring(0, 1);
var inProduction = false;
if (inProduction) {
    window.console = undefined;
}

// deprecated use $("#domid")...
function obj(element)
{
    if (arguments.length > 1) {
        alert("invalid use of obj with multiple params:" + element)
    }
    var el = document.getElementById(element);
    if (!el) {
        console.error("element not found: " + element);
    }
    return el;
}

if (!window.console) {
    window.console = new function () {
        this.log = function (str) {/*alert(str)*/
        };
        this.debug = function (str) {/*alert(str)*/
        };
        this.error = function (str) {/*alert(str)*/
        };
    };
}
if (!window.console.debug || !window.console.error || !window.console.log) {
    window.console = new function () {
        this.log = function (str) {/*alert(str)*/
        };
        this.debug = function (str) {/*alert(str)*/
        };
        this.error = function (str) {/*alert(str)*/
        };
    };
}


String.prototype.trim = function () {
    return this.replace(/^\s*(\S*(\s+\S+)*)\s*$/, "$1");
};

String.prototype.startsWith = function (t, i) {
    if (!i) {
        return (t == this.substring(0, t.length));
    } else {
        return (t.toLowerCase() == this.substring(0, t.length).toLowerCase());
    }
};

String.prototype.endsWith = function (t, i) {
    if (!i) {
        return (t == this.substring(this.length - t.length));
    } else {
        return (t.toLowerCase() == this.substring(this.length - t.length).toLowerCase());
    }
};

// leaves only char from A to Z, numbers, _ -> valid ID
String.prototype.asId = function () {
    return this.replace(/[^a-zA-Z0-9_]+/g, '');
};

String.prototype.replaceAll = function (from, to) {
    return this.replace(new RegExp(RegExp.quote(from), 'g'), to);
};


if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement, fromIndex) {
        if (this == null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n != n) { // shortcut for verifying if it's NaN
                n = 0;
            } else {
                if (n != 0 && n != Infinity && n != -Infinity) {
                    n = (n > 0 || -1) * Math.floor(Math.abs(n));
                }
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    };
}


Object.size = function (obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) {
            size++;
        }
    }
    return size;
};


// transform string values to printable: \n in <br>
function transformToPrintable(data)
{
    for (var prop in data) {
        var value = data[prop];
        if (typeof (value) == "string") {
            data[prop] = (value + "").replace(/\n/g, "<br>");
        }
    }
    return data;
}


RegExp.quote = function (str) {
    return str.replace(/([.?*+^$[\]\\(){}-])/g, "\\$1");
};


/* Object Functions */

function stopBubble(e)
{
    e.stopPropagation();
    e.preventDefault();
    return false;
}


// ------ ------- -------- wraps http://www.mysite.com/.......   with <a href="...">
jQuery.fn.activateLinks = function (showImages) {
    var httpRE = /(['"]\s*)?(http[s]?:[\d]*\/\/[^"<>\s]*)/g;
    var wwwRE = /(['"/]\s*)?(www\.[^"<>\s]+)/g;
    var imgRE = /(['"]\s*)?(http[s]?:[\d]*\/\/[^"<>\s]*\.(?:gif|jpg|png|jpeg|bmp))/g;


    this.each(function () {
        var el = $(this);
        var html = el.html();

        if (showImages) {
            // workaround for negative look ahead
            html = html.replace(imgRE, function ($0, $1) {
                return $1 ? $0 : "<div class='imgWrap'  onclick=\"window.open('" + $0 + "','_blank');event.stopPropagation();\"><img src='" + $0 + "' title='" + $0 + "'></div>";
            });
        }

        html = html.replace(httpRE, function ($0, $1) {
            return $1 ? $0 : "<a href='#' onclick=\"window.open('" + $0 + "','_blank');event.stopPropagation();\">" + $0 + "</a>";
        });

        html = html.replace(wwwRE, function ($0, $1) {
            return $1 ? $0 : "<a href='#' onclick=\"window.open('http://" + $0 + "','_blank');event.stopPropagation();\">" + $0 + "</a>";
        });

        el.empty().append(html);

        if (showImages) {
            //inject expand capability on images
            el.find("div.imgWrap").each(function () {
                var imageDiv = $(this);


                imageDiv.click(function (e) {
                    if (e.ctrlKey || e.metaKey) {
                        window.open(imageDiv.find("img").prop("src"), "_blank");
                    } else {
                        var imageClone = imageDiv.find("img").clone();
                        imageClone.mouseout(function () {
                            $(this).remove();
                        });
                        imageClone.addClass("imageClone").css({
                            "position": "absolute",
                            "display": "none",
                            "top": imageDiv.position().top,
                            "left": imageDiv.position().left,
                            "z-index": 1000000
                        });
                        imageDiv.after(imageClone);
                        imageClone.fadeIn();
                    }
                });
            });
        }

    });
    return this;
};

jQuery.fn.emoticonize = function () {
    function convert(text)
    {
        var faccRE = /(:\))|(:-\))|(:-])|(:-\()|(:\()|(:-\/)|(:-\\)|(:-\|)|(;-\))|(:-D)|(:-P)|(:-p)|(:-0)|(:-o)|(:-O)|(:'-\()|(\(@\))/g;
        return text.replace(faccRE, function (str) {
            var ret = {
                ":)": "smile",
                ":-)": "smile",
                ":-]": "polite_smile",
                ":-(": "frown",
                ":(": "frown",
                ":-/": "skepticism",
                ":-\\": "skepticism",
                ":-|": "sarcasm",
                ";-)": "wink",
                ":-D": "grin",
                ":-P": "tongue",
                ":-p": "tongue",
                ":-o": "surprise",
                ":-O": "surprise",
                ":-0": "surprise",
                ":'-(": "tear",
                "(@)": "angry"
            }[str];
            if (ret) {
                ret = "<img src='" + contextPath + "/img/smiley/" + ret + ".png' align='absmiddle'>";
                return ret;
            } else {
                return str;
            }
        });
    }

    function addBold(text)
    {
        var returnedValue;
        var faccRE = /\*\*[^*]*\*\*/ig;
        return text.replace(faccRE, function (str) {
            var temp = str.substr(2);
            var temp2 = temp.substr(0, temp.length - 2);
            return "<b>" + temp2 + "</b>";
        });
    }

    this.each(function () {
        var el = $(this);
        var html = convert(el.html());
        html = addBold(html);
        el.html(html);
    });
    return this;
};


$.fn.unselectable = function () {
    this.each(function () {
        $(this).addClass("unselectable").attr("unselectable", "on");
    });
    return $(this);
};

$.fn.clearUnselectable = function () {
    this.each(function () {
        $(this).removeClass("unselectable").removeAttr("unselectable");
    });
    return $(this);
};

// ---------------------------------- initialize management
var __initedComponents = new Object();

function initialize(url, type, ndo)
{
    //console.debug("initialize before: " + url);
    var normUrl = url.asId();
    var deferred = $.Deferred();

    if (!__initedComponents[normUrl]) {
        __initedComponents[normUrl] = deferred;

        if ("CSS" == (type + "").toUpperCase()) {
            var link = $("<link rel='stylesheet' type='text/css'>").prop("href", url);
            $("head").append(link);
            deferred.resolve();

        } else {
            if ("SCRIPT" == (type + "").toUpperCase()) {
                $.ajax({
                    type: "GET",
                    url: url + "?" + buildNumber,
                    dataType: "script",
                    cache: true,
                    success: function () {
                        //console.debug("initialize loaded:" + url);
                        deferred.resolve()
                    },
                    error: function () {
                        //console.debug("initialize failed:" + url);
                        deferred.reject();
                    }
                });


            } else {
                //console.debug(url+" as DOM");
                //var text = getContent(url);
                url = url + (url.indexOf("?") > -1 ? "&" : "?") + buildNumber;
                var text = $.ajax({
                    type: "GET",
                    url: url,
                    dataType: "html",
                    cache: true,
                    success: function (text) {
                        //console.debug("initialize loaded:" + url);
                        ndo = ndo || $("body");
                        ndo.append(text);
                        deferred.resolve()
                    },
                    error: function () {
                        //console.debug("initialize failed:" + url);
                        deferred.reject();
                    }
                });
            }
        }
    }

    return __initedComponents[normUrl].promise();
}


/**
 *  callback receive event, data
 *  data.response  contiene la response json arrivata dal controller
 *  E.G.:
 *     $("body").trigger("worklogEvent",[{type:"delete",response:response}])
 *
 *     in caso di delete di solito c'è il response.deletedId
 */
function registerEvent(eventName, callback)
{
    $("body").off(eventName).on(eventName, callback);
}


function openPersistentFile(file)
{
    //console.debug("openPersistentFile",file);
    var t = window.self;
    try {
        if (window.top != window.self) {
            t = window.top;
        }
    } catch (e) {
    }

    if (file.mime.indexOf("image") >= 0) {
        var img = $("<img>").prop("src", file.url).css({position: "absolute", top: "-10000px", left: "-10000px"}).one(
            "load", function () {
                //console.debug("image loaded");
                var img = $(this);
                var w = img.width();
                var h = img.height();
                //console.debug("image loaded",w,h);
                var f = w / h;
                var ww = $(t).width() * .8;
                var wh = $(t).height() * .8;
                if (w > ww) {
                    w = ww;
                    h = w / f;
                }
                if (h > wh) {
                    h = wh;
                    w = h * f;
                }

                var hasTop = false;
                img.width(w).height(h).css({position: "static", top: 0, left: 0});

                t.createModalPopup(w + 100, h + 100).append(img);
            });
        t.$("body").append(img);
    } else {
        if (file.mime.indexOf("pdf") >= 0) {
            t.openBlackPopup(file.url, $(t).width() * .8, $(t).height() * .8);
        } else {
            window.open(file.url + "&TREATASATTACH=yes");
        }
    }
}


function wrappedEvaluer(toEval)
{
    eval(toEval);
}

function evalInContext(stringToEval, context)
{
    wrappedEvaluer.apply(context, [stringToEval]);
}


Storage.prototype.setObject = function (key, value) {
    this.setItem(key, JSON.stringify(value));
};

Storage.prototype.getObject = function (key) {
    return this.getItem(key) && JSON.parse(this.getItem(key));
};

function objectSize(size)
{
    var divisor = 1;
    var unit = "bytes";
    if (size >= 1024 * 1024) {
        divisor = 1024 * 1024;
        unit = "MB";
    } else {
        if (size >= 1024) {
            divisor = 1024;
            unit = "KB";
        }
    }
    if (divisor == 1) {
        return size + " " + unit;
    }

    return (size / divisor).toFixed(2) + ' ' + unit;
}


function htmlEncode(value)
{
    //create a in-memory div, set it's inner text(which jQuery automatically encodes)
    //then grab the encoded contents back out.  The div never exists on the page.
    return $('<div/>').text(value).html();
}

function htmlLinearize(value, length)
{
    value = value.replace(/(\r\n|\n|\r)/gm, "").replace(/<br>/g, " - ");
    value = value.replace(/-  -/g, "-");

    var ret = $('<div/>').text(value).text();

    if (length) {
        var ellips = ret.length > length ? "..." : "";
        ret = ret.substring(0, length) + ellips;
    }

    return ret;
}

function htmlDecode(value)
{
    return $('<div/>').html(value).text();
}


function createCookie(name, value, days)
{
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        var expires = "; expires=" + date.toGMTString();
    } else {
        var expires = "";
    }
    document.cookie = name + "=" + value + expires + "; path=/";
}

function readCookie(name)
{
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1, c.length);
        }
        if (c.indexOf(nameEQ) == 0) {
            return c.substring(nameEQ.length, c.length);
        }
    }
    return null;
}

function eraseCookie(name)
{
    createCookie(name, "", -1);
}


function getParameterByName(name, url)
{
    if (!url) {
        url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) {
        return null;
    }
    if (!results[2]) {
        return '';
    }
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

$.fn.isEmptyElement = function () {
    return !$.trim($(this).html())
};

//workaround for jquery 3.x
if (typeof ($.fn.size) != "funcion") {
    $.fn.size = function () {
        return this.length
    };
}

/*
 Copyright (c) 2012-2017 Open Lab
 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

var muteAlertOnChange = false;


// isRequired ----------------------------------------------------------------------------

//return true if every mandatory field is filled and highlight empty ones
jQuery.fn.isFullfilled = function () {
    var canSubmit = true;
    var firstErrorElement = "";

    this.each(function () {
        var theElement = $(this);
        theElement.removeClass("formElementsError");
        //if (theElement.val().trim().length == 0 || theElement.attr("invalid") == "true") {  //robicch 13/2/15
        if (theElement.is("[required]") && theElement.val().trim().length == 0 || theElement.attr(
            "invalid") == "true") {
            if (theElement.attr("type") == "hidden") {
                theElement = theElement.prevAll("#" + theElement.prop("id") + "_txt:first");
            } else {
                if (theElement.is("[withTinyMCE]")) {
                    if (tinymce.activeEditor.getContent() == "") {
                        theElement = $("#" + theElement.attr("name") + "_tbl");
                    } else {
                        return true;
                    }// in order to continue the loop
                }
            }
            theElement.addClass("formElementsError");
            canSubmit = false;

            if (firstErrorElement == "") {
                firstErrorElement = theElement;
            }
        }
    });

    if (!canSubmit) {
        // get the tabdiv
        var theTabDiv = firstErrorElement.closest(".tabBox");
        if (theTabDiv.length > 0) {
            clickTab(theTabDiv.attr("tabId"));
        }

        // highlight element
        firstErrorElement.effect("highlight", {color: "red"}, 1500);
    }
    return canSubmit;

};

function canSubmitForm(formOrId)
{
    //console.debug("canSubmitForm",formOrId);
    if (typeof formOrId != "object") {
        formOrId = $("#" + formOrId);
    }
    return formOrId.find(":input[required],:input[invalid=true]").isFullfilled();
}

function showSavingMessage()
{
    $("#savingMessage:hidden").fadeIn();
    $("body").addClass("waiting");
    $(window).resize();
}

function hideSavingMessage()
{
    $("#savingMessage:visible").fadeOut();
    $("body").removeClass("waiting");
    $(window).resize();
}


/* Types Function */

function isValidURL(url)
{
    var RegExp = /^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/;
    return RegExp.test(url);
}

function isValidEmail(email)
{
    //var RegExp = /^((([a-z]|[0-9]|!|#|$|%|&|'|\*|\+|\-|\/|=|\?|\^|_|`|\{|\||\}|~)+(\.([a-z]|[0-9]|!|#|$|%|&|'|\*|\+|\-|\/|=|\?|\^|_|`|\{|\||\}|~)+)*)@((((([a-z]|[0-9])([a-z]|[0-9]|\-){0,61}([a-z]|[0-9])\.))*([a-z]|[0-9])([a-z]|[0-9]|\-){0,61}([a-z]|[0-9])\.)[\w]{2,4}|(((([0-9]){1,3}\.){3}([0-9]){1,3}))|(\[((([0-9]){1,3}\.){3}([0-9]){1,3})\])))$/;
    var RegExp = /^.+@\S+\.\S+$/;
    return RegExp.test(email);
}

function isValidInteger(n)
{
    reg = new RegExp("^[-+]{0,1}[0-9]*$");
    return reg.test(n) || isNumericExpression(n);
}

function isValidDouble(n)
{
    var sep = Number.decimalSeparator;
    reg = new RegExp("^[-+]{0,1}[0-9]*[" + sep + "]{0,1}[0-9]*$");
    return reg.test(n) || isNumericExpression(n);
}

function isValidTime(n)
{
    return !isNaN(millisFromHourMinute(n));
}

function isValidDurationDays(n)
{
    return !isNaN(daysFromString(n));
}

function isValidDurationMillis(n)
{
    return !isNaN(millisFromString(n));
}

function isNumericExpression(expr)
{
    try {
        var a = eval(expr);
        return typeof (a) == 'number';
    } catch (t) {
        return false;
    }

}

function getNumericExpression(expr)
{
    var ret;
    try {
        var a = eval(expr);
        if (typeof (a) == 'number') {
            ret = a;
        }
    } catch (t) {
    }
    return ret;

}

/*
 supports almost all Java currency format e.g.: ###,##0.00EUR   €#,###.00  #,###.00€  -$#,###.00  $-#,###.00
 */
function isValidCurrency(numStr)
{
    //first try to convert format in a regex
    var regex = "";
    var format = Number.currencyFormat + "";

    var minusFound = false;
    var numFound = false;
    var currencyString = "";
    var numberRegex = "[0-9\\" + Number.groupingSeparator + "]+[\\" + Number.decimalSeparator + "]?[0-9]*";

    for (var i = 0; i < format.length; i++) {
        var ch = format.charAt(i);

        if (ch == "." || ch == "," || ch == "0") {
            //skip it
            if (currencyString != "") {
                regex = regex + "(?:" + RegExp.quote(currencyString) + ")?";
                currencyString = "";
            }

        } else {
            if (ch == "#") {
                if (currencyString != "") {
                    regex = regex + "(?:" + RegExp.quote(currencyString) + ")?";
                    currencyString = "";
                }

                if (!numFound) {
                    numFound = true;
                    regex = regex + numberRegex;
                }

            } else {
                if (ch == "-") {
                    if (currencyString != "") {
                        regex = regex + "(?:" + RegExp.quote(currencyString) + ")?";
                        currencyString = "";
                    }
                    if (!minusFound) {
                        minusFound = true;
                        regex = regex + "[-]?";
                    }

                } else {
                    currencyString = currencyString + ch;
                }
            }
        }
    }
    if (!minusFound) {
        regex = "[-]?" + regex;
    }

    if (currencyString != "") {
        regex = regex + "(?:" + RegExp.quote(currencyString) + ")?";
    }

    regex = "^" + regex + "$";

    var rg = new RegExp(regex);
    return rg.test(numStr) || isNumericExpression(numStr);
}

function getCurrencyValue(numStr)
{
    if (!isValidCurrency(numStr)) {
        return NaN;
    }

    var ripul = numStr.replaceAll(Number.groupingSeparator, "").replaceAll(Number.decimalSeparator, ".");
    return getNumericExpression(ripul) || parseFloat(ripul.replace(/[^-0123456789.]/, ""));
}


function formatCurrency(numberString)
{
    return formatNumber(numberString, Number.currencyFormat);
}


function formatNumber(numberString, format)
{
    if (!format) {
        format = "##0.00";
    }

    var dec = Number.decimalSeparator;
    var group = Number.groupingSeparator;
    var neg = Number.minusSign;

    var round = true;

    var validFormat = "0#-,.";

    // strip all the invalid characters at the beginning and the end
    // of the format, and we'll stick them back on at the end
    // make a special case for the negative sign "-" though, so
    // we can have formats like -$23.32
    var prefix = "";
    var negativeInFront = false;
    for (var i = 0; i < format.length; i++) {
        if (validFormat.indexOf(format.charAt(i)) == -1) {
            prefix = prefix + format.charAt(i);
        } else {
            if (i == 0 && format.charAt(i) == '-') {
                negativeInFront = true;
            } else {
                break;
            }
        }
    }
    var suffix = "";
    for (var i = format.length - 1; i >= 0; i--) {
        if (validFormat.indexOf(format.charAt(i)) == -1) {
            suffix = format.charAt(i) + suffix;
        } else {
            break;
        }
    }

    format = format.substring(prefix.length);
    format = format.substring(0, format.length - suffix.length);

    // now we need to convert it into a number
    //while (numberString.indexOf(group) > -1)
    //	numberString = numberString.replace(group, '');
    //var number = new Number(numberString.replace(dec, ".").replace(neg, "-"));
    var number = new Number(numberString);


    var forcedToZero = false;
    if (isNaN(number)) {
        number = 0;
        forcedToZero = true;
    }

    // special case for percentages
    if (suffix == "%") {
        number = number * 100;
    }

    var returnString = "";
    if (format.indexOf(".") > -1) {
        var decimalPortion = dec;
        var decimalFormat = format.substring(format.lastIndexOf(".") + 1);

        // round or truncate number as needed
        if (round) {
            number = new Number(number.toFixed(decimalFormat.length));
        } else {
            var numStr = number.toString();
            numStr = numStr.substring(0, numStr.lastIndexOf('.') + decimalFormat.length + 1);
            number = new Number(numStr);
        }

        var decimalValue = number % 1;
        var decimalString = new String(decimalValue.toFixed(decimalFormat.length));
        decimalString = decimalString.substring(decimalString.lastIndexOf(".") + 1);

        for (var i = 0; i < decimalFormat.length; i++) {
            if (decimalFormat.charAt(i) == '#' && decimalString.charAt(i) != '0') {
                decimalPortion += decimalString.charAt(i);
            } else {
                if (decimalFormat.charAt(i) == '#' && decimalString.charAt(i) == '0') {
                    var notParsed = decimalString.substring(i);
                    if (notParsed.match('[1-9]')) {
                        decimalPortion += decimalString.charAt(i);
                    } else {
                        break;
                    }
                } else {
                    if (decimalFormat.charAt(i) == "0") {
                        decimalPortion += decimalString.charAt(i);
                    }
                }
            }
        }
        returnString += decimalPortion;
    } else {
        number = Math.round(number);
    }
    var ones = Math.floor(number);
    if (number < 0) {
        ones = Math.ceil(number);
    }

    var onesFormat = "";
    if (format.indexOf(".") == -1) {
        onesFormat = format;
    } else {
        onesFormat = format.substring(0, format.indexOf("."));
    }

    var onePortion = "";
    if (!(ones == 0 && onesFormat.substr(onesFormat.length - 1) == '#') || forcedToZero) {
        // find how many digits are in the group
        var oneText = new String(Math.abs(ones));
        var groupLength = 9999;
        if (onesFormat.lastIndexOf(",") != -1) {
            groupLength = onesFormat.length - onesFormat.lastIndexOf(",") - 1;
        }
        var groupCount = 0;
        for (var i = oneText.length - 1; i > -1; i--) {
            onePortion = oneText.charAt(i) + onePortion;
            groupCount++;
            if (groupCount == groupLength && i != 0) {
                onePortion = group + onePortion;
                groupCount = 0;
            }
        }

        // account for any pre-data padding
        if (onesFormat.length > onePortion.length) {
            var padStart = onesFormat.indexOf('0');
            if (padStart != -1) {
                var padLen = onesFormat.length - padStart;

                // pad to left with 0's or group char
                var pos = onesFormat.length - onePortion.length - 1;
                while (onePortion.length < padLen) {
                    var padChar = onesFormat.charAt(pos);
                    // replace with real group char if needed
                    if (padChar == ',') {
                        padChar = group;
                    }
                    onePortion = padChar + onePortion;
                    pos--;
                }
            }
        }
    }

    if (!onePortion && onesFormat.indexOf('0', onesFormat.length - 1) !== -1) {
        onePortion = '0';
    }

    returnString = onePortion + returnString;

    // handle special case where negative is in front of the invalid characters
    if (number < 0 && negativeInFront && prefix.length > 0) {
        prefix = neg + prefix;
    } else {
        if (number < 0) {
            returnString = neg + returnString;
        }
    }

    if (returnString.lastIndexOf(dec) == returnString.length - 1) {
        returnString = returnString.substring(0, returnString.length - 1);
    }
    returnString = prefix + returnString + suffix;
    return returnString;
}


//validation functions - used by textfield and datefield
jQuery.fn.validateField = function () {
    var isValid = true;

    this.each(function () {
        var el = $(this);
        el.clearErrorAlert();

        var value = el.val();
        if (value) {
            var rett = true;
            var type = (el.attr('entryType') + "").toUpperCase();
            var errParam;

            if (type == "INTEGER") {
                rett = isValidInteger(value);
            } else {
                if (type == "DOUBLE") {
                    rett = isValidDouble(value);
                } else {
                    if (type == "PERCENTILE") {
                        rett = isValidDouble(value);
                    } else {
                        if (type == "URL") {
                            rett = isValidURL(value);
                        } else {
                            if (type == "EMAIL") {
                                rett = isValidEmail(value);
                            } else {
                                if (type == "DURATIONMILLIS") {
                                    rett = isValidDurationMillis(value);
                                } else {
                                    if (type == "DURATIONDAYS") {
                                        rett = isValidDurationDays(value);
                                    } else {
                                        if (type == "DATE") {
                                            rett = Date.isValid(value, el.attr("format"), true);
                                            if (!rett) {
                                                errParam = el.attr("format");
                                            }
                                        } else {
                                            if (type == "TIME") {
                                                rett = isValidTime(value);
                                            } else {
                                                if (type == "CURRENCY") {
                                                    rett = isValidCurrency(value);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!rett) {
                el.createErrorAlert(i18n.ERROR_ON_FIELD, i18n.INVALID_DATA + (errParam ? " " + errParam : ""));
                isValid = false;
            }


            //check limits  minValue : maxValue
            if (rett && (el.attr("minValue") || el.attr("maxValue"))) {
                var val = value;
                var min = el.attr("minValue");
                var max = el.attr("maxValue");
                if (type == "INTEGER") {
                    val = parseInt(value);
                    min = parseInt(min);
                    max = parseInt(max);
                } else {
                    if (type == "DOUBLE" || type == "PERCENTILE") {
                        val = parseDouble(value);
                        min = parseDouble(min);
                        max = parseDouble(max);
                    } else {
                        if (type == "URL") {
                            val = value;
                        } else {
                            if (type == "EMAIL") {
                                val = value;
                            } else {
                                if (type == "DURATIONMILLIS") {
                                    val = millisFromString(value);
                                    min = millisFromString(min);
                                    max = millisFromString(max);

                                } else {
                                    if (type == "DURATIONDAYS") {
                                        val = daysFromString(value);
                                        min = daysFromString(min);
                                        max = daysFromString(max);
                                    } else {
                                        if (type == "DATE") {
                                            val = Date.parseString(value, el.attr("format"), true).getTime();
                                            min = Date.parseString(min, el.attr("format"), true).getTime();
                                            max = Date.parseString(max, el.attr("format"), true).getTime();
                                        } else {
                                            if (type == "TIME") {
                                                val = millisFromHourMinute(value);
                                                min = millisFromHourMinute(min);
                                                max = millisFromHourMinute(max);
                                            } else {
                                                if (type == "CURRENCY") {
                                                    val = getCurrencyValue(value);
                                                    min = getCurrencyValue(min);
                                                    max = getCurrencyValue(max);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (el.attr("minValue") && val < min) {
                    el.createErrorAlert(i18n.ERROR_ON_FIELD,
                        i18n.OUT_OF_BOUDARIES + " (" + el.attr("minValue") + " : " + (el.attr("maxValue") ? el.attr(
                            "maxValue") : "--") + ")");
                    rett = false;
                    isValid = false;

                    $("body").trigger("error");
                }

                if (rett && el.attr("maxValue") && val > max) {
                    el.createErrorAlert(i18n.ERROR_ON_FIELD,
                        i18n.OUT_OF_BOUDARIES + " (" + (el.attr("minValue") ? el.attr(
                            "minValue") : "--") + " : " + el.attr("maxValue") + ")");
                    rett = false;
                    isValid = false;
                }

            }

        }

    });

    return isValid;
};

jQuery.fn.clearErrorAlert = function () {
    this.each(function () {
        var el = $(this);
        el.removeAttr("invalid").removeClass("formElementsError");
        $("#" + el.prop("id") + "error").remove();
    });
    return this;
};

jQuery.fn.createErrorAlert = function (errorCode, message) {
    this.each(function () {
        var el = $(this);
        el.attr("invalid", "true").addClass("formElementsError");
        if ($("#" + el.prop("id") + "error").length <= 0) {
            var errMess = (errorCode ? errorCode : "") + ": " + (message ? message : "");
            var err = "<span class='formElementExclamation' id=\"" + el.prop("id") + "error\" error='1'";
            err += " onclick=\"alert($(this).attr('title'))\" border='0' align='absmiddle'>&nbsp;";
            err += "</span>\n";
            err = $(err);
            err.prop("title", errMess);
            el.after(err);
        }
    });
    return this;
};


// button submit support BEGIN ------------------

function saveFormValues(idForm)
{
    var formx = obj(idForm);
    formx.setAttribute("savedAction", formx.action);
    formx.setAttribute("savedTarget", formx.target);
    var el = formx.elements;
    for (i = 0; i < el.length; i++) {
        if (el[i].getAttribute("savedValue") != null) {
            el[i].setAttribute("savedValue", el[i].value);
        }
    }
}

function restoreFormValues(idForm)
{
    var formx = obj(idForm);
    formx.action = formx.getAttribute("savedAction");
    formx.target = formx.getAttribute("savedTarget");
    var el = formx.elements;
    for (i = 0; i < el.length; i++) {
        if (el[i].getAttribute("savedValue") != null) {
            el[i].value = el[i].getAttribute("savedValue");
        }
    }
}

function changeActionAndSubmit(action, command)
{
    var f = $("form:first");
    f.prop("action", action);
    f.find("[name=CM]").val(command);
    f.submit();
}


// textarea limit size -------------------------------------------------
function limitSize(ob)
{
    if (ob.getAttribute("maxlength")) {
        var ml = parseInt(ob.getAttribute("maxlength"));
        var val = ob.value;//.replace(/\r\n/g,"\n");
        if (val.length > ml) {
            ob.value = val.substr(0, ml);
            $(ob).createErrorAlert("Error", i18n.ERR_FIELD_MAX_SIZE_EXCEEDED);
        } else {
            $(ob).clearErrorAlert();
        }
    }
    return true;
}


// verify before unload BEGIN ----------------------------------------------------------------------------

function alertOnUnload(container)
{
    //console.debug("alertOnUnload",container,muteAlertOnChange);
    if (!muteAlertOnChange) {

        //first try to call a function eventually defined on the page
        if (typeof (managePageUnload) == "function") {
            managePageUnload();
        }

        container = container || $("body");
        var inps = $("[alertonchange=true]", container).find("[oldValue=1]");
        for (var j = 0; j < inps.length; j++) {
            var anInput = inps.eq(j);
            //console.debug(j,anInput,anInput.isValueChanged())
            var oldValue = anInput.getOldValue() + "";
            if (!('true' == '' + anInput.attr('excludeFromAlert'))) {
                if (anInput.attr("maleficoTiny")) {
                    if (tinymce.EditorManager.get(anInput.prop("id")).isDirty()) {
                        return i18n.FORM_IS_CHANGED + " \"" + anInput.prop("name") + "\"";
                    }

                } else {
                    if (anInput.isValueChanged()) {
                        var inputLabel = $("label[for='" + anInput.prop("id") + "']").text(); //use label element
                        inputLabel = inputLabel ? inputLabel : anInput.prop("name");
                        return i18n.FORM_IS_CHANGED + " \"" + inputLabel + "\"";
                    }
                }
            }
        }
    }
    return undefined;
}

function canILeave()
{
    var ret = window.onbeforeunload();
    if (typeof (ret) != "undefined" && !confirm(ret + "  \n" + i18n.PROCEED)) {
        return false;
    } else {
        return true;
    }
}

// ---------------------------------- oldvalues management
// update all values selected
jQuery.fn.updateOldValue = function () {
    this.each(function () {
        var el = $(this);
        var val = (el.is(":checkbox,:radio") ? el.prop("checked") : el.val()) + "";
        el.data("_oldvalue", val);
    });
    return this;
};

// return true if at least one element has changed
jQuery.fn.isValueChanged = function () {
    var ret = false;
    this.each(function () {
        var el = $(this);
        var val = (el.is(":checkbox,:radio") ? el.prop("checked") : el.val()) + "";
        if (val != el.data("_oldvalue") + "") {
            //console.debug("io sono diverso "+el.prop("id")+ " :"+el.val()+" != "+el.data("_oldvalue"));
            ret = true;
            return false;
        }
    });
    return ret;
};

jQuery.fn.getOldValue = function () {
    return $(this).data("_oldvalue");
};

jQuery.fn.fillJsonWithInputValues = function (jsonObject) {
    var inputs = this.find(":input");
    $.each(inputs.serializeArray(), function () {
        if (this.name) {
            jsonObject[this.name] = this.value;
        }
    });

    inputs.filter(":checkbox[name]").each(function () {
        var el = $(this);
        jsonObject[el.attr("name")] = el.is(":checked") ? "yes" : "no";

    })

    return this;
};


function enlargeTextArea(immediate)
{
    //console.debug("enlargeTextArea",immediate);
    var el = $(this);

    var delay = immediate === true ? 1 : 300;
    el.stopTime("taResizeApply");
    el.oneTime(delay, "taResizeApply", function () {

        var miH = el.is("[minHeight]") ? parseInt(el.attr("minHeight")) : 30;
        var maH = el.is("[maxHeight]") ? parseInt(el.attr("maxHeight")) : 400;
        var inc = el.is("[lineHeight]") ? parseInt(el.attr("lineHeight")) : 30;

        //si copiano nel css per sicurezza
        el.css({maxHeight: maH, minHeight: miH});

        var domEl = el.get(0);
        var pad = el.outerHeight() - el.height();
        //devo allargare
        if (domEl.scrollHeight > el.outerHeight() && el.outerHeight() < maH) {
            var nh = domEl.scrollHeight - pad + inc;
            nh = nh > maH - pad ? maH - pad : nh;
            el.height(nh);
        } else {
            if (el.height() > miH) {
                //devo stringere
                el.height(el.height() - inc);

                while (el.outerHeight() - domEl.scrollHeight > 0 && el.height() > miH) {
                    el.height(el.height() - inc);
                }
                var newH = domEl.scrollHeight - pad + inc;
                //newH=newH<minH?minH:newH;
                el.height(newH);

            }
        }
        el.stopTime("winResize");
    });

}

/**
 * Copyright (c)2005-2009 Matt Kruse (javascripttoolbox.com)
 * Dual licensed under the MIT and GPL licenses.
 * This basically means you can use this code however you want for
 */
/*
Date functions

These functions are used to parse, format, and manipulate Date objects.
See documentation and examples at http://www.JavascriptToolbox.com/lib/date/

*/
Date.$VERSION = 1.02;

// Utility function to append a 0 to single-digit numbers
Date.LZ = function (x) {
    return (x < 0 || x > 9 ? "" : "0") + x
};
// Full month names. Change this for local month names
Date.monthNames = new Array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
    'October', 'November', 'December');
// Month abbreviations. Change this for local month names
Date.monthAbbreviations = new Array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
// Full day names. Change this for local month names
Date.dayNames = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
// Day abbreviations. Change this for local month names
Date.dayAbbreviations = new Array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
// Used for parsing ambiguous dates like 1/2/2000 - default to preferring 'American' format meaning Jan 2.
// Set to false to prefer 'European' format meaning Feb 1
Date.preferAmericanFormat = true;

// Set to 0=SUn for American 1=Mon for european
Date.firstDayOfWeek = 0;

//default
Date.defaultFormat = "dd/MM/yyyy";

// If the getFullYear() method is not defined, create it
if (!Date.prototype.getFullYear) {
    Date.prototype.getFullYear = function () {
        var yy = this.getYear();
        return (yy < 1900 ? yy + 1900 : yy);
    };
}

// Parse a string and convert it to a Date object.
// If no format is passed, try a list of common formats.
// If string cannot be parsed, return null.
// Avoids regular expressions to be more portable.
Date.parseString = function (val, format, lenient) {
    // If no format is specified, try a few common formats
    if (typeof (format) == "undefined" || format == null || format == "") {
        var generalFormats = new Array(Date.defaultFormat, 'y-M-d', 'MMM d, y', 'MMM d,y', 'y-MMM-d', 'd-MMM-y',
            'MMM d', 'MMM-d', 'd-MMM');
        var monthFirst = new Array('M/d/y', 'M-d-y', 'M.d.y', 'M/d', 'M-d');
        var dateFirst = new Array('d/M/y', 'd-M-y', 'd.M.y', 'd/M', 'd-M');
        var checkList = new Array(generalFormats, Date.preferAmericanFormat ? monthFirst : dateFirst,
            Date.preferAmericanFormat ? dateFirst : monthFirst);
        for (var i = 0; i < checkList.length; i++) {
            var l = checkList[i];
            for (var j = 0; j < l.length; j++) {
                var d = Date.parseString(val, l[j]);
                if (d != null) {
                    return d;
                }
            }
        }
        return null;
    }
    ;

    this.isInteger = function (val) {
        for (var i = 0; i < val.length; i++) {
            if ("1234567890".indexOf(val.charAt(i)) == -1) {
                return false;
            }
        }
        return true;
    };
    this.getInt = function (str, i, minlength, maxlength) {
        for (var x = maxlength; x >= minlength; x--) {
            var token = str.substring(i, i + x);
            if (token.length < minlength) {
                return null;
            }
            if (this.isInteger(token)) {
                return token;
            }
        }
        return null;
    };


    this.decodeShortcut = function (str) {
        str = str ? str : ""; // just in case
        var dateUpper = str.trim().toUpperCase();
        var ret = new Date();
        ret.clearTime();

        if (["NOW", "N"].indexOf(dateUpper) >= 0) {
            ret = new Date();

        } else {
            if (["TODAY", "T"].indexOf(dateUpper) >= 0) {
                //do nothing

            } else {
                if (["YESTERDAY", "Y"].indexOf(dateUpper) >= 0) {
                    ret.setDate(ret.getDate() - 1);

                } else {
                    if (["TOMORROW", "TO"].indexOf(dateUpper) >= 0) {
                        ret.setDate(ret.getDate() + 1);

                    } else {
                        if (["W", "TW", "WEEK", "THISWEEK", "WEEKSTART", "THISWEEKSTART"].indexOf(dateUpper) >= 0) {
                            ret.setFirstDayOfThisWeek();

                        } else {
                            if (["LW", "LASTWEEK", "LASTWEEKSTART"].indexOf(dateUpper) >= 0) {
                                ret.setFirstDayOfThisWeek();
                                ret.setDate(ret.getDate() - 7);

                            } else {
                                if (["NW", "NEXTWEEK", "NEXTWEEKSTART"].indexOf(dateUpper) >= 0) {
                                    ret.setFirstDayOfThisWeek();
                                    ret.setDate(ret.getDate() + 7);

                                } else {
                                    if (["M", "TM", "MONTH", "THISMONTH", "MONTHSTART", "THISMONTHSTART"].indexOf(
                                        dateUpper) >= 0) {
                                        ret.setDate(1);

                                    } else {
                                        if (["LM", "LASTMONTH", "LASTMONTHSTART"].indexOf(dateUpper) >= 0) {
                                            ret.setDate(1);
                                            ret.setMonth(ret.getMonth() - 1);

                                        } else {
                                            if (["NM", "NEXTMONTH", "NEXTMONTHSTART"].indexOf(dateUpper) >= 0) {
                                                ret.setDate(1);
                                                ret.setMonth(ret.getMonth() + 1);

                                            } else {
                                                if ([
                                                    "Q",
                                                    "TQ",
                                                    "QUARTER",
                                                    "THISQUARTER",
                                                    "QUARTERSTART",
                                                    "THISQUARTERSTART"
                                                ].indexOf(dateUpper) >= 0) {
                                                    ret.setDate(1);
                                                    ret.setMonth(Math.floor((ret.getMonth()) / 3) * 3);

                                                } else {
                                                    if (["LQ", "LASTQUARTER", "LASTQUARTERSTART"].indexOf(
                                                        dateUpper) >= 0) {
                                                        ret.setDate(1);
                                                        ret.setMonth(Math.floor((ret.getMonth()) / 3) * 3 - 3);

                                                    } else {
                                                        if (["NQ", "NEXTQUARTER", "NEXTQUARTERSTART"].indexOf(
                                                            dateUpper) >= 0) {
                                                            ret.setDate(1);
                                                            ret.setMonth(Math.floor((ret.getMonth()) / 3) * 3 + 3);


                                                        } else {
                                                            if (/^-?[0-9]+[DWMY]$/.test(dateUpper)) {
                                                                var lastOne = dateUpper.substr(dateUpper.length - 1);
                                                                var val = parseInt(
                                                                    dateUpper.substr(0, dateUpper.length - 1));
                                                                if (lastOne == "W") {
                                                                    ret.setDate(ret.getDate() + val * 7);
                                                                } else {
                                                                    if (lastOne == "M") {
                                                                        ret.setMonth(ret.getMonth() + val);
                                                                    } else {
                                                                        if (lastOne == "Y") {
                                                                            ret.setYear(ret.getYear() + val);
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                ret = undefined;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return ret;
    };

    var ret = this.decodeShortcut(val);
    if (ret) {
        return ret;
    }

    this._getDate = function (val, format) {
        val = val + "";
        format = format + "";
        var i_val = 0;
        var i_format = 0;
        var c = "";
        var token = "";
        var token2 = "";
        var x, y;
        var year = new Date().getFullYear();
        var month = 1;
        var date = 1;
        var hh = 0;
        var mm = 0;
        var ss = 0;
        var ampm = "";
        while (i_format < format.length) {
            // Get next token from format string
            c = format.charAt(i_format);
            token = "";
            while ((format.charAt(i_format) == c) && (i_format < format.length)) {
                token += format.charAt(i_format++);
            }
            // Extract contents of value based on format token
            if (token == "yyyy" || token == "yy" || token == "y") {
                if (token == "yyyy") {
                    x = 4;
                    y = 4;
                }
                if (token == "yy") {
                    x = 2;
                    y = 2;
                }
                if (token == "y") {
                    x = 2;
                    y = 4;
                }
                year = this.getInt(val, i_val, x, y);
                if (year == null) {
                    return null;
                }
                i_val += year.length;
                if (year.length == 2) {
                    if (year > 70) {
                        year = 1900 + (year - 0);
                    } else {
                        year = 2000 + (year - 0);
                    }
                }

                //		} else if (token=="MMM" || token=="NNN"){
            } else {
                if (token == "MMM" || token == "MMMM") {
                    month = 0;
                    var names = (token == "MMMM" ? (Date.monthNames.concat(
                        Date.monthAbbreviations)) : Date.monthAbbreviations);
                    for (var i = 0; i < names.length; i++) {
                        var month_name = names[i];
                        if (val.substring(i_val, i_val + month_name.length).toLowerCase() == month_name.toLowerCase()) {
                            month = (i % 12) + 1;
                            i_val += month_name.length;
                            break;
                        }
                    }
                    if ((month < 1) || (month > 12)) {
                        return null;
                    }
                } else {
                    if (token == "E" || token == "EE" || token == "EEE" || token == "EEEE") {
                        var names = (token == "EEEE" ? Date.dayNames : Date.dayAbbreviations);
                        for (var i = 0; i < names.length; i++) {
                            var day_name = names[i];
                            if (val.substring(i_val, i_val + day_name.length).toLowerCase() == day_name.toLowerCase()) {
                                i_val += day_name.length;
                                break;
                            }
                        }
                    } else {
                        if (token == "MM" || token == "M") {
                            month = this.getInt(val, i_val, token.length, 2);
                            if (month == null || (month < 1) || (month > 12)) {
                                return null;
                            }
                            i_val += month.length;
                        } else {
                            if (token == "dd" || token == "d") {
                                date = this.getInt(val, i_val, token.length, 2);
                                if (date == null || (date < 1) || (date > 31)) {
                                    return null;
                                }
                                i_val += date.length;
                            } else {
                                if (token == "hh" || token == "h") {
                                    hh = this.getInt(val, i_val, token.length, 2);
                                    if (hh == null || (hh < 1) || (hh > 12)) {
                                        return null;
                                    }
                                    i_val += hh.length;
                                } else {
                                    if (token == "HH" || token == "H") {
                                        hh = this.getInt(val, i_val, token.length, 2);
                                        if (hh == null || (hh < 0) || (hh > 23)) {
                                            return null;
                                        }
                                        i_val += hh.length;
                                    } else {
                                        if (token == "KK" || token == "K") {
                                            hh = this.getInt(val, i_val, token.length, 2);
                                            if (hh == null || (hh < 0) || (hh > 11)) {
                                                return null;
                                            }
                                            i_val += hh.length;
                                            hh++;
                                        } else {
                                            if (token == "kk" || token == "k") {
                                                hh = this.getInt(val, i_val, token.length, 2);
                                                if (hh == null || (hh < 1) || (hh > 24)) {
                                                    return null;
                                                }
                                                i_val += hh.length;
                                                hh--;
                                            } else {
                                                if (token == "mm" || token == "m") {
                                                    mm = this.getInt(val, i_val, token.length, 2);
                                                    if (mm == null || (mm < 0) || (mm > 59)) {
                                                        return null;
                                                    }
                                                    i_val += mm.length;
                                                } else {
                                                    if (token == "ss" || token == "s") {
                                                        ss = this.getInt(val, i_val, token.length, 2);
                                                        if (ss == null || (ss < 0) || (ss > 59)) {
                                                            return null;
                                                        }
                                                        i_val += ss.length;
                                                    } else {
                                                        if (token == "a") {
                                                            if (val.substring(i_val, i_val + 2).toLowerCase() == "am") {
                                                                ampm = "AM";
                                                            } else {
                                                                if (val.substring(i_val,
                                                                    i_val + 2).toLowerCase() == "pm") {
                                                                    ampm = "PM";
                                                                } else {
                                                                    return null;
                                                                }
                                                            }
                                                            i_val += 2;
                                                        } else {
                                                            if (val.substring(i_val, i_val + token.length) != token) {
                                                                return null;
                                                            } else {
                                                                i_val += token.length;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // If there are any trailing characters left in the value, it doesn't match
        if (i_val != val.length) {
            return null;
        }
        // Is date valid for month?
        if (month == 2) {
            // Check for leap year
            if (((year % 4 == 0) && (year % 100 != 0)) || (year % 400 == 0)) { // leap year
                if (date > 29) {
                    return null;
                }
            } else {
                if (date > 28) {
                    return null;
                }
            }
        }
        if ((month == 4) || (month == 6) || (month == 9) || (month == 11)) {
            if (date > 30) {
                return null;
            }
        }
        // Correct hours value
        if (hh < 12 && ampm == "PM") {
            hh = hh - 0 + 12;
        } else {
            if (hh > 11 && ampm == "AM") {
                hh -= 12;
            }
        }
        return new Date(year, month - 1, date, hh, mm, ss);
    };

    var theDate = this._getDate(val, format);
    if (!theDate && lenient) {
        //try with short format
        var f = format.replace("MMMM", "M").replace("MMM", "M").replace("MM", "M")
            .replace("yyyy", "y").replace("yyy", "y").replace("yy", "y")
            .replace("dd", "d");
        //console.debug("second round with format "+f);
        return this._getDate(val, f);
    } else {
        return theDate;
    }

};

// Check if a date string is valid
Date.isValid = function (val, format, lenient) {
    return (Date.parseString(val, format, lenient) != null);
};

// Check if a date object is before another date object
Date.prototype.isBefore = function (date2) {
    if (date2 == null) {
        return false;
    }
    return (this.getTime() < date2.getTime());
};

// Check if a date object is after another date object
Date.prototype.isAfter = function (date2) {
    if (date2 == null) {
        return false;
    }
    return (this.getTime() > date2.getTime());
};

Date.prototype.isOutOfRange = function (minDate, maxDate) {

    minDate = minDate || this;
    maxDate = maxDate || this;

    if (typeof minDate == "string") {
        minDate = Date.parseString(minDate);
    }

    if (typeof maxDate == "string") {
        maxDate = Date.parseString(maxDate);
    }

    return (this.isBefore(minDate) || this.isAfter(maxDate));
};

// Check if two date objects have equal dates and times
Date.prototype.equals = function (date2) {
    if (date2 == null) {
        return false;
    }
    return (this.getTime() == date2.getTime());
};

// Check if two date objects have equal dates, disregarding times
Date.prototype.equalsIgnoreTime = function (date2) {
    if (date2 == null) {
        return false;
    }
    var d1 = new Date(this.getTime()).clearTime();
    var d2 = new Date(date2.getTime()).clearTime();
    return (d1.getTime() == d2.getTime());
};

/**
 * Get week number in the year.
 */
Date.prototype.getWeekNumber = function () {
    var d = new Date(+this);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 4 - (d.getDay() || 7));
    return Math.ceil((((d - new Date(d.getFullYear(), 0, 1)) / 8.64e7) + 1) / 7);
};

// Format a date into a string using a given format string
Date.prototype.format = function (format) {
    if (!format) {
        format = Date.defaultFormat;
    }
    format = format + "";
    var result = "";
    var i_format = 0;
    var c = "";
    var token = "";
    var y = this.getFullYear() + "";
    var M = this.getMonth() + 1;
    var d = this.getDate();
    var E = this.getDay();
    var H = this.getHours();
    var m = this.getMinutes();
    var s = this.getSeconds();
    var w = this.getWeekNumber();
    // Convert real date parts into formatted versions
    var value = new Object();
    if (y.length < 4) {
        y = "" + (+y + 1900);
    }
    value["y"] = "" + y;
    value["yyyy"] = y;
    value["yy"] = y.substring(2, 4);
    value["M"] = M;
    value["MM"] = Date.LZ(M);
    value["MMM"] = Date.monthAbbreviations[M - 1];
    value["MMMM"] = Date.monthNames[M - 1];
    value["d"] = d;
    value["dd"] = Date.LZ(d);
    value["E"] = Date.dayAbbreviations[E];
    value["EE"] = Date.dayAbbreviations[E];
    value["EEE"] = Date.dayAbbreviations[E];
    value["EEEE"] = Date.dayNames[E];
    value["H"] = H;
    value["HH"] = Date.LZ(H);
    value["w"] = w;
    value["ww"] = Date.LZ(w);
    if (H == 0) {
        value["h"] = 12;
    } else {
        if (H > 12) {
            value["h"] = H - 12;
        } else {
            value["h"] = H;
        }
    }
    value["hh"] = Date.LZ(value["h"]);
    value["K"] = value["h"] - 1;
    value["k"] = value["H"] + 1;
    value["KK"] = Date.LZ(value["K"]);
    value["kk"] = Date.LZ(value["k"]);
    if (H > 11) {
        value["a"] = "PM";
    } else {
        value["a"] = "AM";
    }
    value["m"] = m;
    value["mm"] = Date.LZ(m);
    value["s"] = s;
    value["ss"] = Date.LZ(s);
    while (i_format < format.length) {
        c = format.charAt(i_format);
        token = "";
        while ((format.charAt(i_format) == c) && (i_format < format.length)) {
            token += format.charAt(i_format++);
        }
        if (typeof (value[token]) != "undefined") {
            result = result + value[token];
        } else {
            result = result + token;
        }
    }
    return result;
};

// Get the full name of the day for a date
Date.prototype.getDayName = function () {
    return Date.dayNames[this.getDay()];
};

// Get the abbreviation of the day for a date
Date.prototype.getDayAbbreviation = function () {
    return Date.dayAbbreviations[this.getDay()];
};

// Get the full name of the month for a date
Date.prototype.getMonthName = function () {
    return Date.monthNames[this.getMonth()];
};

// Get the abbreviation of the month for a date
Date.prototype.getMonthAbbreviation = function () {
    return Date.monthAbbreviations[this.getMonth()];
};

// Clear all time information in a date object
Date.prototype.clearTime = function () {
    this.setHours(0);
    this.setMinutes(0);
    this.setSeconds(0);
    this.setMilliseconds(0);
    return this;
};

// Add an amount of time to a date. Negative numbers can be passed to subtract time.
Date.prototype.add = function (interval, number) {
    if (typeof (interval) == "undefined" || interval == null || typeof (number) == "undefined" || number == null) {
        return this;
    }
    number = +number;
    if (interval == 'y') { // year
        this.setFullYear(this.getFullYear() + number);
    } else {
        if (interval == 'M') { // Month
            this.setMonth(this.getMonth() + number);
        } else {
            if (interval == 'd') { // Day
                this.setDate(this.getDate() + number);
            } else {
                if (interval == 'w') { // Week
                    this.setDate(this.getDate() + number * 7);
                } else {
                    if (interval == 'h') { // Hour
                        this.setHours(this.getHours() + number);
                    } else {
                        if (interval == 'm') { // Minute
                            this.setMinutes(this.getMinutes() + number);
                        } else {
                            if (interval == 's') { // Second
                                this.setSeconds(this.getSeconds() + number);
                            }
                        }
                    }
                }
            }
        }
    }
    return this;

};

Date.prototype.toInt = function () {
    return this.getFullYear() * 10000 + (this.getMonth() + 1) * 100 + this.getDate();
};

Date.fromInt = function (dateInt) {
    var year = parseInt(dateInt / 10000);
    var month = parseInt((dateInt - year * 10000) / 100);
    var day = parseInt(dateInt - year * 10000 - month * 100);
    return new Date(year, month - 1, day, 12, 0, 0);
};


Date.prototype.isHoliday = function () {
    return isHoliday(this);
};

Date.prototype.isToday = function () {
    return this.toInt() == new Date().toInt();
};


Date.prototype.incrementDateByWorkingDays = function (days) {
    //console.debug("incrementDateByWorkingDays start ",d,days)
    var q = Math.abs(days);
    while (q > 0) {
        this.setDate(this.getDate() + (days > 0 ? 1 : -1));
        if (!this.isHoliday()) {
            q--;
        }
    }
    return this;
};


Date.prototype.distanceInDays = function (toDate) {
    // Discard the time and time-zone information.
    var utc1 = Date.UTC(this.getFullYear(), this.getMonth(), this.getDate());
    var utc2 = Date.UTC(toDate.getFullYear(), toDate.getMonth(), toDate.getDate());
    return Math.floor((utc2 - utc1) / (3600000 * 24));
};

//low performances in case of long distance
/*Date.prototype.distanceInWorkingDays= function (toDate){
  var pos = new Date(this.getTime());
  pos.setHours(23, 59, 59, 999);
  var days = 0;
  var nd=new Date(toDate.getTime());
  nd.setHours(23, 59, 59, 999);
  var end=nd.getTime();
  while (pos.getTime() <= end) {
    days = days + (isHoliday(pos) ? 0 : 1);
    pos.setDate(pos.getDate() + 1);
  }
  return days;
};*/

//low performances in case of long distance
// bicch 22/4/2016: modificato per far ritornare anche valori negativi, così come la controparte Java in CompanyCalendar.
// attenzione che prima tornava 1 per due date uguali adesso torna 0
Date.prototype.distanceInWorkingDays = function (toDate) {
    var pos = new Date(Math.min(this, toDate));
    pos.setHours(12, 0, 0, 0);
    var days = 0;
    var nd = new Date(Math.max(this, toDate));
    nd.setHours(12, 0, 0, 0);
    while (pos < nd) {
        days = days + (isHoliday(pos) ? 0 : 1);
        pos.setDate(pos.getDate() + 1);
    }
    days = days * (this > toDate ? -1 : 1);

    //console.debug("distanceInWorkingDays",this,toDate,days);
    return days;
};

Date.prototype.setFirstDayOfThisWeek = function (firstDayOfWeek) {
    if (!firstDayOfWeek) {
        firstDayOfWeek = Date.firstDayOfWeek;
    }
    this.setDate(this.getDate() - this.getDay() + firstDayOfWeek - (this.getDay() == 0 && firstDayOfWeek != 0 ? 7 : 0));
    return this;
};


/* ----- millis format --------- */
/**
 * @param         str         - Striga da riempire
 * @param         len         - Numero totale di caratteri, comprensivo degli "zeri"
 * @param         ch          - Carattere usato per riempire
 */

function pad(str, len, ch)
{
    if ((str + "").length < len) {
        return new Array(len - ('' + str).length + 1).join(ch) + str;
    } else {
        return str
    }
}

function getMillisInHours(millis)
{
    if (!millis) {
        return "";
    }
    var hour = Math.floor(millis / 3600000);
    return (millis >= 0 ? "" : "-") + pad(hour, 1, "0");
}

function getMillisInHoursMinutes(millis)
{
    if (typeof (millis) != "number") {
        return "";
    }

    var sgn = millis >= 0 ? 1 : -1;
    millis = Math.abs(millis);
    var hour = Math.floor(millis / 3600000);
    var min = Math.floor((millis % 3600000) / 60000);
    return (sgn > 0 ? "" : "-") + pad(hour, 1, "0") + ":" + pad(min, 2, "0");
}

function getMillisInDaysHoursMinutes(millis)
{
    if (!millis) {
        return "";
    }
    // millisInWorkingDay is set on partHeaderFooter
    var sgn = millis >= 0 ? 1 : -1;
    millis = Math.abs(millis);
    var days = Math.floor(millis / millisInWorkingDay);
    var hour = Math.floor((millis % millisInWorkingDay) / 3600000);
    var min = Math.floor((millis - days * millisInWorkingDay - hour * 3600000) / 60000);
    return (sgn >= 0 ? "" : "-") + (days > 0 ? days + "  " : "") + pad(hour, 1, "0") + ":" + pad(min, 2, "0");
}


function millisToString(millis, considerWorkingdays)
{
    // console.debug("millisToString",millis)
    if (!millis) {
        return "";
    }
    // millisInWorkingDay is set on partHeaderFooter
    var sgn = millis >= 0 ? 1 : -1;
    millis = Math.abs(millis);
    var wm = (considerWorkingdays ? millisInWorkingDay : 3600000 * 24);
    var days = Math.floor(millis / wm);
    var hour = Math.floor((millis % wm) / 3600000);
    var min = Math.floor((millis - days * wm - hour * 3600000) / 60000);
    var sec = Math.floor((millis - days * wm - hour * 3600000 - min * 60000) / 1000);
    //console.debug("millisToString",wm, millis,days,hour,min)
    return (sgn >= 0 ? "" : "-") + (days > 0 ? days + "d " : "") + (hour > 0 ? (days > 0 ? " " : "") + hour + "h" : "") + (min > 0 ? (days > 0 || hour > 0 ? " " : "") + min + "m" : "") + (sec > 0 ? +sec + "s" : "");
}


function millisFromHourMinute(stringHourMinutes)
{ //All this format are valid: "12:58" "13.75"  "63635676000" (this is already in milliseconds)
    var semiColSeparator = stringHourMinutes.indexOf(":");
    if (semiColSeparator == 0) { // :30 minutes
        return millisFromHourMinuteSecond("00" + stringHourMinutes + ":00");
    } else {
        if (semiColSeparator > 0) {// 1:15 hours:minutes
            return millisFromHourMinuteSecond(stringHourMinutes + ":00");
        } else {
            return millisFromHourMinuteSecond(stringHourMinutes);
        }
    }
}

function millisFromHourMinuteSecond(stringHourMinutesSeconds)
{ //All this format are valid: "00:12:58" "12:58:55" "13.75"  "63635676000" (this is already in milliseconds)
    var result = 0;
    stringHourMinutesSeconds.replace(",", ".");
    var semiColSeparator = stringHourMinutesSeconds.indexOf(":");
    var dotSeparator = stringHourMinutesSeconds.indexOf(".");

    if (semiColSeparator < 0 && dotSeparator < 0 && stringHourMinutesSeconds.length > 5) {
        return parseInt(stringHourMinutesSeconds, 10); //already in millis
    } else {

        if (dotSeparator > -1) {
            var d = parseFloat(stringHourMinutesSeconds);
            result = d * 3600000;
        } else {
            var hour = 0;
            var minute = 0;
            var second = 0;

            if (semiColSeparator == -1) {
                hour = parseInt(stringHourMinutesSeconds, 10);
            } else {

                var units = stringHourMinutesSeconds.split(":")

                hour = parseInt(units[0], 10);
                minute = parseInt(units[1], 10);
                second = parseInt(units[2], 10);
            }
            result = hour * 3600000 + minute * 60000 + second * 1000;
        }
        if (typeof (result) != "number") {
            result = NaN;
        }
        return result;
    }
}


/**
 * @param string              "3y 4d", "4D:08:10", "12M/3d", "1.5D", "2H4D", "3M4d,2h", "12:30", "11", "3", "1.5", "2m/3D", "12/3d", "1234"
 *                            by default 2 means 2 hours 1.5 means 1:30
 * @param considerWorkingdays if true day length is from global.properties CompanyCalendar.MILLIS_IN_WORKING_DAY  otherwise in 24
 * @return milliseconds. 0 if invalid string
 */
function millisFromString(string, considerWorkingdays)
{
    if (!string) {
        return 0;
    }

    //var regex = new RegExp("(\\d+[Yy])|(\\d+[M])|(\\d+[Ww])|(\\d+[Dd])|(\\d+[Hh])|(\\d+[m])|(\\d+[Ss])|(\\d+:\\d+)|(:\\d+)|(\\d*[\\.,]\\d+)|(\\d+)", "g"); // bicch 14/1/16 supporto per 1.5d
    var regex = new RegExp(
        "([0-9\\.,]+[Yy])|([0-9\\.,]+[Qq])|([0-9\\.,]+[M])|([0-9\\.,]+[Ww])|([0-9\\.,]+[Dd])|([0-9\\.,]+[Hh])|([0-9\\.,]+[m])|([0-9\\.,]+[Ss])|(\\d+:\\d+:\\d+)|(\\d+:\\d+)|(:\\d+)|(\\d*[\\.,]\\d+)|(\\d+)",
        "g");

    var matcher = regex.exec(string);
    var totMillis = 0;

    if (!matcher) {
        return NaN;
    }

    while (matcher != null) {
        for (var i = 1; i < matcher.length; i++) {
            var match = matcher[i];
            if (match) {
                var number = 0;
                try {
                    //number = parseInt(match); // bicch 14/1/16 supporto per 1.5d
                    number = parseFloat(match.replace(',', '.'));
                } catch (e) {
                }
                if (i == 1) { // years
                    totMillis = totMillis + number * (considerWorkingdays ? millisInWorkingDay * workingDaysPerWeek * 52 : 3600000 * 24 * 365);
                } else {
                    if (i == 2) { // quarter
                        totMillis = totMillis + number * (considerWorkingdays ? millisInWorkingDay * workingDaysPerWeek * 4 : 3600000 * 24 * 91);
                    } else {
                        if (i == 3) { // months
                            totMillis = totMillis + number * (considerWorkingdays ? millisInWorkingDay * workingDaysPerWeek * 4 : 3600000 * 24 * 30);
                        } else {
                            if (i == 4) { // weeks
                                totMillis = totMillis + number * (considerWorkingdays ? millisInWorkingDay * workingDaysPerWeek : 3600000 * 24 * 7);
                            } else {
                                if (i == 5) { // days
                                    totMillis = totMillis + number * (considerWorkingdays ? millisInWorkingDay : 3600000 * 24);
                                } else {
                                    if (i == 6) { // hours
                                        totMillis = totMillis + number * 3600000;
                                    } else {
                                        if (i == 7) { // minutes
                                            totMillis = totMillis + number * 60000;
                                        } else {
                                            if (i == 8) { // seconds
                                                totMillis = totMillis + number * 1000;
                                            } else {
                                                if (i == 9) { // hour:minutes:seconds
                                                    totMillis = totMillis + millisFromHourMinuteSecond(match);
                                                } else {
                                                    if (i == 10) { // hour:minutes
                                                        totMillis = totMillis + millisFromHourMinute(match);
                                                    } else {
                                                        if (i == 11) { // :minutes
                                                            totMillis = totMillis + millisFromHourMinute(match);
                                                        } else {
                                                            if (i == 12) { // hour.minutes
                                                                totMillis = totMillis + millisFromHourMinute(match);
                                                            } else {
                                                                if (i == 13) { // hours
                                                                    totMillis = totMillis + number * 3600000;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        matcher = regex.exec(string);
    }

    return totMillis;
}

/**
 * @param string              "3y 4d", "4D:08:10", "12M/3d", "2H4D", "3M4d,2h", "12:30", "11", "3", "1.5", "2m/3D", "12/3d", "1234"
 *                            by default 2 means 2 hours 1.5 means 1:30
 * @param considerWorkingdays if true day length is from global.properties CompanyCalendar.MILLIS_IN_WORKING_DAY  otherwise in 24
 * @return milliseconds. 0 if invalid string
 */
function daysFromString(string, considerWorkingdays)
{
    if (!string) {
        return undefined;
    }

    //var regex = new RegExp("(\\d+[Yy])|(\\d+[Mm])|(\\d+[Ww])|(\\d+[Dd])|(\\d*[\\.,]\\d+)|(\\d+)", "g"); // bicch 14/1/16 supporto per 1.5d
    //var regex = new RegExp("([0-9\\.,]+[Yy])|([0-9\\.,]+[Qq])|([0-9\\.,]+[Mm])|([0-9\\.,]+[Ww])|([0-9\\.,]+[Dd])|(\\d*[\\.,]\\d+)|(\\d+)", "g");
    var regex = new RegExp(
        "([\\-]?[0-9\\.,]+[Yy])|([\\-]?[0-9\\.,]+[Qq])|([\\-]?[0-9\\.,]+[Mm])|([\\-]?[0-9\\.,]+[Ww])|([\\-]?[0-9\\.,]+[Dd])|([\\-]?\\d*[\\.,]\\d+)|([\\-]?\\d+)",
        "g");

    var matcher = regex.exec(string);
    var totDays = 0;

    if (!matcher) {
        return NaN;
    }

    while (matcher != null) {
        for (var i = 1; i < matcher.length; i++) {
            var match = matcher[i];
            if (match) {
                var number = 0;
                try {
                    number = parseInt(match);// bicch 14/1/16 supporto per 1.5d
                    number = parseFloat(match.replace(',', '.'));
                } catch (e) {
                }
                if (i == 1) { // years
                    totDays = totDays + number * (considerWorkingdays ? workingDaysPerWeek * 52 : 365);
                } else {
                    if (i == 2) { // quarter
                        totDays = totDays + number * (considerWorkingdays ? workingDaysPerWeek * 12 : 91);
                    } else {
                        if (i == 3) { // months
                            totDays = totDays + number * (considerWorkingdays ? workingDaysPerWeek * 4 : 30);
                        } else {
                            if (i == 4) { // weeks
                                totDays = totDays + number * (considerWorkingdays ? workingDaysPerWeek : 7);
                            } else {
                                if (i == 5) { // days
                                    totDays = totDays + number;
                                } else {
                                    if (i == 6) { // days.minutes
                                        totDays = totDays + number;
                                    } else {
                                        if (i == 7) { // days
                                            totDays = totDays + number;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        matcher = regex.exec(string);
    }

    return parseInt(totDays);
}

/*
 Copyright (c) 2012-2017 Open Lab
 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

function centerPopup(url, target, w, h, scroll, resiz)
{
    var winl = (screen.width - w) / 2;
    var wint = (screen.height - h) / 2;
    var winprops = 'height=' + h + ',width=' + w + ',top=' + wint + ',left=' + winl + ',scrollbars=' + scroll + ',resizable=' + resiz + ', toolbars=false, status=false, menubar=false';
    var win = window.open(url, target, winprops);
    if (!win) {
        alert(
            "A popup blocker was detected: please allow them for this application (check out the upper part of the browser window).");
    }
    if (parseInt(navigator.appVersion) >= 4) {
        win.window.focus();
    }
}

function openCenteredWindow(url, target, winprops)
{
    var prop_array = winprops.split(",");
    var i = 0;
    var w = 800;
    var h = 600;
    if (winprops && winprops != '') {
        while (i < prop_array.length) {
            if (prop_array[i].indexOf('width') > -1) {
                s = prop_array[i].substring(prop_array[i].indexOf('=') + 1);
                w = parseInt(s);
            } else {
                if (prop_array[i].indexOf('height') > -1) {
                    s = prop_array[i].substring(prop_array[i].indexOf('=') + 1);
                    h = parseInt(s);
                }
            }
            i += 1;
        }
        var winl = (screen.width - w) / 2;
        var wint = (screen.height - h) / 2;
        winprops = winprops + ",top=" + wint + ",left=" + winl;
    }
    win = window.open(url, target, winprops);
    if (!win) {
        alert(
            "A popup blocker was detected: please allow them for this application (check out the upper part of the browser window).");
    }
    if (parseInt(navigator.appVersion) >= 4) {
        win.window.focus();
    }
}

function showFeedbackMessage(typeOrObject, message, title, autoCloseTime)
{

    if (!autoCloseTime) {
        autoCloseTime = 0;
    }

    //console.debug("showFeedbackMessage",typeOrObject, message, title);
    var place = $("#__FEEDBACKMESSAGEPLACE");
    var mess;
    if (typeof (typeOrObject) == "object") {
        mess = typeOrObject;
    } else {
        mess = {type: typeOrObject, message: message, title: title};
    }
    //if exists append error message
    var etm = $(".FFC_" + mess.type + ":visible ._errorTemplateMessage");
    if (etm.length > 0) {
        etm.append("<hr>" + (mess.title ? "<b>" + mess.title + "</b><br>" : "") + mess.message + "<br>");
    } else {
        etm = $.JST.createFromTemplate(mess, "errorTemplate");
        place.append(etm);
        place.fadeIn();
    }

    if (autoCloseTime > 0) {
        setTimeout(function () {
            etm.fadeOut();
        }, autoCloseTime);
    }

    $(".FFC_OK").stopTime("ffchide").oneTime(1500, "ffchide", function () {
        $(this).fadeOut(400, function () {
            $(this)
        })
    });
    $(".FFC_WARNING").stopTime("ffchide").oneTime(75000, "ffchide", function () {
        $(this).fadeOut(400, function () {
            $(this)
        })
    });
    $(".FFC_ERROR").stopTime("ffchide").oneTime(10000, "ffchide", function () {
        $(this).fadeOut(400, function () {
            $(this)
        })
    });
}

function showFeedbackMessageInDiv(type, message, divId)
{
    var place = $("#" + divId);
    var mess = {type: type, message: message};
    place.prepend($.JST.createFromTemplate(mess, "errorTemplate"));
    place.fadeIn();
    $("body").oneTime(1200, function () {
        $(".FFC_OK").fadeOut();
    });
}

function hideFeedbackMessages()
{
    $("#__FEEDBACKMESSAGEPLACE").empty();
}


function submitInBlack(formId, actionHref, w, h)
{

    if (!w) {
        w = $(window).width() - 100;
    }
    if (!h) {
        h = $(window).height() - 50;
    }

    openBlackPopup('', w + "px", h + "px", null, formId + "_ifr");
    var form = $("#" + formId);
    var oldAction = form.prop("action");
    var oldTarget = form.prop("target");
    form.prop("action", actionHref);
    form.prop("target", formId + "_ifr");
    $(window).data("openerForm", form);
    form.submit();
    form.prop("action", oldAction);
    if (oldTarget) {
        form.prop("target", oldTarget);
    } else {
        form.removeAttr("target");
    }
}


var __popups = [];

function createModalPopup(width, height, onCloseCallBack, cssClass, element, popupOpener)
{
    //console.debug("createModalPopup");


    if (typeof (disableUploadize) == "function") {
        disableUploadize();
    }

    // se non diversamenete specificato l'openere è la window corrente;
    popupOpener = popupOpener || window;

    if (!width) {
        width = "80%";
    }

    if (!height) {
        height = "80%";
    }

    var localWidth = width, localHeight = height;

    if (typeof (width) == "string" && width.indexOf("%") > 0) {
        localWidth = function () {
            return ($(window).width() * parseFloat(width)) / 100
        };
    }

    if (typeof (height) == "string" && height.indexOf("%") > 0) {
        localHeight = function () {
            return ($(window).height() * parseFloat(height)) / 100
        };
    }

    var popupWidth = localWidth, popupHeight = localHeight;

    if (typeof localWidth == "function") {
        popupWidth = localWidth();
    }

    if (typeof localHeight == "function") {
        popupHeight = localHeight();
    }

    popupWidth = parseFloat(popupWidth);
    popupHeight = parseFloat(popupHeight);

    if (typeof onCloseCallBack == "string") {
        cssClass = onCloseCallBack;
    }

    //$("#__popup__").remove();

    var popupN = __popups.length + 1;
    __popups.push("__popup__" + popupN);

    var isInIframe = isIframe();

    var bg = $("<div>").prop("id", "__popup__" + popupN);
    bg.addClass("modalPopup" + (isInIframe ? " inIframe" : "")).hide();

    if (cssClass) {
        bg.addClass(cssClass);
    }

    function getMarginTop()
    {
        var mt = ($(window).height() - popupHeight) / 2 - 100;
        return mt < 0 ? 10 : mt;
    }

    var internalDiv = $("<div>").addClass("bwinPopupd").css({
        width: popupWidth,
        minHeight: popupHeight,
        marginTop: getMarginTop(),
        maxHeight: $(window).height() - 20,
        overflow: "auto"
    });

    $(window).off("resize.popup" + popupN).on("resize.popup" + popupN, function () {

        if (typeof localWidth == "function") {
            popupWidth = localWidth();
        }

        if (typeof localHeight == "function") {
            popupHeight = localHeight();
        }

        internalDiv.css({width: popupWidth, minHeight: popupHeight});

        var w = internalDiv.outerWidth() > $(window).width() - 20 ? $(window).width() - 20 : popupWidth;
        var h = internalDiv.outerHeight() > $(window).height() - 20 ? $(window).height() - 20 : popupHeight;

        internalDiv.css({marginTop: getMarginTop(), minHeight: h, maxHeight: $(window).height() - 20, minWidth: w});

    });

    bg.append(internalDiv);

    var showBG = function (el, time, callback) {

        if (isInIframe) {
            internalDiv.css({marginTop: -50});
            el.show();
            internalDiv.animate({marginTop: 0}, (time / 2), callback);
        } else {
            internalDiv.css({opacity: 0, top: -50}).show();
            el.fadeIn(time, function () {
                internalDiv.animate({top: 0, opacity: 1}, time / 3, callback);
            });
        }

        /*
                if(isInIframe) {
                    internalDiv.css({marginTop: -1000 });
                    el.show();
                    internalDiv.animate({marginTop: 0}, (time * 2), callback);
                }else{
                    internalDiv.css({opacity:0, top: -500}).show();
                    el.fadeIn(time, function(){
                        internalDiv.animate({top: 0, opacity:1}, time, callback);
                    });
                }
        */

        return this;
    };

    if (!element) {
        $("#twMainContainer").addClass("blur");
    }

    showBG(bg, 300, function () {
    })
    bg.on("click", function (event) {
        if ($(event.target).closest(".bwinPopupd").length <= 0) {
            bg.trigger("close");
        }
    });

    var close = $("<span class=\"teamworkIcon close popUpClose\" style='cursor:pointer;position:absolute;'>x</span>");
    internalDiv.append(close);

    close.click(function () {
        bg.trigger("close");
    });

    $("body").css({overflowY: "hidden"});

    if (!element) {
        $("body").append(bg);
    } else {
        element.after(bg);
    }

    //close call callback
    bg.on("close", function () {
        var callBackdata = $(this).data("callBackdata");
        var ndo = bg;

        if (typeof (enableUploadize) == "function") {
            enableUploadize();
        }

        //console.debug("ndo",ndo);

        var alertMsg;
        var ifr = bg.find("iframe");

        if (ifr.length > 0) {
            try {
                alertMsg = ifr.get(0).contentWindow.alertOnUnload();
            } catch (e) {
            }
        } else {
            alertMsg = alertOnUnload(ndo);
        }

        if (alertMsg) {
            if (!confirm(alertMsg)) {
                return;
            }
        }

        bg.fadeOut(100, function () {

            $(window).off("resize.popup" + popupN);
            bg.remove();
            __popups.pop();

            if (__popups.length == 0) {
                $("#twMainContainer").removeClass("blur");
            }

            if (typeof (onCloseCallBack) == "function") {
                onCloseCallBack(callBackdata);
            }

            $("body").css({overflowY: "auto"});
        });

    });

    //destroy do not call callback
    bg.on("destroy", function () {
        bg.remove();
        $("body").css({overflowY: "auto"});
    });

    //rise resize event in order to show buttons
    $("body").oneTime(1000, "br", function () {
        $(this).resize();
    }); // con meno di 1000 non funziona


    //si deposita l'popupOpener sul bg. Per riprenderlo si usa getBlackPopupOpener()
    bg.data("__opener", popupOpener);

    return internalDiv;
}

function changeModalSize(w, h)
{
    var newDim = {};
    if (w) {
        newDim.width = w;
    }
    if (h) {
        newDim.minHeight = h;
    }

    var isInIframe = isIframe();
    var popUp = isInIframe ? window.parent.$(".bwinPopupd") : $(".bwinPopupd");

    if (popUp.length) {
        popUp.delay(300).animate(newDim, 200);
    }
}

function openBlackPopup(url, width, height, onCloseCallBack, iframeId, cssClass)
{

    if (!iframeId) {
        iframeId = "bwinPopupIframe";
    }

    //add black only if not already in blackpupup
    var color = cssClass ? cssClass + " iframe" : "iframe";

    var ndo = top.createModalPopup(width, height, onCloseCallBack, color, null, window);

    //ndo.closest(".modalPopup ").data("__opener",window);  // si deposita il vero opener

    var isInIframe = isIframe();

    ndo.append(
        "<div class='bwinPopupIframe_wrapper'><iframe id='" + iframeId + "' name='" + iframeId + "' frameborder='0'></iframe></div>");
    ndo.find("iframe:first").prop("src", url).css(
        {width: "100%", height: "100%", backgroundColor: isInIframe ? '#F9F9F9' : '#FFFFFF'});
}

function getBlackPopup()
{
    var ret = $([]);
    if (__popups.length > 0) {
        var id = __popups[__popups.length - 1];
        ret = $("#" + id);
    }
    if (ret.length == 0 && window != top) {
        ret = window.parent.getBlackPopup();
    }
    return ret;
}


function getBlackPopupOpener()
{
    return getBlackPopup().data("__opener")
}

function closeBlackPopup(callBackdata)
{
    //console.debug("closeBlackPopup ",callBackdata);
    var bp = getBlackPopup();

    if (callBackdata) {
        bp.data("callBackdata", callBackdata);
    }
    bp.trigger("close");
}

function openPopUp(el, width, height)
{
    var popup = createModalPopup(width, height);
    popup.append(el.clone().show());
}

//returns a jquery object where to write content

function isIframe()
{
    var isIframe = false;
    try {
        //try to access the document object
        if (self.location.href != top.location.href) {
            isIframe = true;
        }
    } catch (e) {
        //We don't have access, it's cross-origin!
        isIframe = true;
    }
    return isIframe;
};


function openBulkAction(bulkDivId)
{
    var popup = createModalPopup(500, 300);
    popup.append($("#" + bulkDivId).clone().show());
}


function refreshBulk(el)
{
    //console.debug("refreshBulk")

    if (el.is(":checked")) {
        el.closest("tr").addClass("selected");
    } else {
        el.closest("tr").removeClass("selected");
    }

    var table = el.closest(".dataTable");
    if (table.find(".selected :checked").length > 0) {

        $("#bulkOp #bulkRowSel").html(
            table.find("tbody > tr.selected").length + "/" + table.children("tbody").children("tr").length);

        var bukOpt = $("#bulkOp").clone().addClass("bulkOpClone");
        bukOpt.fadeIn(200, function () {
            $("#bulkPlace").html(bukOpt);
            $.tableHF.refreshTfoot();
        });

    } else {
        $(".bulkOpClone").fadeOut(200, function () {
            $.tableHF.refreshTfoot();
        });
    }
}

function selUnselAll(el)
{
    //var bulkCheckbox = $("#multi td [type='checkbox']");
    var bulkCheckbox = el.closest(".dataTable").find("[type='checkbox']");
    if (el.is(":checked")) {
        bulkCheckbox.prop("checked", true);
        bulkCheckbox.closest("tr").addClass("selected");
    } else {
        bulkCheckbox.prop("checked", false);
        bulkCheckbox.closest("tr").removeClass("selected");
    }

    refreshBulk(el);
}

/*
 Copyright (c) 2012-2017 Open Lab
 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

//----------------------------------positioning-----------------------------------------------
jQuery.fn.centerOnScreen = function () {
    return this.each(function () {
        var container = $(this);
        //console.debug($(window).height(), container.outerHeight(),(($(window).height() - container.outerHeight()) / 2))
        container.css("position", "fixed");
        container.css("top", (($(window).height() - container.outerHeight()) / 2) + 'px');
        container.css("left", (($(window).width() - container.outerWidth()) / 2) + 'px');
    });
};


function nearBestPosition(whereId, theObjId, centerOnEl)
{

    var el = whereId;
    var target = theObjId;

    if (typeof whereId != "object") {
        el = $("#" + whereId);
    }
    if (typeof theObjId != "object") {
        target = $("#" + theObjId);
    }

    if (el) {
        target.css("visibility", "hidden");
        var hasContainment = false;

        target.parents().each(function () {
            if ($(this).css("position") == "static") {
                return;
            }

            hasContainment = true;
        });

        var trueX = hasContainment ? el.position().left : el.offset().left;
        var trueY = hasContainment ? el.position().top : el.offset().top;
        var h = el.outerHeight();
        var elHeight = parseFloat(h);

        if (centerOnEl) {
            var elWidth = parseFloat(el.outerWidth());
            var targetWidth = parseFloat(target.outerWidth());
            trueX += (elWidth - targetWidth) / 2;
        }

        trueY += parseFloat(elHeight);

        var left = trueX;
        var top = trueY;
        var barHeight = 45;
        var barWidth = 20;

        if (trueX && trueY) {
            target.css("left", left);
            target.css("top", top);
        }

        if (target.offset().left >= (($(window).width() + $(window).scrollLeft()) - target.outerWidth())) {

            left = (($(window).width() + $(window).scrollLeft()) - target.outerWidth() - 10);
            target.css({left: left, marginTop: 0});
        }

        if (target.offset().left < 0) {
            left = 10;
            target.css("left", left);
        }

        if ((target.offset().top + target.outerHeight() >= (($(window).height() + $(
            window).scrollTop()) - barHeight)) && (target.outerHeight() < $(window).height())) {
            var marginTop = -(target.outerHeight() + el.outerHeight());
            target.css("margin-top", marginTop);
        }

        if (target.offset().top < 0) {
            top = 0;
            target.css("top", top);
        }


        target.css("visibility", "visible");
    }
}

$.fn.keepItVisible = function (ref) {
    var thisTop = $(this).offset().top;
    var thisLeft = $(this).offset().left;
    var fromTop = 0;
    var fromLeft = 0;

    var windowH = $(window).height() + $(window).scrollTop();
    var windowW = $(window).width() + $(window).scrollLeft();

    if (ref) {
        fromTop = windowH - (ref.offset().top);
        fromLeft = windowW - (ref.offset().left + ref.outerWidth());
    }

    if (thisTop + $(this).outerHeight() > windowH) {
        var mt = (thisTop + $(this).outerHeight()) - windowH;
//		$(this).css("margin-top", -$(this).outerHeight() - fromTop);
        $(this).css("margin-top", -mt - fromTop);
    }
    if (thisLeft + $(this).outerWidth() > windowW) {
        var mL = (thisLeft + $(this).outerWidth()) - windowW;
//		$(this).css("margin-left", -$(this).outerWidth() - fromLeft);
        $(this).css("margin-left", -mL - fromLeft);
    }
    $(this).css("visibility", "visible");
};

//END positioning


/*   Caret Functions
 Use setSelection with start = end to set caret
 */
function setSelection(input, start, end)
{
    input.setSelectionRange(start, end);
}

$.fn.setCursorPosition = function (pos) {
    this.each(function (index, elem) {
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
        } else {
            if (elem.createTextRange) {
                var range = elem.createTextRange();
                range.collapse(true);
                range.moveEnd('character', pos);
                range.moveStart('character', pos);
                range.select();
            }
        }
    });
    return this;
};

//-- Caret Functions END ---------------------------------------------------------------------------- --


/*----------------------------------------------------------------- manage bbButtons*/
$.buttonBar = {
    defaults: {},

    init: function () {
        setTimeout(function () {
            $.buttonBar.manageButtonBar();
        }, 100);

        $(window).on("scroll.ButtonBar", function () {
            $.buttonBar.manageButtonBar();
        });
        $(window).on("resize.ButtonBar", function () {
            $.buttonBar.manageButtonBar();
        });
    },

    manageButtonBar: function (anim) {

        $(".buttonArea").not(".bbCloned").not(".notFix").each(function () {
            var bb = this;

            //se usiamo questi si rompe la button bar flottante del save sulla issue list
            //bb.originalHeigh=bb.originalHeigh ||  $(bb).height();
            //bb.originalOffsetTop=bb.originalOffsetTop||$(bb).offset().top;

            bb.originalHeigh = $(bb).height();
            bb.originalOffsetTop = $(bb).offset().top;

            bb.isOut = $(window).scrollTop() + $(window).height() - bb.originalHeigh < bb.originalOffsetTop;

            if (bb.bbHolder) {
                bb.bbHolder.css({width: $(bb).outerWidth(), left: $(bb).offset().left});
            }

            if (bb.isOut && !bb.isCloned) {
                if (bb.bbHolder) {
                    bb.bbHolder.remove();
                }
                bb.isCloned = true;
                bb.bbHolder = $(bb).clone().addClass("bbCloned clone bottom").css(
                    {width: $(bb).outerWidth(), marginTop: 0, left: $(bb).offset().left});
                bb.bbHolder.hide();
                bb.bbHolder.css({position: "fixed", bottom: 0, left: $(bb).offset().left});
                $(bb).after(bb.bbHolder);
                bb.bbHolder.show();
                $(bb).css("visibility", "hidden");

            } else {
                if (!bb.isOut && bb.isCloned) {
                    //} else {
                    bb.isCloned = false;
                    bb.bbHolder.remove();
                    $(bb).css("visibility", "visible");
                }
            }
        });
    },

    refreshButtonBar: function () {
        $(".bbCloned").remove();
        $(".buttonArea").not(".bbCloned").each(function () {
            var bb = this;
            bb.isCloned = false;
        });

        $.buttonBar.manageButtonBar(false);
    }
};

/*
 Copyright (c) 2012-2017 Open Lab
 Permission is hereby granted, free of charge, to any person obtaining
 a copy of this software and associated documentation files (the
 "Software"), to deal in the Software without restriction, including
 without limitation the rights to use, copy, modify, merge, publish,
 distribute, sublicense, and/or sell copies of the Software, and to
 permit persons to whom the Software is furnished to do so, subject to
 the following conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


function dateToRelative(localTime)
{
    var diff = new Date().getTime() - localTime;
    var ret = "";

    var min = 60000;
    var hour = 3600000;
    var day = 86400000;
    var wee = 604800000;
    var mon = 2629800000;
    var yea = 31557600000;

    if (diff < -yea * 2) {
        ret = "in ## years".replace("##", (-diff / yea).toFixed(0));
    } else {
        if (diff < -mon * 9) {
            ret = "in ## months".replace("##", (-diff / mon).toFixed(0));
        } else {
            if (diff < -wee * 5) {
                ret = "in ## weeks".replace("##", (-diff / wee).toFixed(0));
            } else {
                if (diff < -day * 2) {
                    ret = "in ## days".replace("##", (-diff / day).toFixed(0));
                } else {
                    if (diff < -hour) {
                        ret = "in ## hours".replace("##", (-diff / hour).toFixed(0));
                    } else {
                        if (diff < -min * 35) {
                            ret = "in about one hour";
                        } else {
                            if (diff < -min * 25) {
                                ret = "in about half hour";
                            } else {
                                if (diff < -min * 10) {
                                    ret = "in some minutes";
                                } else {
                                    if (diff < -min * 2) {
                                        ret = "in few minutes";
                                    } else {
                                        if (diff <= min) {
                                            ret = "just now";
                                        } else {
                                            if (diff <= min * 5) {
                                                ret = "few minutes ago";
                                            } else {
                                                if (diff <= min * 15) {
                                                    ret = "some minutes ago";
                                                } else {
                                                    if (diff <= min * 35) {
                                                        ret = "about half hour ago";
                                                    } else {
                                                        if (diff <= min * 75) {
                                                            ret = "about an hour ago";
                                                        } else {
                                                            if (diff <= hour * 5) {
                                                                ret = "few hours ago";
                                                            } else {
                                                                if (diff <= hour * 24) {
                                                                    ret = "## hours ago".replace("##",
                                                                        (diff / hour).toFixed(0));
                                                                } else {
                                                                    if (diff <= day * 7) {
                                                                        ret = "## days ago".replace("##",
                                                                            (diff / day).toFixed(0));
                                                                    } else {
                                                                        if (diff <= wee * 5) {
                                                                            ret = "## weeks ago".replace("##",
                                                                                (diff / wee).toFixed(0));
                                                                        } else {
                                                                            if (diff <= mon * 12) {
                                                                                ret = "## months ago".replace("##",
                                                                                    (diff / mon).toFixed(0));
                                                                            } else {
                                                                                ret = "## years ago".replace("##",
                                                                                    (diff / yea).toFixed(0));
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return ret;
}

//override date format i18n

Date.monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];
// Month abbreviations. Change this for local month names
Date.monthAbbreviations = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
// Full day names. Change this for local month names
Date.dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
// Day abbreviations. Change this for local month names
Date.dayAbbreviations = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
// Used for parsing ambiguous dates like 1/2/2000 - default to preferring 'American' format meaning Jan 2.
// Set to false to prefer 'European' format meaning Feb 1
Date.preferAmericanFormat = false;

Date.firstDayOfWeek = 0;
Date.defaultFormat = "M/d/yyyy";
Date.masks = {
    fullDate: "EEEE, MMMM d, yyyy",
    shortTime: "h:mm a"
};
Date.today = "Today";

Number.decimalSeparator = ".";
Number.groupingSeparator = ",";
Number.minusSign = "-";
Number.currencyFormat = "###,##0.00";


var millisInWorkingDay = 28800000;
var workingDaysPerWeek = 5;

function isHoliday(date)
{
    var friIsHoly = false;
    var satIsHoly = true;
    var sunIsHoly = true;

    var pad = function (val) {
        val = "0" + val;
        return val.substr(val.length - 2);
    };

    var holidays = "##";

    var ymd = "#" + date.getFullYear() + "_" + pad(date.getMonth() + 1) + "_" + pad(date.getDate()) + "#";
    var md = "#" + pad(date.getMonth() + 1) + "_" + pad(date.getDate()) + "#";
    var day = date.getDay();

    return (day == 5 && friIsHoly) || (day == 6 && satIsHoly) || (day == 0 && sunIsHoly) || holidays.indexOf(
        ymd) > -1 || holidays.indexOf(md) > -1;
}


var i18n = {
    YES: "Yes",
    NO: "No",
    FLD_CONFIRM_DELETE: "confirm the deletion?",
    INVALID_DATA: "The data inserted are invalid for the field format.",
    ERROR_ON_FIELD: "Error on field",
    OUT_OF_BOUDARIES: "Out of field admitted values:",
    CLOSE_ALL_CONTAINERS: "close all?",
    DO_YOU_CONFIRM: "Do you confirm?",
    ERR_FIELD_MAX_SIZE_EXCEEDED: "Field max size exceeded",
    WEEK_SHORT: "W.",

    FILE_TYPE_NOT_ALLOWED: "File type not allowed.",
    FILE_UPLOAD_COMPLETED: "File upload completed.",
    UPLOAD_MAX_SIZE_EXCEEDED: "Max file size exceeded",
    ERROR_UPLOADING: "Error uploading",
    UPLOAD_ABORTED: "Upload aborted",
    DROP_HERE: "Drop files here",

    FORM_IS_CHANGED: "You have some unsaved data on the page!",

    PIN_THIS_MENU: "PIN_THIS_MENU",
    UNPIN_THIS_MENU: "UNPIN_THIS_MENU",
    OPEN_THIS_MENU: "OPEN_THIS_MENU",
    CLOSE_THIS_MENU: "CLOSE_THIS_MENU",
    PROCEED: "Proceed?",

    PREV: "Previous",
    NEXT: "Next",
    HINT_SKIP: "Got it, close this hint.",

    WANT_TO_SAVE_FILTER: "save this filter",
    NEW_FILTER_NAME: "name of the new filter",
    SAVE: "Save",
    DELETE: "Delete",

    COMBO_NO_VALUES: "no values available...?",

    FILTER_UPDATED: "Filter updated.",
    FILTER_SAVED: "Filter correctly saved."
};

/*
  Copyright (c) 2009 Open Lab
  Written by Roberto Bicchierai http://roberto.open-lab.com
  Permission is hereby granted, free of charge, to any person obtaining
  a copy of this software and associated documentation files (the
  "Software"), to deal in the Software without restriction, including
  without limitation the rights to use, copy, modify, merge, publish,
  distribute, sublicense, and/or sell copies of the Software, and to
  permit persons to whom the Software is furnished to do so, subject to
  the following conditions:

  The above copyright notice and this permission notice shall be
  included in all copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
  EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
  MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
  NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
  WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

jQuery.fn.dateField = function(options) {
    //console.debug("dateField",options);
    //check if the input field is passed correctly
    if (!options.inputField){
        console.error("You must supply an input field");
        return false;
    }

    // --------------------------  start default option values --------------------------

    if (typeof(options.firstDayOfWeek) == "undefined")
        options.firstDayOfWeek=Date.firstDayOfWeek;

    if (typeof(options.useWheel) == "undefined")
        options.useWheel=true;

    if (typeof(options.dateFormat) == "undefined")
        options.dateFormat=Date.defaultFormat;

    if (typeof(options.todayLabel) == "undefined")
        options.todayLabel=Date.today;

    /* optional
      options.notBeforeMillis //disable buttons if before millis
      options.notAfterMillis //disable buttons if after millis
      options.width // imposta una larghezza al calendario
      options.height
      options.showToday // show "today" on the year or month bar
      options.centerOnScreen //se true centra invece che usa nearBestPosition
      options.useYears:0 // se >0 non disegna prev-next ma n anni prima e n anni dopo quello corrente
      options.useMonths:0 // se >0 non disegna prev-next ma n mesi prima e n mesi dopo quello corrente
    */
    // --------------------------  end default option values --------------------------



    // ------------------ start
    if(options.inputField.is("[readonly]") && !options.inputField.is(".noFocus")  || options.inputField.is("[disabled]"))
        return;

    var calendar = {currentDate: new Date()};
    calendar.options = options;

    //build the calendar on the first element in the set of matched elements.
    var theOpener = this.eq(0);
    var theDiv=$("<div>").addClass("calBox");

    if(options.width)
        theDiv.css("width",options.width);

    if(options.height)
        theDiv.css("height",options.height);


    //create calendar elements elements
    var divNavBar = $("<div>").addClass("calNav");
    var divDays = $("<div>").addClass("calDay");

    divDays.addClass("calFullMonth");
    theDiv.append(divNavBar).append(divDays);

    if (options.isSearchField){
        var divShortcuts=$("<div>").addClass("shortCuts").html("<span title='last quarter'>LQ</span> <span title='last month'>LM</span> <span title='this month'>M</span> <span title='last week'>LW</span> <span title='this week'>W</span> <span title='yesterday'>Y</span> <span title='today'>T</span><span title='tomorrow'>TO</span><span title='next week'>NW</span> <span title='next month'>NM</span> <span title='this quarter'>Q</span> <span title='next quarter'>NQ</span>");
        divShortcuts.click(function(ev){
            var el=$(ev.target);
            if(el.is("span")){
                if (!options.isSearchField)
                    options.inputField.val(Date.parseString(el.text().trim(),options.dateFormat,true).format(options.dateFormat));
                else
                    options.inputField.val(el.text().trim());
                calendar.closeCalendar()
            }
        });
        theDiv.append(divShortcuts);
    }

    //mobile management
    if ($("body").is(".mobile")){
        enableComponentOverlay(options.inputField,theDiv);
    }
    $("body").append(theDiv);


    if (options.centerOnScreen){
        theDiv.oneTime(10,"ce",function(){$(this).centerOnScreen()});
    } else {
        nearBestPosition(theOpener,theDiv);
    }
    theDiv.css("z-index",10000);


    //register for click outside. Delayed to avoid it run immediately
    $("body").oneTime(100, "regclibodcal", function() {
        $("body").bind("click.dateField", function() {
            calendar.closeCalendar();
        });
    });


    calendar.drawCalendar = function(date) {
        calendar.currentDate = date;
        //console.debug("drawCalendar",date);


        var fillNavBar = function(date) {
            //console.debug("fillNavBar",date);
            var today = new Date();//today
            divNavBar.empty();

            var showToday = options.showToday;
            //use the classic prev next bar
            if (!options.useYears && !options.useMonths) {
                var t = new Date(date.getTime());
                t.setDate(1);
                t.setMonth(t.getMonth() - 1);
                var spanPrev = $("<span>").addClass("calElement noCallback prev").attr("millis", t.getTime());
                var spanToday = $("<span>").addClass("calElement noCallback goToday").attr("millis", new Date().getTime()).attr("title", "today");
                t.setMonth(t.getMonth() + 1);
                var spanMonth = $("<span>").html(t.format("MMMM yyyy"));
                t.setMonth(t.getMonth() + 1);
                var spanNext = $("<span>").addClass("calElement noCallback next").attr("millis", t.getTime());
                divNavBar.append(spanPrev, spanToday, spanMonth, spanNext);

                // use the year month bar
            } else {
                if (options.useYears>0){
                    options.useMonths=options.useMonths||1; //if shows years -> shows also months
                    t = new Date(date.getTime());
                    var yB= $("<div class='calYear'>");
                    var w=100/(2*options.useYears+1+(showToday?1:0));
                    t.setFullYear(t.getFullYear()-options.useYears);
                    if(showToday){
                        var s = $("<span>").addClass("calElement noCallback goToday").attr("millis", today.getTime()).append(options.todayLabel).css("width",w+"%");
                        showToday=false;
                        yB.append(s);
                    }
                    for (var i=-options.useYears;i<=options.useYears;i++){
                        var s = $("<span>").addClass("calElement noCallback").attr("millis", t.getTime()).append(t.getFullYear()).css("width",w+"%");
                        if (today.getFullYear()== t.getFullYear()) //current year
                            s.addClass("today");
                        if (i==0) //selected year
                            s.addClass("selected");

                        yB.append(s);
                        t.setFullYear(t.getFullYear()+1);
                    }
                    divNavBar.append(yB);
                }
                if (options.useMonths>0){
                    t = new Date(date.getTime());
                    t.setDate(1);
                    var w=100/(2*options.useMonths+1+(showToday?1:0));
                    t.setMonth(t.getMonth()-options.useMonths);
                    var yB= $("<div class='calMonth'>");

                    if(showToday){
                        var s = $("<span>").addClass("calElement noCallback goToday").attr("millis", today.getTime()).append(options.todayLabel).css("width",w+"%");
                        yB.append(s);
                    }

                    for (var i=-options.useMonths;i<=options.useMonths;i++){
                        var s = $("<span>").addClass("calElement noCallback").attr("millis", t.getTime()).append(t.format("MMM")).css("width",w+"%");
                        if (today.getFullYear()== t.getFullYear() && today.getMonth()== t.getMonth()) //current year
                            s.addClass("today");
                        if (i==0) //selected month
                            s.addClass("selected");
                        yB.append(s);
                        t.setMonth(t.getMonth()+1);
                    }
                    divNavBar.append(yB);
                }
            }

        };

        var fillDaysFullMonth = function(date) {
            divDays.empty();
            var today = new Date();//today
            var w = 100/7;
            // draw day headers
            var d = new Date(date);
            d.setFirstDayOfThisWeek(options.firstDayOfWeek);
            for (var i = 0; i < 7; i++) {
                var span = $("<span>").addClass("calDayHeader").attr("day", d.getDay());
                if (d.isHoliday())
                    span.addClass("holy");
                span.css("width",w+"%");
                span.html(Date.dayAbbreviations[d.getDay()]);

                //call the dayHeaderRenderer
                if (typeof(options.dayHeaderRenderer) == "function")
                    options.dayHeaderRenderer(span,d.getDay());

                divDays.append(span);
                d.setDate(d.getDate()+1);
            }

            //draw cells
            d = new Date(date);
            d.setDate(1); // set day to start of month
            d.setFirstDayOfThisWeek(options.firstDayOfWeek);//go to first day of week

            var i=0;

            while ((d.getMonth()<=date.getMonth() && d.getFullYear()<=date.getFullYear()) || d.getFullYear()<date.getFullYear() || (i%7!=0)) {
                var span = $("<span>").addClass("calElement day").attr("millis", d.getTime());

                span.html("<span class=dayNumber>" + d.getDate() + "</span>").css("width",w+"%");
                if (d.getYear() == today.getYear() && d.getMonth() == today.getMonth() && d.getDate() == today.getDate())
                    span.addClass("today");
                if (d.getYear() == date.getYear() && d.getMonth() == date.getMonth() && d.getDate() == date.getDate())
                    span.addClass("selected");

                if (d.isHoliday())
                    span.addClass("holy");

                if(d.getMonth()!=date.getMonth())
                    span.addClass("calOutOfScope");

                //call the dayRenderer
                if (typeof(options.dayRenderer) == "function")
                    options.dayRenderer(span,d);

                divDays.append(span);
                d.setDate(d.getDate()+1);
                i++;
            }

        };

        fillNavBar(date);
        fillDaysFullMonth(date);

        //disable all buttons out of validity period
        if (options.notBeforeMillis ||options.notAfterMillis) {
            var notBefore = options.notBeforeMillis ? options.notBeforeMillis : Number.MIN_VALUE;
            var notAfter = options.notAfterMillis ? options.notAfterMillis : Number.MAX_VALUE;
            divDays.find(".calElement[millis]").each(function(){
                var el=$(this);
                var m=parseInt(el.attr("millis"));
                if (m>notAfter || m<notBefore)
                    el.addClass("disabled");
            })
        }

    };

    calendar.closeCalendar=function(){
        //mobile management
        if ($("body").is(".mobile")){
            disableComponentOverlay();
        }
        theDiv.remove();
        $("body").unbind("click.dateField");
    };

    theDiv.click(function(ev) {
        var el = $(ev.target).closest(".calElement");
        if (el.length > 0) {
            var millis = parseInt(el.attr("millis"));
            var date = new Date(millis);

            if (el.is(".disabled")) {
                ev.stopPropagation();
                return;
            }

            if (el.hasClass("day")) {
                calendar.closeCalendar();
                if (!el.is(".noCallback")) {
                    options.inputField.val(date.format(options.dateFormat)).attr("millis", date.getTime()).focus();
                    if (typeof(options.callback) == "function")
                        options.callback.apply(options.inputField,[date]); // in callBack you can use "this" that refers to the input
                }
            } else {
                calendar.drawCalendar(date);
            }
        }
        ev.stopPropagation();
    });


    //if mousewheel
    if ($.event.special.mousewheel && options.useWheel) {
        divDays.mousewheel(function(event, delta) {
            var d = new Date(calendar.currentDate.getTime());
            d.setMonth(d.getMonth() + delta);
            calendar.drawCalendar(d);
            return false;
        });
    }


    // start calendar to the date in the input
    var dateStr=options.inputField.val();

    if (!dateStr || !Date.isValid(dateStr,options.dateFormat,true)){
        calendar.drawCalendar(new Date());
    } else {
        var date = Date.parseString(dateStr,options.dateFormat,true);
        var newDateStr=date.format(options.dateFormat);
        //set date string formatted if not equals
        if (!options.isSearchField) {
            options.inputField.attr("millis", date.getTime());
            if (dateStr != newDateStr)
                options.inputField.val(newDateStr);
        }
        calendar.drawCalendar(date);
    }

    return calendar;
};

$.fn.loadTemplates = function() {
    $.JST.loadTemplates($(this));
    return this;
};

$.JST = {
    _templates: new Object(),
    _decorators:new Object(),

    loadTemplates: function(elems) {
        elems.each(function() {
            $(this).find(".__template__").each(function() {
                var tmpl = $(this);
                var type = tmpl.attr("type");

                //template may be inside <!-- ... --> or not in case of ajax loaded templates
                var found=false;
                var el=tmpl.get(0).firstChild;
                while (el && !found) {
                    if (el.nodeType == 8) { // 8==comment
                        var templateBody = el.nodeValue; // this is inside the comment
                        found=true;
                        break;
                    }
                    el=el.nextSibling;
                }
                if (!found)
                    var templateBody = tmpl.html(); // this is the whole template

                if (!templateBody.match(/##\w+##/)) { // is Resig' style? e.g. (#=id#) or (# ...some javascript code 'obj' is the alias for the object #)
                    var strFunc =
                        "var p=[],print=function(){p.push.apply(p,arguments);};" +
                        "with(obj){p.push('" +
                        templateBody.replace(/[\r\t\n]/g, " ")
                            .replace(/'(?=[^#]*#\))/g, "\t")
                            .split("'").join("\\'")
                            .split("\t").join("'")
                            .replace(/\(#=(.+?)#\)/g, "',$1,'")
                            .split("(#").join("');")
                            .split("#)").join("p.push('")
                        + "');}return p.join('');";

                    try {
                        $.JST._templates[type] = new Function("obj", strFunc);
                    } catch (e) {
                        console.error("JST error: "+type, e,strFunc);
                    }

                } else { //plain template   e.g. ##id##
                    try {
                        $.JST._templates[type] = templateBody;
                    } catch (e) {
                        console.error("JST error: "+type, e,templateBody);
                    }
                }

                tmpl.remove();

            });
        });
    },

    createFromTemplate: function(jsonData, template, transformToPrintable) {
        var templates = $.JST._templates;

        var jsData=new Object();
        if (transformToPrintable){
            for (var prop in jsonData){
                var value = jsonData[prop];
                if (typeof(value) == "string")
                    value = (value + "").replace(/\n/g, "<br>");
                jsData[prop]=value;
            }
        } else {
            jsData=jsonData;
        }

        function fillStripData(strip, data) {
            for (var prop in data) {
                var value = data[prop];

                strip = strip.replace(new RegExp("##" + prop + "##", "gi"), value);
            }
            // then clean the remaining ##xxx##
            strip = strip.replace(new RegExp("##\\w+##", "gi"), "");
            return strip;
        }

        var stripString = "";
        if (typeof(template) == "undefined") {
            alert("Template is required");
            stripString = "<div>Template is required</div>";

        } else if (typeof(templates[template]) == "function") { // resig template
            try {
                stripString = templates[template](jsData);// create a jquery object in memory
            } catch (e) {
                console.error("JST error: "+template,e.message);
                stripString = "<div> ERROR: "+template+"<br>" + e.message + "</div>";
            }

        } else {
            stripString = templates[template]; // recover strip template
            if (!stripString || stripString.trim() == "") {
                console.error("No template found for type '" + template + "'");
                return $("<div>");

            } else {
                stripString = fillStripData(stripString, jsData); //replace placeholders with data
            }
        }

        var ret = $(stripString);// create a jquery object in memory
        ret.attr("__template", template); // set __template attribute

        //decorate the strip
        var dec = $.JST._decorators[template];
        if (typeof (dec) == "function")
            dec(ret, jsData);

        return ret;
    },


    existsTemplate: function(template) {
        return $.JST._templates[template];
    },

    //decorate function is like function(domElement,jsonData){...}
    loadDecorator:function(template, decorator) {
        $.JST._decorators[template] = decorator;
    },

    getDecorator:function(template) {
        return $.JST._decorators[template];
    },

    decorateTemplate:function(element) {
        var dec = $.JST._decorators[element.attr("__template")];
        if (typeof (dec) == "function")
            dec(editor);
    },

    // asynchronous
    ajaxLoadAsynchTemplates: function(templateUrl, callback) {

        $.get(templateUrl, function(data) {

            var div = $("<div>");
            div.html(data);

            $.JST.loadTemplates(div);

            if (typeof(callback == "function"))
                callback();
        },"html");
    },

    ajaxLoadTemplates: function(templateUrl) {
        $.ajax({
            async:false,
            url: templateUrl,
            dataType: "html",
            success: function(data) {
                var div = $("<div>");
                div.html(data);
                $.JST.loadTemplates(div);
            }
        });
    }
};

/*******************************************************************************
 * jquery.mb.components
 * file: jquery.mb.slider.js
 * last modified: 18/11/17 18.21
 * Version:  {{ version }}
 * Build:  {{ buildnum }}
 *
 * Open Lab s.r.l., Florence - Italy
 * email: matteo@open-lab.com
 * site:  http://pupunzi.com
 *  http://open-lab.com
 * blog:  http://pupunzi.open-lab.com
 *
 * Licences: MIT, GPL
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Copyright (c) 2001-2017. Matteo Bicocchi (Pupunzi)
 ******************************************************************************/

(function ($) {

    $.mbSlider = {
        name   : "mb.slider",
        author : "Matteo Bicocchi",
        version: "1.6.0",

        defaults: {
            minVal       : 0,
            maxVal       : 100,
            grid         : 0,
            showVal      : true,
            labelPos     : "top",
            rangeColor   : "#000",
            negativeColor: "#e20000",
            formatValue  : function (val) {return parseFloat(val)},
            onSlideLoad  : function (o) {},
            onStart      : function (o) {},
            onSlide      : function (o) {},
            onStop       : function (o) {}
        },

        buildSlider: function (options) {
            return this.each(function () {
                var slider = this;
                var $slider = $(slider);
                $slider.addClass("mb_slider");

                slider.options = {};
                slider.metadata = $slider.data("property") && typeof $slider.data("property") == "string" ? eval('(' + $slider.data("property") + ')') : $slider.data("property");
                $.extend(slider.options, $.mbSlider.defaults, options, this.metadata);
                slider.options.element = slider;

                if (slider.options.grid == 0)
                    slider.options.grid = 1;

                if (this.options.startAt < 0 && this.options.startAt < slider.options.minVal)
                    slider.options.minVal = parseFloat(this.options.startAt);

                slider.actualPos = this.options.startAt;

                /**
                 * Slider UI builder
                 */
                slider.sliderStart = $("<div class='mb_sliderStart'/>");
                slider.sliderEnd = $("<div class='mb_sliderEnd'/>");
                slider.sliderValue = $("<div class='mb_sliderValue'/>").css({color: this.options.rangeColor});
                slider.sliderZeroLabel = $("<div class='mb_sliderZeroLabel'>0</div>").css({position: "absolute", top: (slider.options.labelPos == "top" ? -18 : 29)});
                slider.sliderValueLabel = $("<div class='mb_sliderValueLabel'/>").css({position: "absolute", borderTop: "2px solid " + slider.options.rangeColor});

                slider.sliderBar = $("<div class='mb_sliderBar'/>").css({position: "relative", display: "block"});
                slider.sliderRange = $("<div class='mb_sliderRange'/>").css({background: slider.options.rangeColor});
                slider.sliderZero = $("<div class='mb_sliderZero'/>").css({});
                slider.sliderHandler = $("<div class='mb_sliderHandler'/>");

                $(slider).append(slider.sliderBar);
                slider.sliderBar.append(slider.sliderValueLabel);

                if (slider.options.showVal) $(slider).append(slider.sliderEnd);
                if (slider.options.showVal) $(slider).prepend(slider.sliderStart);
                slider.sliderBar.append(slider.sliderRange);
                slider.sliderBar.append(slider.sliderRange);

                if (slider.options.minVal < 0) {
                    slider.sliderBar.append(slider.sliderZero);
                    slider.sliderBar.append(slider.sliderZeroLabel);
                }

                slider.sliderBar.append(slider.sliderHandler);
                slider.rangeVal = slider.options.maxVal - slider.options.minVal;
                slider.zero = slider.options.minVal < 0 ? (slider.sliderBar.outerWidth() * Math.abs(slider.options.minVal)) / slider.rangeVal : 0;
                slider.sliderZero.css({left: 0, width: slider.zero});
                slider.sliderZeroLabel.css({left: slider.zero - 5});

                $(slider).find("div").css({display: "inline-block", clear: "left"});

                $(slider).attr("unselectable", "on");
                $(slider).find("div").attr("unselectable", "on");

                var sliderVal = parseFloat(this.options.startAt) >= slider.options.minVal ? parseFloat(this.options.startAt) : slider.options.minVal;
                slider.sliderValue.html(sliderVal);
                slider.sliderValueLabel.html(slider.options.formatValue(sliderVal));

                slider.sliderStart.html(slider.options.formatValue(slider.options.minVal));
                slider.sliderEnd.html(slider.options.formatValue(slider.options.maxVal));

                if (slider.options.startAt < slider.options.minVal || !slider.options.startAt)
                    this.options.startAt = slider.options.minVal;

                slider.evalPosGrid = parseFloat(slider.actualPos);
                $(slider).mbsetVal(slider.evalPosGrid);

                function setNewPosition(e) {

                    e.preventDefault();
                    e.stopPropagation();

                    var mousePos = e.clientX - slider.sliderBar.offset().left;
                    var grid = (slider.options.grid * slider.sliderBar.outerWidth()) / slider.rangeVal;
                    var posInGrid = grid * Math.round(mousePos / grid);
                    var evalPos = ((slider.options.maxVal - slider.options.minVal) * posInGrid) / (slider.sliderBar.outerWidth() - (slider.sliderHandler.outerWidth() / 2)) + parseFloat(slider.options.minVal);

                    slider.evalPosGrid = Math.max(slider.options.minVal, Math.min(slider.options.maxVal, slider.options.grid * Math.round(evalPos / slider.options.grid)));

                    if (typeof slider.options.onSlide == "function" && slider.gridStep != posInGrid) {
                        slider.gridStep = posInGrid;
                        slider.options.onSlide(slider);
                    }

                    $(slider).mbsetVal(slider.evalPosGrid);

                }

                /**
                 * Slider Events
                 *
                 * Add start event both on slider bar and on slider handler
                 */
                var sliderElements = slider.sliderBar.add(slider.sliderHandler);

                sliderElements.on("mousedown.mb_slider", function (e) {

                    if (!$(e.target).is(slider.sliderHandler))
                        setNewPosition(e);

                    if (typeof slider.options.onStart == "function")
                        slider.options.onStart(slider);

                    $(document).on("mousemove.mb_slider", function (e) {
                        setNewPosition(e);
                    });

                    $(document).on("mouseup.mb_slider", function () {
                        $(document).off("mousemove.mb_slider").off("mouseup.mb_slider");
                        if (typeof slider.options.onStop == "function")
                            slider.options.onStop(slider);
                    });

                });

                $(window).on("resize", function() {
                    $(slider).mbsetVal(slider.evalPosGrid);
                })

                if (typeof slider.options.onSlideLoad == "function")
                    slider.options.onSlideLoad(slider);
            });
        },

        setVal: function (val) {
            var slider = $(this).get(0);
            if (val > slider.options.maxVal) val = slider.options.maxVal;
            if (val < slider.options.minVal) val = slider.options.minVal;
            var startPos = val == slider.options.minVal ? 0 : Math.round(((val - slider.options.minVal) * slider.sliderBar.outerWidth()) / slider.rangeVal);
            startPos = startPos >= 0 ? startPos : slider.zero + val;
            var grid = (slider.options.grid * slider.sliderBar.outerWidth()) / slider.rangeVal;
            var posInGrid = grid * Math.round(startPos / grid);

            slider.evalPosGrid = slider.options.grid * Math.round(val / slider.options.grid);
            slider.sliderHandler.css({left: posInGrid - slider.sliderHandler.outerWidth()/2});
            slider.sliderValueLabel.css({left: posInGrid - (slider.sliderHandler.outerWidth() / 2) - (slider.sliderValueLabel.outerWidth() - slider.sliderHandler.outerWidth()) / 2});

            if (slider.evalPosGrid >= 0) {
                slider.sliderValueLabel.css({borderTop: "2px solid " + slider.options.rangeColor});
                slider.sliderRange.css({left: 0, width: posInGrid, background: slider.options.rangeColor}).removeClass("negative");
                slider.sliderZero.css({width: slider.zero});
            } else {
                slider.sliderValueLabel.css({borderTop: "2px solid " + slider.options.negativeColor});
                slider.sliderRange.css({left: 0, width: slider.zero, background: slider.options.negativeColor}).addClass("negative");
                slider.sliderZero.css({width: posInGrid + (slider.sliderHandler.outerWidth() / 2)});
            }

            if (startPos >= slider.sliderBar.outerWidth() && slider.sliderValueLabel.outerWidth() > 40)
                slider.sliderValueLabel.addClass("right");

            else if (startPos <= 0 && slider.sliderValueLabel.outerWidth() > 40)
                slider.sliderValueLabel.addClass("left");

            else
                slider.sliderValueLabel.removeClass("left right");


            slider.sliderValue.html(val >= slider.options.minVal ? slider.evalPosGrid : slider.options.minVal);
            slider.sliderValueLabel.html(slider.options.formatValue(val >= slider.options.minVal ? slider.evalPosGrid : slider.options.minVal));
        },

        getVal: function () {
            var slider = $(this).get(0);
            return slider.evalPosGrid;
        }
    };

    $.fn.mbSlider = $.mbSlider.buildSlider;
    $.fn.mbsetVal = $.mbSlider.setVal;
    $.fn.mbgetVal = $.mbSlider.getVal;

})(jQuery);

/* http://keith-wood.name/svg.html
   SVG for jQuery v1.4.5.
   Written by Keith Wood (kbwood{at}iinet.com.au) August 2007.
   Dual licensed under the GPL (http://dev.jquery.com/browser/trunk/jquery/GPL-LICENSE.txt) and
   MIT (http://dev.jquery.com/browser/trunk/jquery/MIT-LICENSE.txt) licenses.
   Please attribute the author if you use it. */
(function($){function SVGManager(){this._settings=[];this._extensions=[];this.regional=[];this.regional['']={errorLoadingText:'Error loading',notSupportedText:'This browser does not support SVG'};this.local=this.regional[''];this._uuid=new Date().getTime();this._renesis=detectActiveX('RenesisX.RenesisCtrl')}function detectActiveX(a){try{return!!(window.ActiveXObject&&new ActiveXObject(a))}catch(e){return false}}var q='svgwrapper';$.extend(SVGManager.prototype,{markerClassName:'hasSVG',svgNS:'http://www.w3.org/2000/svg',xlinkNS:'http://www.w3.org/1999/xlink',_wrapperClass:SVGWrapper,_attrNames:{class_:'class',in_:'in',alignmentBaseline:'alignment-baseline',baselineShift:'baseline-shift',clipPath:'clip-path',clipRule:'clip-rule',colorInterpolation:'color-interpolation',colorInterpolationFilters:'color-interpolation-filters',colorRendering:'color-rendering',dominantBaseline:'dominant-baseline',enableBackground:'enable-background',fillOpacity:'fill-opacity',fillRule:'fill-rule',floodColor:'flood-color',floodOpacity:'flood-opacity',fontFamily:'font-family',fontSize:'font-size',fontSizeAdjust:'font-size-adjust',fontStretch:'font-stretch',fontStyle:'font-style',fontVariant:'font-variant',fontWeight:'font-weight',glyphOrientationHorizontal:'glyph-orientation-horizontal',glyphOrientationVertical:'glyph-orientation-vertical',horizAdvX:'horiz-adv-x',horizOriginX:'horiz-origin-x',imageRendering:'image-rendering',letterSpacing:'letter-spacing',lightingColor:'lighting-color',markerEnd:'marker-end',markerMid:'marker-mid',markerStart:'marker-start',stopColor:'stop-color',stopOpacity:'stop-opacity',strikethroughPosition:'strikethrough-position',strikethroughThickness:'strikethrough-thickness',strokeDashArray:'stroke-dasharray',strokeDashOffset:'stroke-dashoffset',strokeLineCap:'stroke-linecap',strokeLineJoin:'stroke-linejoin',strokeMiterLimit:'stroke-miterlimit',strokeOpacity:'stroke-opacity',strokeWidth:'stroke-width',textAnchor:'text-anchor',textDecoration:'text-decoration',textRendering:'text-rendering',underlinePosition:'underline-position',underlineThickness:'underline-thickness',vertAdvY:'vert-adv-y',vertOriginY:'vert-origin-y',wordSpacing:'word-spacing',writingMode:'writing-mode'},_attachSVG:function(a,b){var c=(a.namespaceURI==this.svgNS?a:null);var a=(c?null:a);if($(a||c).hasClass(this.markerClassName)){return}if(typeof b=='string'){b={loadURL:b}}else if(typeof b=='function'){b={onLoad:b}}$(a||c).addClass(this.markerClassName);try{if(!c){c=document.createElementNS(this.svgNS,'svg');c.setAttribute('version','1.1');if(a.clientWidth>0){c.setAttribute('width',a.clientWidth)}if(a.clientHeight>0){c.setAttribute('height',a.clientHeight)}a.appendChild(c)}this._afterLoad(a,c,b||{})}catch(e){if($.browser.msie){if(!a.id){a.id='svg'+(this._uuid++)}this._settings[a.id]=b;a.innerHTML='<embed type="image/svg+xml" width="100%" '+'height="100%" src="'+(b.initPath||'')+'blank.svg" '+'pluginspage="http://www.adobe.com/svg/viewer/install/main.html"/>'}else{a.innerHTML='<p class="svg_error">'+this.local.notSupportedText+'</p>'}}},_registerSVG:function(){for(var i=0;i<document.embeds.length;i++){var a=document.embeds[i].parentNode;if(!$(a).hasClass($.svg.markerClassName)||$.data(a,q)){continue}var b=null;try{b=document.embeds[i].getSVGDocument()}catch(e){setTimeout($.svg._registerSVG,250);return}b=(b?b.documentElement:null);if(b){$.svg._afterLoad(a,b)}}},_afterLoad:function(a,b,c){var c=c||this._settings[a.id];this._settings[a?a.id:'']=null;var d=new this._wrapperClass(b,a);$.data(a||b,q,d);try{if(c.loadURL){d.load(c.loadURL,c)}if(c.settings){d.configure(c.settings)}if(c.onLoad&&!c.loadURL){c.onLoad.apply(a||b,[d])}}catch(e){alert(e)}},_getSVG:function(a){a=(typeof a=='string'?$(a)[0]:(a.jquery?a[0]:a));return $.data(a,q)},_destroySVG:function(a){var b=$(a);if(!b.hasClass(this.markerClassName)){return}b.removeClass(this.markerClassName);if(a.namespaceURI!=this.svgNS){b.empty()}$.removeData(a,q)},addExtension:function(a,b){this._extensions.push([a,b])},isSVGElem:function(a){return(a.nodeType==1&&a.namespaceURI==$.svg.svgNS)}});function SVGWrapper(a,b){this._svg=a;this._container=b;for(var i=0;i<$.svg._extensions.length;i++){var c=$.svg._extensions[i];this[c[0]]=new c[1](this)}}$.extend(SVGWrapper.prototype,{_width:function(){return(this._container?this._container.clientWidth:this._svg.width)},_height:function(){return(this._container?this._container.clientHeight:this._svg.height)},root:function(){return this._svg},configure:function(a,b,c){if(!a.nodeName){c=b;b=a;a=this._svg}if(c){for(var i=a.attributes.length-1;i>=0;i--){var d=a.attributes.item(i);if(!(d.nodeName=='onload'||d.nodeName=='version'||d.nodeName.substring(0,5)=='xmlns')){a.attributes.removeNamedItem(d.nodeName)}}}for(var e in b){a.setAttribute($.svg._attrNames[e]||e,b[e])}return this},getElementById:function(a){return this._svg.ownerDocument.getElementById(a)},change:function(a,b){if(a){for(var c in b){if(b[c]==null){a.removeAttribute($.svg._attrNames[c]||c)}else{a.setAttribute($.svg._attrNames[c]||c,b[c])}}}return this},_args:function(b,c,d){c.splice(0,0,'parent');c.splice(c.length,0,'settings');var e={};var f=0;if(b[0]!=null&&b[0].jquery){b[0]=b[0][0]}if(b[0]!=null&&!(typeof b[0]=='object'&&b[0].nodeName)){e['parent']=null;f=1}for(var i=0;i<b.length;i++){e[c[i+f]]=b[i]}if(d){$.each(d,function(i,a){if(typeof e[a]=='object'){e.settings=e[a];e[a]=null}})}return e},title:function(a,b,c){var d=this._args(arguments,['text']);var e=this._makeNode(d.parent,'title',d.settings||{});e.appendChild(this._svg.ownerDocument.createTextNode(d.text));return e},describe:function(a,b,c){var d=this._args(arguments,['text']);var e=this._makeNode(d.parent,'desc',d.settings||{});e.appendChild(this._svg.ownerDocument.createTextNode(d.text));return e},defs:function(a,b,c){var d=this._args(arguments,['id'],['id']);return this._makeNode(d.parent,'defs',$.extend((d.id?{id:d.id}:{}),d.settings||{}))},symbol:function(a,b,c,d,e,f,g){var h=this._args(arguments,['id','x1','y1','width','height']);return this._makeNode(h.parent,'symbol',$.extend({id:h.id,viewBox:h.x1+' '+h.y1+' '+h.width+' '+h.height},h.settings||{}))},marker:function(a,b,c,d,e,f,g,h){var i=this._args(arguments,['id','refX','refY','mWidth','mHeight','orient'],['orient']);return this._makeNode(i.parent,'marker',$.extend({id:i.id,refX:i.refX,refY:i.refY,markerWidth:i.mWidth,markerHeight:i.mHeight,orient:i.orient||'auto'},i.settings||{}))},style:function(a,b,c){var d=this._args(arguments,['styles']);var e=this._makeNode(d.parent,'style',$.extend({type:'text/css'},d.settings||{}));e.appendChild(this._svg.ownerDocument.createTextNode(d.styles));if($.browser.opera){$('head').append('<style type="text/css">'+d.styles+'</style>')}return e},script:function(a,b,c,d){var e=this._args(arguments,['script','type'],['type']);var f=this._makeNode(e.parent,'script',$.extend({type:e.type||'text/javascript'},e.settings||{}));f.appendChild(this._svg.ownerDocument.createTextNode(e.script));if(!$.browser.mozilla){$.globalEval(e.script)}return f},linearGradient:function(a,b,c,d,e,f,g,h){var i=this._args(arguments,['id','stops','x1','y1','x2','y2'],['x1']);var j=$.extend({id:i.id},(i.x1!=null?{x1:i.x1,y1:i.y1,x2:i.x2,y2:i.y2}:{}));return this._gradient(i.parent,'linearGradient',$.extend(j,i.settings||{}),i.stops)},radialGradient:function(a,b,c,d,e,r,f,g,h){var i=this._args(arguments,['id','stops','cx','cy','r','fx','fy'],['cx']);var j=$.extend({id:i.id},(i.cx!=null?{cx:i.cx,cy:i.cy,r:i.r,fx:i.fx,fy:i.fy}:{}));return this._gradient(i.parent,'radialGradient',$.extend(j,i.settings||{}),i.stops)},_gradient:function(a,b,c,d){var e=this._makeNode(a,b,c);for(var i=0;i<d.length;i++){var f=d[i];this._makeNode(e,'stop',$.extend({offset:f[0],stopColor:f[1]},(f[2]!=null?{stopOpacity:f[2]}:{})))}return e},pattern:function(a,b,x,y,c,d,e,f,g,h,i){var j=this._args(arguments,['id','x','y','width','height','vx','vy','vwidth','vheight'],['vx']);var k=$.extend({id:j.id,x:j.x,y:j.y,width:j.width,height:j.height},(j.vx!=null?{viewBox:j.vx+' '+j.vy+' '+j.vwidth+' '+j.vheight}:{}));return this._makeNode(j.parent,'pattern',$.extend(k,j.settings||{}))},clipPath:function(a,b,c,d){var e=this._args(arguments,['id','units']);e.units=e.units||'userSpaceOnUse';return this._makeNode(e.parent,'clipPath',$.extend({id:e.id,clipPathUnits:e.units},e.settings||{}))},mask:function(a,b,x,y,c,d,e){var f=this._args(arguments,['id','x','y','width','height']);return this._makeNode(f.parent,'mask',$.extend({id:f.id,x:f.x,y:f.y,width:f.width,height:f.height},f.settings||{}))},createPath:function(){return new SVGPath()},createText:function(){return new SVGText()},svg:function(a,x,y,b,c,d,e,f,g,h){var i=this._args(arguments,['x','y','width','height','vx','vy','vwidth','vheight'],['vx']);var j=$.extend({x:i.x,y:i.y,width:i.width,height:i.height},(i.vx!=null?{viewBox:i.vx+' '+i.vy+' '+i.vwidth+' '+i.vheight}:{}));return this._makeNode(i.parent,'svg',$.extend(j,i.settings||{}))},group:function(a,b,c){var d=this._args(arguments,['id'],['id']);return this._makeNode(d.parent,'g',$.extend({id:d.id},d.settings||{}))},use:function(a,x,y,b,c,d,e){var f=this._args(arguments,['x','y','width','height','ref']);if(typeof f.x=='string'){f.ref=f.x;f.settings=f.y;f.x=f.y=f.width=f.height=null}var g=this._makeNode(f.parent,'use',$.extend({x:f.x,y:f.y,width:f.width,height:f.height},f.settings||{}));g.setAttributeNS($.svg.xlinkNS,'href',f.ref);return g},link:function(a,b,c){var d=this._args(arguments,['ref']);var e=this._makeNode(d.parent,'a',d.settings);e.setAttributeNS($.svg.xlinkNS,'href',d.ref);return e},image:function(a,x,y,b,c,d,e){var f=this._args(arguments,['x','y','width','height','ref']);var g=this._makeNode(f.parent,'image',$.extend({x:f.x,y:f.y,width:f.width,height:f.height},f.settings||{}));g.setAttributeNS($.svg.xlinkNS,'href',f.ref);return g},path:function(a,b,c){var d=this._args(arguments,['path']);return this._makeNode(d.parent,'path',$.extend({d:(d.path.path?d.path.path():d.path)},d.settings||{}))},rect:function(a,x,y,b,c,d,e,f){var g=this._args(arguments,['x','y','width','height','rx','ry'],['rx']);return this._makeNode(g.parent,'rect',$.extend({x:g.x,y:g.y,width:g.width,height:g.height},(g.rx?{rx:g.rx,ry:g.ry}:{}),g.settings||{}))},circle:function(a,b,c,r,d){var e=this._args(arguments,['cx','cy','r']);return this._makeNode(e.parent,'circle',$.extend({cx:e.cx,cy:e.cy,r:e.r},e.settings||{}))},ellipse:function(a,b,c,d,e,f){var g=this._args(arguments,['cx','cy','rx','ry']);return this._makeNode(g.parent,'ellipse',$.extend({cx:g.cx,cy:g.cy,rx:g.rx,ry:g.ry},g.settings||{}))},line:function(a,b,c,d,e,f){var g=this._args(arguments,['x1','y1','x2','y2']);return this._makeNode(g.parent,'line',$.extend({x1:g.x1,y1:g.y1,x2:g.x2,y2:g.y2},g.settings||{}))},polyline:function(a,b,c){var d=this._args(arguments,['points']);return this._poly(d.parent,'polyline',d.points,d.settings)},polygon:function(a,b,c){var d=this._args(arguments,['points']);return this._poly(d.parent,'polygon',d.points,d.settings)},_poly:function(a,b,c,d){var e='';for(var i=0;i<c.length;i++){e+=c[i].join()+' '}return this._makeNode(a,b,$.extend({points:$.trim(e)},d||{}))},text:function(a,x,y,b,c){var d=this._args(arguments,['x','y','value']);if(typeof d.x=='string'&&arguments.length<4){d.value=d.x;d.settings=d.y;d.x=d.y=null}return this._text(d.parent,'text',d.value,$.extend({x:(d.x&&isArray(d.x)?d.x.join(' '):d.x),y:(d.y&&isArray(d.y)?d.y.join(' '):d.y)},d.settings||{}))},textpath:function(a,b,c,d){var e=this._args(arguments,['path','value']);var f=this._text(e.parent,'textPath',e.value,e.settings||{});f.setAttributeNS($.svg.xlinkNS,'href',e.path);return f},_text:function(a,b,c,d){var e=this._makeNode(a,b,d);if(typeof c=='string'){e.appendChild(e.ownerDocument.createTextNode(c))}else{for(var i=0;i<c._parts.length;i++){var f=c._parts[i];if(f[0]=='tspan'){var g=this._makeNode(e,f[0],f[2]);g.appendChild(e.ownerDocument.createTextNode(f[1]));e.appendChild(g)}else if(f[0]=='tref'){var g=this._makeNode(e,f[0],f[2]);g.setAttributeNS($.svg.xlinkNS,'href',f[1]);e.appendChild(g)}else if(f[0]=='textpath'){var h=$.extend({},f[2]);h.href=null;var g=this._makeNode(e,f[0],h);g.setAttributeNS($.svg.xlinkNS,'href',f[2].href);g.appendChild(e.ownerDocument.createTextNode(f[1]));e.appendChild(g)}else{e.appendChild(e.ownerDocument.createTextNode(f[1]))}}}return e},other:function(a,b,c){var d=this._args(arguments,['name']);return this._makeNode(d.parent,d.name,d.settings||{})},_makeNode:function(a,b,c){a=a||this._svg;var d=this._svg.ownerDocument.createElementNS($.svg.svgNS,b);for(var b in c){var e=c[b];if(e!=null&&e!=null&&(typeof e!='string'||e!='')){d.setAttribute($.svg._attrNames[b]||b,e)}}a.appendChild(d);return d},add:function(b,c){var d=this._args((arguments.length==1?[null,b]:arguments),['node']);var f=this;d.parent=d.parent||this._svg;d.node=(d.node.jquery?d.node:$(d.node));try{if($.svg._renesis){throw'Force traversal';}d.parent.appendChild(d.node.cloneNode(true))}catch(e){d.node.each(function(){var a=f._cloneAsSVG(this);if(a){d.parent.appendChild(a)}})}return this},clone:function(b,c){var d=this;var e=this._args((arguments.length==1?[null,b]:arguments),['node']);e.parent=e.parent||this._svg;e.node=(e.node.jquery?e.node:$(e.node));var f=[];e.node.each(function(){var a=d._cloneAsSVG(this);if(a){a.id='';e.parent.appendChild(a);f.push(a)}});return f},_cloneAsSVG:function(a){var b=null;if(a.nodeType==1){b=this._svg.ownerDocument.createElementNS($.svg.svgNS,this._checkName(a.nodeName));for(var i=0;i<a.attributes.length;i++){var c=a.attributes.item(i);if(c.nodeName!='xmlns'&&c.nodeValue){if(c.prefix=='xlink'){b.setAttributeNS($.svg.xlinkNS,c.localName||c.baseName,c.nodeValue)}else{b.setAttribute(this._checkName(c.nodeName),c.nodeValue)}}}for(var i=0;i<a.childNodes.length;i++){var d=this._cloneAsSVG(a.childNodes[i]);if(d){b.appendChild(d)}}}else if(a.nodeType==3){if($.trim(a.nodeValue)){b=this._svg.ownerDocument.createTextNode(a.nodeValue)}}else if(a.nodeType==4){if($.trim(a.nodeValue)){try{b=this._svg.ownerDocument.createCDATASection(a.nodeValue)}catch(e){b=this._svg.ownerDocument.createTextNode(a.nodeValue.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'))}}}return b},_checkName:function(a){a=(a.substring(0,1)>='A'&&a.substring(0,1)<='Z'?a.toLowerCase():a);return(a.substring(0,4)=='svg:'?a.substring(4):a)},load:function(j,k){k=(typeof k=='boolean'?{addTo:k}:(typeof k=='function'?{onLoad:k}:(typeof k=='string'?{parent:k}:(typeof k=='object'&&k.nodeName?{parent:k}:(typeof k=='object'&&k.jquery?{parent:k}:k||{})))));if(!k.parent&&!k.addTo){this.clear(false)}var l=[this._svg.getAttribute('width'),this._svg.getAttribute('height')];var m=this;var n=function(a){a=$.svg.local.errorLoadingText+': '+a;if(k.onLoad){k.onLoad.apply(m._container||m._svg,[m,a])}else{m.text(null,10,20,a)}};var o=function(a){var b=new ActiveXObject('Microsoft.XMLDOM');b.validateOnParse=false;b.resolveExternals=false;b.async=false;b.loadXML(a);if(b.parseError.errorCode!=0){n(b.parseError.reason);return null}return b};var p=function(a){if(!a){return}if(a.documentElement.nodeName!='svg'){var b=a.getElementsByTagName('parsererror');var c=(b.length?b[0].getElementsByTagName('div'):[]);n(!b.length?'???':(c.length?c[0]:b[0]).firstChild.nodeValue);return}var d=(k.parent?$(k.parent)[0]:m._svg);var f={};for(var i=0;i<a.documentElement.attributes.length;i++){var g=a.documentElement.attributes.item(i);if(!(g.nodeName=='version'||g.nodeName.substring(0,5)=='xmlns')){f[g.nodeName]=g.nodeValue}}m.configure(d,f,!k.parent);var h=a.documentElement.childNodes;for(var i=0;i<h.length;i++){try{if($.svg._renesis){throw'Force traversal';}d.appendChild(m._svg.ownerDocument.importNode(h[i],true));if(h[i].nodeName=='script'){$.globalEval(h[i].textContent)}}catch(e){m.add(d,h[i])}}if(!k.changeSize){m.configure(d,{width:l[0],height:l[1]})}if(k.onLoad){k.onLoad.apply(m._container||m._svg,[m])}};if(j.match('<svg')){p($.browser.msie?o(j):new DOMParser().parseFromString(j,'text/xml'))}else{$.ajax({url:j,dataType:($.browser.msie?'text':'xml'),success:function(a){p($.browser.msie?o(a):a)},error:function(a,b,c){n(b+(c?' '+c.message:''))}})}return this},remove:function(a){a=(a.jquery?a[0]:a);a.parentNode.removeChild(a);return this},clear:function(a){if(a){this.configure({},true)}while(this._svg.firstChild){this._svg.removeChild(this._svg.firstChild)}return this},toSVG:function(a){a=a||this._svg;return(typeof XMLSerializer=='undefined'?this._toSVG(a):new XMLSerializer().serializeToString(a))},_toSVG:function(a){var b='';if(!a){return b}if(a.nodeType==3){b=a.nodeValue}else if(a.nodeType==4){b='<![CDATA['+a.nodeValue+']]>'}else{b='<'+a.nodeName;if(a.attributes){for(var i=0;i<a.attributes.length;i++){var c=a.attributes.item(i);if(!($.trim(c.nodeValue)==''||c.nodeValue.match(/^\[object/)||c.nodeValue.match(/^function/))){b+=' '+(c.namespaceURI==$.svg.xlinkNS?'xlink:':'')+c.nodeName+'="'+c.nodeValue+'"'}}}if(a.firstChild){b+='>';var d=a.firstChild;while(d){b+=this._toSVG(d);d=d.nextSibling}b+='</'+a.nodeName+'>'}else{b+='/>'}}return b}});function SVGPath(){this._path=''}$.extend(SVGPath.prototype,{reset:function(){this._path='';return this},move:function(x,y,a){a=(isArray(x)?y:a);return this._coords((a?'m':'M'),x,y)},line:function(x,y,a){a=(isArray(x)?y:a);return this._coords((a?'l':'L'),x,y)},horiz:function(x,a){this._path+=(a?'h':'H')+(isArray(x)?x.join(' '):x);return this},vert:function(y,a){this._path+=(a?'v':'V')+(isArray(y)?y.join(' '):y);return this},curveC:function(a,b,c,d,x,y,e){e=(isArray(a)?b:e);return this._coords((e?'c':'C'),a,b,c,d,x,y)},smoothC:function(a,b,x,y,c){c=(isArray(a)?b:c);return this._coords((c?'s':'S'),a,b,x,y)},curveQ:function(a,b,x,y,c){c=(isArray(a)?b:c);return this._coords((c?'q':'Q'),a,b,x,y)},smoothQ:function(x,y,a){a=(isArray(x)?y:a);return this._coords((a?'t':'T'),x,y)},_coords:function(a,b,c,d,e,f,g){if(isArray(b)){for(var i=0;i<b.length;i++){var h=b[i];this._path+=(i==0?a:' ')+h[0]+','+h[1]+(h.length<4?'':' '+h[2]+','+h[3]+(h.length<6?'':' '+h[4]+','+h[5]))}}else{this._path+=a+b+','+c+(d==null?'':' '+d+','+e+(f==null?'':' '+f+','+g))}return this},arc:function(a,b,c,d,e,x,y,f){f=(isArray(a)?b:f);this._path+=(f?'a':'A');if(isArray(a)){for(var i=0;i<a.length;i++){var g=a[i];this._path+=(i==0?'':' ')+g[0]+','+g[1]+' '+g[2]+' '+(g[3]?'1':'0')+','+(g[4]?'1':'0')+' '+g[5]+','+g[6]}}else{this._path+=a+','+b+' '+c+' '+(d?'1':'0')+','+(e?'1':'0')+' '+x+','+y}return this},close:function(){this._path+='z';return this},path:function(){return this._path}});SVGPath.prototype.moveTo=SVGPath.prototype.move;SVGPath.prototype.lineTo=SVGPath.prototype.line;SVGPath.prototype.horizTo=SVGPath.prototype.horiz;SVGPath.prototype.vertTo=SVGPath.prototype.vert;SVGPath.prototype.curveCTo=SVGPath.prototype.curveC;SVGPath.prototype.smoothCTo=SVGPath.prototype.smoothC;SVGPath.prototype.curveQTo=SVGPath.prototype.curveQ;SVGPath.prototype.smoothQTo=SVGPath.prototype.smoothQ;SVGPath.prototype.arcTo=SVGPath.prototype.arc;function SVGText(){this._parts=[]}$.extend(SVGText.prototype,{reset:function(){this._parts=[];return this},string:function(a){this._parts[this._parts.length]=['text',a];return this},span:function(a,b){this._parts[this._parts.length]=['tspan',a,b];return this},ref:function(a,b){this._parts[this._parts.length]=['tref',a,b];return this},path:function(a,b,c){this._parts[this._parts.length]=['textpath',b,$.extend({href:a},c||{})];return this}});$.fn.svg=function(a){var b=Array.prototype.slice.call(arguments,1);if(typeof a=='string'&&a=='get'){return $.svg['_'+a+'SVG'].apply($.svg,[this[0]].concat(b))}return this.each(function(){if(typeof a=='string'){$.svg['_'+a+'SVG'].apply($.svg,[this].concat(b))}else{$.svg._attachSVG(this,a||{})}})};function isArray(a){return(a&&a.constructor==Array)}$.svg=new SVGManager()})(jQuery);

/* http://keith-wood.name/svg.html
 jQuery DOM compatibility for jQuery SVG v1.4.5.
 Written by Keith Wood (kbwood{at}iinet.com.au) April 2009.
 Dual licensed under the GPL (http://dev.jquery.com/browser/trunk/jquery/GPL-LICENSE.txt) and
 MIT (http://dev.jquery.com/browser/trunk/jquery/MIT-LICENSE.txt) licenses.
 Please attribute the author if you use it. */

(function ($) { // Hide scope, no $ conflict

    var rclass = /[\t\r\n]/g,
        rspace = /\s+/,
        rwhitespace = "[\\x20\\t\\r\\n\\f]";

    /* Support adding class names to SVG nodes. */
    $.fn.addClass = function (origAddClass) {
        return function (value) {
            var classNames, i, l, elem,
                setClass, c, cl;

            if (jQuery.isFunction(value)) {
                return this.each(function (j) {
                    jQuery(this).addClass(value.call(this, j, this.className));
                });
            }

            if (value && typeof value === "string") {
                classNames = value.split(rspace);

                for (i = 0, l = this.length; i < l; i++) {
                    elem = this[ i ];

                    if (elem.nodeType === 1) {
                        if (!(elem.className && elem.getAttribute('class')) && classNames.length === 1) {
                            if ($.svg.isSVGElem(elem)) {
                                (elem.className ? elem.className.baseVal = value
                                    : elem.setAttribute('class', value));
                            } else {
                                elem.className = value;
                            }
                        } else {
                            setClass = !$.svg.isSVGElem(elem) ? elem.className :
                                elem.className ? elem.className.baseVal :
                                    elem.getAttribute('class');

                            setClass = (" " + setClass + " ");
                            for (c = 0, cl = classNames.length; c < cl; c++) {
                                if (setClass.indexOf(" " + classNames[ c ] + " ") < 0) {
                                    setClass += classNames[ c ] + " ";
                                }
                            }

                            setClass = jQuery.trim(setClass);
                            if ($.svg.isSVGElem(elem)) {

                                (elem.className ? elem.className.baseVal = setClass
                                    : elem.setAttribute('class', setClass));
                            } else {
                                elem.className = setClass;
                            }
                        }
                    }
                }
            }

            return this;
        };
    }($.fn.addClass);

    /* Support removing class names from SVG nodes. */
    $.fn.removeClass = function (origRemoveClass) {
        return function (value) {
            var classNames, i, l, elem, className, c, cl;

            if (jQuery.isFunction(value)) {
                return this.each(function (j) {
                    jQuery(this).removeClass(value.call(this, j, this.className));
                });
            }

            if ((value && typeof value === "string") || value === undefined) {
                classNames = ( value || "" ).split(rspace);

                for (i = 0, l = this.length; i < l; i++) {
                    elem = this[ i ];

                    if (elem.nodeType === 1 && (elem.className || elem.getAttribute('class'))) {
                        if (value) {
                            className = !$.svg.isSVGElem(elem) ? elem.className :
                                elem.className ? elem.className.baseVal :
                                    elem.getAttribute('class');

                            className = (" " + className + " ").replace(rclass, " ");

                            for (c = 0, cl = classNames.length; c < cl; c++) {
                                // Remove until there is nothing to remove,
                                while (className.indexOf(" " + classNames[ c ] + " ") >= 0) {
                                    className = className.replace(" " + classNames[ c ] + " ", " ");
                                }
                            }

                            className = jQuery.trim(className);
                        } else {
                            className = "";
                        }

                        if ($.svg.isSVGElem(elem)) {
                            (elem.className ? elem.className.baseVal = className
                                : elem.setAttribute('class', className));
                        } else {
                            elem.className = className;
                        }
                    }
                }
            }

            return this;
        };
    }($.fn.removeClass);

    /* Support toggling class names on SVG nodes. */
    $.fn.toggleClass = function (origToggleClass) {
        return function (className, state) {
            return this.each(function () {
                if ($.svg.isSVGElem(this)) {
                    if (typeof state !== 'boolean') {
                        state = !$(this).hasClass(className);
                    }
                    $(this)[(state ? 'add' : 'remove') + 'Class'](className);
                }
                else {
                    origToggleClass.apply($(this), [className, state]);
                }
            });
        };
    }($.fn.toggleClass);

    /* Support checking class names on SVG nodes. */
    $.fn.hasClass = function (origHasClass) {
        return function (selector) {

            var className = " " + selector + " ",
                i = 0,
                l = this.length,
                elem, classes;

            for (; i < l; i++) {
                elem = this[i];
                if (elem.nodeType === 1) {
                    classes = !$.svg.isSVGElem(elem) ? elem.className :
                        elem.className ? elem.className.baseVal :
                            elem.getAttribute('class');
                    if ((" " + classes + " ").replace(rclass, " ").indexOf(className) > -1) {
                        return true;
                    }
                }
            }

            return false;
        };
    }($.fn.hasClass);

    /* Support attributes on SVG nodes. */
    $.fn.attr = function (origAttr) {
        return function (name, value, type) {
            var origArgs = arguments;
            if (typeof name === 'string' && value === undefined) {
                var val = origAttr.apply(this, origArgs);
                if (val && val.baseVal && val.baseVal.numberOfItems != null) { // Multiple values
                    value = '';
                    val = val.baseVal;
                    if (name == 'transform') {
                        for (var i = 0; i < val.numberOfItems; i++) {
                            var item = val.getItem(i);
                            switch (item.type) {
                                case 1:
                                    value += ' matrix(' + item.matrix.a + ',' + item.matrix.b + ',' +
                                        item.matrix.c + ',' + item.matrix.d + ',' +
                                        item.matrix.e + ',' + item.matrix.f + ')';
                                    break;
                                case 2:
                                    value += ' translate(' + item.matrix.e + ',' + item.matrix.f + ')';
                                    break;
                                case 3:
                                    value += ' scale(' + item.matrix.a + ',' + item.matrix.d + ')';
                                    break;
                                case 4:
                                    value += ' rotate(' + item.angle + ')';
                                    break; // Doesn't handle new origin
                                case 5:
                                    value += ' skewX(' + item.angle + ')';
                                    break;
                                case 6:
                                    value += ' skewY(' + item.angle + ')';
                                    break;
                            }
                        }
                        val = value.substring(1);
                    }
                    else {
                        val = val.getItem(0).valueAsString;
                    }
                }
                return (val && val.baseVal ? val.baseVal.valueAsString : val);
            }

            var options = name;
            if (typeof name === 'string') {
                options = {};
                options[name] = value;
            }
            return $(this).each(function () {
                if ($.svg.isSVGElem(this)) {
                    for (var n in options) {
                        var val = ($.isFunction(options[n]) ? options[n]() : options[n]);
                        (type ? this.style[n] = val : this.setAttribute(n, val));
                    }
                }
                else {
                    origAttr.apply($(this), origArgs);
                }
            });
        };
    }($.fn.attr);

    /* Support removing attributes on SVG nodes. */
    $.fn.removeAttr = function (origRemoveAttr) {
        return function (name) {
            return this.each(function () {
                if ($.svg.isSVGElem(this)) {
                    (this[name] && this[name].baseVal ? this[name].baseVal.value = '' :
                        this.setAttribute(name, ''));
                }
                else {
                    origRemoveAttr.apply($(this), [name]);
                }
            });
        };
    }($.fn.removeAttr);

    /* Add numeric only properties. */
    $.extend($.cssNumber, {
        'stopOpacity':     true,
        'strokeMitrelimit':true,
        'strokeOpacity':   true
    });

    /* Support retrieving CSS/attribute values on SVG nodes. */
    if ($.cssProps) {
        $.css = function (origCSS) {
            return function (elem, name, numeric, extra) {
                var value = (name.match(/^svg.*/) ? $(elem).attr($.cssProps[name] || name) : '');
                return value || origCSS(elem, name, numeric, extra);
            };
        }($.css);
    }

    $.find.isXML = function (origIsXml) {
        return function (elem) {
            return $.svg.isSVGElem(elem) || origIsXml(elem);
        }
    }($.find.isXML)

    var div = document.createElement('div');
    div.appendChild(document.createComment(''));
    if (div.getElementsByTagName('*').length > 0) { // Make sure no comments are found
        $.expr.find.TAG = function (match, context) {
            var results = context.getElementsByTagName(match[1]);
            if (match[1] === '*') { // Filter out possible comments
                var tmp = [];
                for (var i = 0; results[i] || results.item(i); i++) {
                    if ((results[i] || results.item(i)).nodeType === 1) {
                        tmp.push(results[i] || results.item(i));
                    }
                }
                results = tmp;
            }
            return results;
        };
    }

    $.expr.filter.CLASS = function (className) {
        var pattern = new RegExp("(^|" + rwhitespace + ")" + className + "(" + rwhitespace + "|$)");
        return function (elem) {
            var elemClass = (!$.svg.isSVGElem(elem) ? elem.className || (typeof elem.getAttribute !== "undefined" && elem.getAttribute("class")) || "" :
                (elem.className ? elem.className.baseVal : elem.getAttribute('class')));

            return pattern.test(elemClass);
        };
    };

    /*
     In the removeData function (line 1881, v1.7.2):

     if ( jQuery.support.deleteExpando ) {
     delete elem[ internalKey ];
     } else {
     try { // SVG
     elem.removeAttribute( internalKey );
     } catch (e) {
     elem[ internalKey ] = null;
     }
     }

     In the event.add function (line 2985, v1.7.2):

     if ( !special.setup || special.setup.call( elem, data, namespaces, eventHandle ) === false ) {
     // Bind the global event handler to the element
     try { // SVG
     elem.addEventListener( type, eventHandle, false );
     } catch(e) {
     if ( elem.attachEvent ) {
     elem.attachEvent( "on" + type, eventHandle );
     }
     }
     }

     In the event.remove function (line 3074, v1.7.2):

     if ( !special.teardown || special.teardown.call( elem, namespaces ) === false ) {
     try { // SVG
     elem.removeEventListener(type, elemData.handle, false);
     }
     catch (e) {
     if (elem.detachEvent) {
     elem.detachEvent("on" + type, elemData.handle);
     }
     }
     }

     In the event.fix function (line 3394, v1.7.2):

     if (event.target.namespaceURI == 'http://www.w3.org/2000/svg') { // SVG
     event.button = [1, 4, 2][event.button];
     }

     // Add which for click: 1 === left; 2 === middle; 3 === right
     // Note: button is not normalized, so don't use it
     if ( !event.which && button !== undefined ) {
     event.which = ( button & 1 ? 1 : ( button & 2 ? 3 : ( button & 4 ? 2 : 0 ) ) );
     }

     In the Sizzle function (line 4083, v1.7.2):

     if ( toString.call(checkSet) === "[object Array]" ) {
     if ( !prune ) {
     results.push.apply( results, checkSet );

     } else if ( context && context.nodeType === 1 ) {
     for ( i = 0; checkSet[i] != null; i++ ) {
     if ( checkSet[i] && (checkSet[i] === true || checkSet[i].nodeType === 1 && Sizzle.contains(context, checkSet[i])) ) {
     results.push( set[i] || set.item(i) ); // SVG
     }
     }

     } else {
     for ( i = 0; checkSet[i] != null; i++ ) {
     if ( checkSet[i] && checkSet[i].nodeType === 1 ) {
     results.push( set[i] || set.item(i) ); // SVG
     }
     }
     }
     } else {...

     In the fallback for the Sizzle makeArray function (line 4877, v1.7.2):

     if ( toString.call(array) === "[object Array]" ) {
     Array.prototype.push.apply( ret, array );

     } else {
     if ( typeof array.length === "number" ) {
     for ( var l = array.length; i &lt; l; i++ ) {
     ret.push( array[i] || array.item(i) ); // SVG
     }

     } else {
     for ( ; array[i]; i++ ) {
     ret.push( array[i] );
     }
     }
     }

     In the jQuery.cleandata function (line 6538, v1.7.2):

     if ( deleteExpando ) {
     delete elem[ jQuery.expando ];

     } else {
     try { // SVG
     elem.removeAttribute( jQuery.expando );
     } catch (e) {
     // Ignore
     }
     }

     In the fallback getComputedStyle function (line 6727, v1.7.2):

     defaultView = (elem.ownerDocument ? elem.ownerDocument.defaultView : elem.defaultView); // SVG
     if ( defaultView &&
     (computedStyle = defaultView.getComputedStyle( elem, null )) ) {

     ret = computedStyle.getPropertyValue( name );
     ...

     */

})(jQuery);
