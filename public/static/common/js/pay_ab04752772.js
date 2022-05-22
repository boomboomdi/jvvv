define("zenjs/util/money", ["require"],
function(t) {
    "use strict";
    function e(t) {
        return t = parseFloat(t, 10),
        e._isNaN(t) ? NaN: t.toFixed(2)
    }
    return e.add = function(t, n) {
        return t = parseFloat(t, 10),
        n = parseFloat(n, 10),
        e(t + n)
    },
    e.minus = function(t, n) {
        return t = parseFloat(t, 10),
        n = parseFloat(n, 10),
        e(t - n)
    },
    e.multiply = function(t, n) {
        return t = parseFloat(t, 10),
        n = parseFloat(n, 10),
        e(t * n)
    },
    e.toCent = function(t) {
        return Math.round(100 * parseFloat(t))
    },
    e._isNaN = function(t) {
        return e._isNumber(t) && t != +t
    },
    e._isNumber = function(t) {
        return "[object Number]" == Object.prototype.toString.call(t)
    },
    e
}),
define("zenjs/util/money_cent", ["require", "./money"],
function(t) {
    "use strict";
    var e = t("./money"),
    n = function(t) {
        return t = parseFloat(t) / 100,
        e._isNaN(t) ? NaN: t.toFixed(2)
    };
    n.add = function(t, n) {
        return e.add.apply(this, i(t, n))
    },
    n.minus = function(t, n) {
        return e.minus.apply(this, i(t, n))
    },
    n.multiply = function(t, n) {
        return e.multiply.apply(this, i(t, n))
    },
    n.sum = function(t) {
        return t = i(t),
        t.reduce(e.add)
    };
    var i = function() {
        var t = arguments.length;
        if (0 !== t) {
            if (1 === t) {
                var e = arguments[0],
                n = typeof e;
                return "string" === n || "number" === n ? parseFloat(e) / 100 : e.map(function(t) {
                    return parseFloat(e) / 100
                })
            }
            return Array.prototype.map.call(arguments,
            function(t) {
                return parseFloat(t) / 100
            })
        }
    };
    return n
}),
window.Zepto &&
function(t) {
    t.fn.serializeArray = function() {
        var e, n, i = [],
        o = function(t) {
            if (t.forEach) return t.forEach(o);
            i.push({
                name: e,
                value: t
            })
        };
        return this[0] && t.each(this[0].elements,
        function(i, s) {
            n = s.type,
            e = s.name,
            e && "fieldset" != s.nodeName.toLowerCase() && !s.disabled && "submit" != n && "reset" != n && "button" != n && "file" != n && ("radio" != n && "checkbox" != n || s.checked) && o(t(s).val())
        }),
        i
    },
    t.fn.serialize = function() {
        var t = [];
        return this.serializeArray().forEach(function(e) {
            t.push(encodeURIComponent(e.name) + "=" + encodeURIComponent(e.value))
        }),
        t.join("&")
    },
    t.fn.submit = function(e) {
        if (0 in arguments) this.bind("submit", e);
        else if (this.length) {
            var n = t.Event("submit");
            this.eq(0).trigger(n),
            n.isDefaultPrevented() || this.get(0).submit()
        }
        return this
    }
} (Zepto),
define("vendor/zepto/form",
function() {}),
define("zenjs/util/number", [],
function() {
    return {
        makeRandomString: function(t) {
            var e = "",
            n = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            t = t || 10;
            for (var i = 0; i < t; i++) e += n.charAt(Math.floor(Math.random() * n.length));
            return e
        }
    }
}),
define("bower_components/pop/pop", ["require", "zenjs/events", "zenjs/util/number"],
function(t) {
    var e = function() {},
    n = t("zenjs/events"),
    i = t("zenjs/util/number");
    return window.zenjs = window.zenjs || {},
    n.extend({
        init: function(t) {
            this._window = $(window);
            var n = i.makeRandomString();
            $("body").append('<div id="' + n + '"                 style="display:none; height: 100%;                 position: fixed; top: 0; left: 0; right: 0;                background-color: rgba(0, 0, 0, ' + (t.transparent || ".7") + ');z-index:1000;opacity:0;transition: opacity ease 0.2s;"></div>'),
            this.nBg = $("#" + n),
            this.nBg.on("click", $.proxy(function() {
                this.isCanNotHide || this.hide()
            },
            this));
            var o = i.makeRandomString();
            $("body").append('<div id="' + o + '" class="' + (t.className || "") + '" style="overflow:hidden;visibility: hidden;"></div>'),
            this.nPopContainer = $("#" + o),
            this.nPopContainer.hide(),
            this.nPopContainer.css({
                opacity: 0,
                position: "absolute",
                "z-index": 1e3
            }),
            t.contentViewClass && (this.contentViewClass = t.contentViewClass, this.contentViewOptions = $.extend({
                el: this.nPopContainer
            },
            t.contentViewOptions || {}), this.contentView = new this.contentViewClass($.extend({
                onHide: $.proxy(this.hide, this)
            },
            this.contentViewOptions)), this.contentView.onHide = $.proxy(this.hide, this)),
            this.animationTime = t.animationTime || 300,
            this.isCanNotHide = t.isCanNotHide,
            this.doNotRemoveOnHide = t.doNotRemoveOnHide || !1,
            this.onShow = t.onShow || e,
            this.onHide = t.onHide || e,
            this.onFinishHide = t.onFinishHide || e,
            this.html = t.html
        },
        render: function(t) {
            return this.renderOptions = t || {},
            this.contentViewClass ? this.contentView.render(this.renderOptions) : this.html && this.nPopContainer.html(this.html),
            this
        },
        show: function() {
            return this.nBg.show().css({
                opacity: "1",
                "transition-property": "none"
            }),
            this.nPopContainer.show(),
            this.trigger("pop:show:before"),
            setTimeout($.proxy(function() {
                this.trigger("pop:show:after"),
                this.nPopContainer.show().css("visibility", "visible"),
                this._doShow && this._doShow(),
                this.onShow()
            },
            this), 200),
            this
        },
        hide: function(t) {
            t = t || {};
            var e = t.doNotRemove || this.doNotRemoveOnHide || !1;
            this._doHide && this._doHide(),
            this.trigger("pop:hide:before"),
            setTimeout($.proxy(function() {
                this.nBg.css({
                    opacity: 0,
                    "transition-property": "opacity"
                }),
                this.trigger("pop:hide:after"),
                setTimeout($.proxy(function() {
                    this.nBg.hide(),
                    this.nPopContainer.hide(),
                    e || this.destroy()
                },
                this), 200)
            },
            this), this.animationTime),
            this.onHide()
        },
        destroy: function() {
            return this.nPopContainer.remove(),
            this.nBg.remove(),
            this.contentView && this.contentView.remove(),
            this
        }
    })
}),
define("bower_components/pop/pop_forbid_scroll", ["require", "./pop"],
function(t) {
    return window.zenjs = window.zenjs || {},
    t("./pop").extend({
        init: function(t) {
            this._super(t),
            t.doNotForbidScroll || (this.on("pop:show:before", $.proxy(this.onBeforePopShow, this)), this.on("pop:show:after", $.proxy(this.onAfterPopShow, this)), this.on("pop:hide:after", $.proxy(this.onAfterPopHide, this)))
        },
        onBeforePopShow: function() {
            this.top = this._window.scrollTop()
        },
        onAfterPopShow: function() {
            this._window.scrollTop(0),
            this.startShow()
        },
        onAfterPopHide: function() {
            var t, e = function(n) {
                if (t !== this._window.scrollTop() && n > 0) return this._window.scrollTop(t),
                void setTimeout($.proxy(e, this, n - 1));
                setTimeout($.proxy(this.onFinishHide, this), 50)
            };
            return function() {
                this.startHide(),
                t = this.top,
                this._window.scrollTop(t),
                $.proxy(e, this)(2),
                setTimeout($.proxy(function() {
                    window.zenjs.popList.length < 1 && $("html").css("position", this.htmlPosition)
                },
                this), 200)
            }
        } (),
        startShow: function() {
            var t = window.zenjs.popList;
            if (t || (t = window.zenjs.popList = []), 0 === t.length) {
                var e = $("body"),
                n = $("html");
                this.htmlPosition = n.css("position"),
                n.css("position", "relative"),
                this.bodyCss = (e.prop("style") || {}).cssText,
                this.htmlCss = (n.prop("style") || {}).cssText,
                $("body,html").css({
                    overflow: "hidden",
                    height: this._window.height(),
                    "padding-bottom": 0,
                    "margin-bottom": 0
                })
            }
            t.indexOf(this) < 0 && t.push(this)
        },
        startHide: function() {
            var t = window.zenjs.popList,
            e = t.indexOf(this);
            e > -1 && t.splice(e, 1),
            t.length < 1 && ($("html").attr("style", this.htmlCss || ""), $("body").attr("style", this.bodyCss || ""))
        }
    })
}),
define("bower_components/pop/popout", ["require", "./pop_forbid_scroll"],
function(t) {
    return t("./pop_forbid_scroll").extend({
        init: function(t) {
            t = t || {},
            this._super(t),
            this.css = $.extend({
                transition: "opacity ease " + this.animationTime + "ms",
                top: "50%",
                left: "50%",
                "-webkit-transform": "translate3d(-50%, -50%, 0)",
                transform: "translateY(-50%, -50%, 0)"
            },
            t.css || {}),
            this.nPopContainer.css(this.css)
        },
        _doShow: function() {
            $(".js-popout-close").click($.proxy(function(t) {
                this.hide()
            },
            this)),
            this.nPopContainer.css("opacity", 1),
            this.nPopContainer.show()
        },
        _doHide: function(t) {
            this.nPopContainer.css({
                opacity: 0
            })
        }
    })
}),
define("bower_components/pop/popout_box", ["require", "./popout"],
function(t) {
    var e = function() {};
    return t("./popout").extend({
        init: function(t) {
            this._super(t),
            this._onOKClicked = t.onOKClicked || e,
            this._onCancelClicked = t.onCancelClicked || e,
            this.preventHideOnOkClicked = t.preventHideOnOkClicked || !1,
            this.width = t.width,
            this.setEventListener()
        },
        setEventListener: function() {
            this.nPopContainer.on("click", ".js-ok", $.proxy(this.onOKClicked, this)),
            this.nPopContainer.on("click", ".js-cancel", $.proxy(this.onCancelClicked, this))
        },
        _doShow: function() {
            this.boxCss = {
                "border-radius": "4px",
                background: "white",
                width: this.width || "270px",
                padding: "15px"
            },
            this.nPopContainer.css(this.boxCss).addClass("popout-box"),
            this._super()
        },
        _doHide: function(t) {
            this._super()
        },
        onOKClicked: function(t) {
            this._onOKClicked(t),
            !this.preventHideOnOkClicked && this.hide()
        },
        onCancelClicked: function(t) {
            this._onCancelClicked(t),
            this.hide()
        }
    })
}),
define("bower_components/pop/popout_box_ios", ["require", "./popout_box"],
function(t) {
    return t("./popout_box").extend({
        _doShow: function() {
            this.boxCss = {
                "border-radius": "4px",
                background: "white",
                width: this.width || "290px",
                padding: "15px 0 0"
            },
            this.nPopContainer.css(this.boxCss).addClass("popout-box-ios"),
            $(".js-popout-close").click($.proxy(function(t) {
                this.hide()
            },
            this)),
            this.nPopContainer.css("opacity", 1),
            this.nPopContainer.show()
        }
    })
}),
define("bower_components/aes/aes", ["require", "exports", "module"],
function(t, e, n) {
    var i = i ||
    function(t, e) {
        var n = {},
        i = n.lib = {},
        o = function() {},
        s = i.Base = {
            extend: function(t) {
                o.prototype = this;
                var e = new o;
                return t && e.mixIn(t),
                e.hasOwnProperty("init") || (e.init = function() {
                    e.$super.init.apply(this, arguments)
                }),
                e.init.prototype = e,
                e.$super = this,
                e
            },
            create: function() {
                var t = this.extend();
                return t.init.apply(t, arguments),
                t
            },
            init: function() {},
            mixIn: function(t) {
                for (var e in t) t.hasOwnProperty(e) && (this[e] = t[e]);
                t.hasOwnProperty("toString") && (this.toString = t.toString)
            },
            clone: function() {
                return this.init.prototype.extend(this)
            }
        },
        r = i.WordArray = s.extend({
            init: function(t, e) {
                t = this.words = t || [],
                this.sigBytes = void 0 != e ? e: 4 * t.length
            },
            toString: function(t) {
                return (t || c).stringify(this)
            },
            concat: function(t) {
                var e = this.words,
                n = t.words,
                i = this.sigBytes;
                if (t = t.sigBytes, this.clamp(), i % 4) for (var o = 0; o < t; o++) e[i + o >>> 2] |= (n[o >>> 2] >>> 24 - o % 4 * 8 & 255) << 24 - (i + o) % 4 * 8;
                else if (65535 < n.length) for (o = 0; o < t; o += 4) e[i + o >>> 2] = n[o >>> 2];
                else e.push.apply(e, n);
                return this.sigBytes += t,
                this
            },
            clamp: function() {
                var e = this.words,
                n = this.sigBytes;
                e[n >>> 2] &= 4294967295 << 32 - n % 4 * 8,
                e.length = t.ceil(n / 4)
            },
            clone: function() {
                var t = s.clone.call(this);
                return t.words = this.words.slice(0),
                t
            },
            random: function(e) {
                for (var n = [], i = 0; i < e; i += 4) n.push(4294967296 * t.random() | 0);
                return new r.init(n, e)
            }
        }),
        a = n.enc = {},
        c = a.Hex = {
            stringify: function(t) {
                var e = t.words;
                t = t.sigBytes;
                for (var n = [], i = 0; i < t; i++) {
                    var o = e[i >>> 2] >>> 24 - i % 4 * 8 & 255;
                    n.push((o >>> 4).toString(16)),
                    n.push((15 & o).toString(16))
                }
                return n.join("")
            },
            parse: function(t) {
                for (var e = t.length,
                n = [], i = 0; i < e; i += 2) n[i >>> 3] |= parseInt(t.substr(i, 2), 16) << 24 - i % 8 * 4;
                return new r.init(n, e / 2)
            }
        },
        d = a.Latin1 = {
            stringify: function(t) {
                var e = t.words;
                t = t.sigBytes;
                for (var n = [], i = 0; i < t; i++) n.push(String.fromCharCode(e[i >>> 2] >>> 24 - i % 4 * 8 & 255));
                return n.join("")
            },
            parse: function(t) {
                for (var e = t.length,
                n = [], i = 0; i < e; i++) n[i >>> 2] |= (255 & t.charCodeAt(i)) << 24 - i % 4 * 8;
                return new r.init(n, e)
            }
        },
        p = a.Utf8 = {
            stringify: function(t) {
                try {
                    return decodeURIComponent(escape(d.stringify(t)))
                } catch(t) {
                    throw Error("Malformed UTF-8 data")
                }
            },
            parse: function(t) {
                return d.parse(unescape(encodeURIComponent(t)))
            }
        },
        l = i.BufferedBlockAlgorithm = s.extend({
            reset: function() {
                this._data = new r.init,
                this._nDataBytes = 0
            },
            _append: function(t) {
                "string" == typeof t && (t = p.parse(t)),
                this._data.concat(t),
                this._nDataBytes += t.sigBytes
            },
            _process: function(e) {
                var n = this._data,
                i = n.words,
                o = n.sigBytes,
                s = this.blockSize,
                a = o / (4 * s),
                a = e ? t.ceil(a) : t.max((0 | a) - this._minBufferSize, 0);
                if (e = a * s, o = t.min(4 * e, o), e) {
                    for (var c = 0; c < e; c += s) this._doProcessBlock(i, c);
                    c = i.splice(0, e),
                    n.sigBytes -= o
                }
                return new r.init(c, o)
            },
            clone: function() {
                var t = s.clone.call(this);
                return t._data = this._data.clone(),
                t
            },
            _minBufferSize: 0
        });
        i.Hasher = l.extend({
            cfg: s.extend(),
            init: function(t) {
                this.cfg = this.cfg.extend(t),
                this.reset()
            },
            reset: function() {
                l.reset.call(this),
                this._doReset()
            },
            update: function(t) {
                return this._append(t),
                this._process(),
                this
            },
            finalize: function(t) {
                return t && this._append(t),
                this._doFinalize()
            },
            blockSize: 16,
            _createHelper: function(t) {
                return function(e, n) {
                    return new t.init(n).finalize(e)
                }
            },
            _createHmacHelper: function(t) {
                return function(e, n) {
                    return new u.HMAC.init(t, n).finalize(e)
                }
            }
        });
        var u = n.algo = {};
        return n
    } (Math); !
    function() {
        var t = i,
        e = t.lib.WordArray;
        t.enc.Base64 = {
            stringify: function(t) {
                var e = t.words,
                n = t.sigBytes,
                i = this._map;
                t.clamp(),
                t = [];
                for (var o = 0; o < n; o += 3) for (var s = (e[o >>> 2] >>> 24 - o % 4 * 8 & 255) << 16 | (e[o + 1 >>> 2] >>> 24 - (o + 1) % 4 * 8 & 255) << 8 | e[o + 2 >>> 2] >>> 24 - (o + 2) % 4 * 8 & 255, r = 0; 4 > r && o + .75 * r < n; r++) t.push(i.charAt(s >>> 6 * (3 - r) & 63));
                if (e = i.charAt(64)) for (; t.length % 4;) t.push(e);
                return t.join("")
            },
            parse: function(t) {
                var n = t.length,
                i = this._map,
                o = i.charAt(64);
                o && -1 != (o = t.indexOf(o)) && (n = o);
                for (var o = [], s = 0, r = 0; r < n; r++) if (r % 4) {
                    var a = i.indexOf(t.charAt(r - 1)) << r % 4 * 2,
                    c = i.indexOf(t.charAt(r)) >>> 6 - r % 4 * 2;
                    o[s >>> 2] |= (a | c) << 24 - s % 4 * 8,
                    s++
                }
                return e.create(o, s)
            },
            _map: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/="
        }
    } (),
    function(t) {
        function e(t, e, n, i, o, s, r) {
            return ((t = t + (e & n | ~e & i) + o + r) << s | t >>> 32 - s) + e
        }
        function n(t, e, n, i, o, s, r) {
            return ((t = t + (e & i | n & ~i) + o + r) << s | t >>> 32 - s) + e
        }
        function o(t, e, n, i, o, s, r) {
            return ((t = t + (e ^ n ^ i) + o + r) << s | t >>> 32 - s) + e
        }
        function s(t, e, n, i, o, s, r) {
            return ((t = t + (n ^ (e | ~i)) + o + r) << s | t >>> 32 - s) + e
        }
        for (var r = i,
        a = r.lib,
        c = a.WordArray,
        d = a.Hasher,
        a = r.algo,
        p = [], l = 0; 64 > l; l++) p[l] = 4294967296 * t.abs(t.sin(l + 1)) | 0;
        a = a.MD5 = d.extend({
            _doReset: function() {
                this._hash = new c.init([1732584193, 4023233417, 2562383102, 271733878])
            },
            _doProcessBlock: function(t, i) {
                for (var r = 0; 16 > r; r++) {
                    var a = i + r,
                    c = t[a];
                    t[a] = 16711935 & (c << 8 | c >>> 24) | 4278255360 & (c << 24 | c >>> 8)
                }
                var r = this._hash.words,
                a = t[i + 0],
                c = t[i + 1],
                d = t[i + 2],
                l = t[i + 3],
                u = t[i + 4],
                h = t[i + 5],
                f = t[i + 6],
                y = t[i + 7],
                m = t[i + 8],
                w = t[i + 9],
                g = t[i + 10],
                _ = t[i + 11],
                v = t[i + 12],
                b = t[i + 13],
                x = t[i + 14],
                k = t[i + 15],
                P = r[0],
                C = r[1],
                S = r[2],
                z = r[3],
                P = e(P, C, S, z, a, 7, p[0]),
                z = e(z, P, C, S, c, 12, p[1]),
                S = e(S, z, P, C, d, 17, p[2]),
                C = e(C, S, z, P, l, 22, p[3]),
                P = e(P, C, S, z, u, 7, p[4]),
                z = e(z, P, C, S, h, 12, p[5]),
                S = e(S, z, P, C, f, 17, p[6]),
                C = e(C, S, z, P, y, 22, p[7]),
                P = e(P, C, S, z, m, 7, p[8]),
                z = e(z, P, C, S, w, 12, p[9]),
                S = e(S, z, P, C, g, 17, p[10]),
                C = e(C, S, z, P, _, 22, p[11]),
                P = e(P, C, S, z, v, 7, p[12]),
                z = e(z, P, C, S, b, 12, p[13]),
                S = e(S, z, P, C, x, 17, p[14]),
                C = e(C, S, z, P, k, 22, p[15]),
                P = n(P, C, S, z, c, 5, p[16]),
                z = n(z, P, C, S, f, 9, p[17]),
                S = n(S, z, P, C, _, 14, p[18]),
                C = n(C, S, z, P, a, 20, p[19]),
                P = n(P, C, S, z, h, 5, p[20]),
                z = n(z, P, C, S, g, 9, p[21]),
                S = n(S, z, P, C, k, 14, p[22]),
                C = n(C, S, z, P, u, 20, p[23]),
                P = n(P, C, S, z, w, 5, p[24]),
                z = n(z, P, C, S, x, 9, p[25]),
                S = n(S, z, P, C, l, 14, p[26]),
                C = n(C, S, z, P, m, 20, p[27]),
                P = n(P, C, S, z, b, 5, p[28]),
                z = n(z, P, C, S, d, 9, p[29]),
                S = n(S, z, P, C, y, 14, p[30]),
                C = n(C, S, z, P, v, 20, p[31]),
                P = o(P, C, S, z, h, 4, p[32]),
                z = o(z, P, C, S, m, 11, p[33]),
                S = o(S, z, P, C, _, 16, p[34]),
                C = o(C, S, z, P, x, 23, p[35]),
                P = o(P, C, S, z, c, 4, p[36]),
                z = o(z, P, C, S, u, 11, p[37]),
                S = o(S, z, P, C, y, 16, p[38]),
                C = o(C, S, z, P, g, 23, p[39]),
                P = o(P, C, S, z, b, 4, p[40]),
                z = o(z, P, C, S, a, 11, p[41]),
                S = o(S, z, P, C, l, 16, p[42]),
                C = o(C, S, z, P, f, 23, p[43]),
                P = o(P, C, S, z, w, 4, p[44]),
                z = o(z, P, C, S, v, 11, p[45]),
                S = o(S, z, P, C, k, 16, p[46]),
                C = o(C, S, z, P, d, 23, p[47]),
                P = s(P, C, S, z, a, 6, p[48]),
                z = s(z, P, C, S, y, 10, p[49]),
                S = s(S, z, P, C, x, 15, p[50]),
                C = s(C, S, z, P, h, 21, p[51]),
                P = s(P, C, S, z, v, 6, p[52]),
                z = s(z, P, C, S, l, 10, p[53]),
                S = s(S, z, P, C, g, 15, p[54]),
                C = s(C, S, z, P, c, 21, p[55]),
                P = s(P, C, S, z, m, 6, p[56]),
                z = s(z, P, C, S, k, 10, p[57]),
                S = s(S, z, P, C, f, 15, p[58]),
                C = s(C, S, z, P, b, 21, p[59]),
                P = s(P, C, S, z, u, 6, p[60]),
                z = s(z, P, C, S, _, 10, p[61]),
                S = s(S, z, P, C, d, 15, p[62]),
                C = s(C, S, z, P, w, 21, p[63]);
                r[0] = r[0] + P | 0,
                r[1] = r[1] + C | 0,
                r[2] = r[2] + S | 0,
                r[3] = r[3] + z | 0
            },
            _doFinalize: function() {
                var e = this._data,
                n = e.words,
                i = 8 * this._nDataBytes,
                o = 8 * e.sigBytes;
                n[o >>> 5] |= 128 << 24 - o % 32;
                var s = t.floor(i / 4294967296);
                for (n[15 + (o + 64 >>> 9 << 4)] = 16711935 & (s << 8 | s >>> 24) | 4278255360 & (s << 24 | s >>> 8), n[14 + (o + 64 >>> 9 << 4)] = 16711935 & (i << 8 | i >>> 24) | 4278255360 & (i << 24 | i >>> 8), e.sigBytes = 4 * (n.length + 1), this._process(), e = this._hash, n = e.words, i = 0; 4 > i; i++) o = n[i],
                n[i] = 16711935 & (o << 8 | o >>> 24) | 4278255360 & (o << 24 | o >>> 8);
                return e
            },
            clone: function() {
                var t = d.clone.call(this);
                return t._hash = this._hash.clone(),
                t
            }
        }),
        r.MD5 = d._createHelper(a),
        r.HmacMD5 = d._createHmacHelper(a)
    } (Math),
    function() {
        var t = i,
        e = t.lib,
        n = e.Base,
        o = e.WordArray,
        e = t.algo,
        s = e.EvpKDF = n.extend({
            cfg: n.extend({
                keySize: 4,
                hasher: e.MD5,
                iterations: 1
            }),
            init: function(t) {
                this.cfg = this.cfg.extend(t)
            },
            compute: function(t, e) {
                for (var n = this.cfg,
                i = n.hasher.create(), s = o.create(), r = s.words, a = n.keySize, n = n.iterations; r.length < a;) {
                    c && i.update(c);
                    var c = i.update(t).finalize(e);
                    i.reset();
                    for (var d = 1; d < n; d++) c = i.finalize(c),
                    i.reset();
                    s.concat(c)
                }
                return s.sigBytes = 4 * a,
                s
            }
        });
        t.EvpKDF = function(t, e, n) {
            return s.create(n).compute(t, e)
        }
    } (),
    i.lib.Cipher ||
    function(t) {
        var e = i,
        n = e.lib,
        o = n.Base,
        s = n.WordArray,
        r = n.BufferedBlockAlgorithm,
        a = e.enc.Base64,
        c = e.algo.EvpKDF,
        d = n.Cipher = r.extend({
            cfg: o.extend(),
            createEncryptor: function(t, e) {
                return this.create(this._ENC_XFORM_MODE, t, e)
            },
            createDecryptor: function(t, e) {
                return this.create(this._DEC_XFORM_MODE, t, e)
            },
            init: function(t, e, n) {
                this.cfg = this.cfg.extend(n),
                this._xformMode = t,
                this._key = e,
                this.reset()
            },
            reset: function() {
                r.reset.call(this),
                this._doReset()
            },
            process: function(t) {
                return this._append(t),
                this._process()
            },
            finalize: function(t) {
                return t && this._append(t),
                this._doFinalize()
            },
            keySize: 4,
            ivSize: 4,
            _ENC_XFORM_MODE: 1,
            _DEC_XFORM_MODE: 2,
            _createHelper: function(t) {
                return {
                    encrypt: function(e, n, i) {
                        return ("string" == typeof n ? y: f).encrypt(t, e, n, i)
                    },
                    decrypt: function(e, n, i) {
                        return ("string" == typeof n ? y: f).decrypt(t, e, n, i)
                    }
                }
            }
        });
        n.StreamCipher = d.extend({
            _doFinalize: function() {
                return this._process(!0)
            },
            blockSize: 1
        });
        var p = e.mode = {},
        l = function(t, e, n) {
            var i = this._iv;
            i ? this._iv = void 0 : i = this._prevBlock;
            for (var o = 0; o < n; o++) t[e + o] ^= i[o]
        },
        u = (n.BlockCipherMode = o.extend({
            createEncryptor: function(t, e) {
                return this.Encryptor.create(t, e)
            },
            createDecryptor: function(t, e) {
                return this.Decryptor.create(t, e)
            },
            init: function(t, e) {
                this._cipher = t,
                this._iv = e
            }
        })).extend();
        u.Encryptor = u.extend({
            processBlock: function(t, e) {
                var n = this._cipher,
                i = n.blockSize;
                l.call(this, t, e, i),
                n.encryptBlock(t, e),
                this._prevBlock = t.slice(e, e + i)
            }
        }),
        u.Decryptor = u.extend({
            processBlock: function(t, e) {
                var n = this._cipher,
                i = n.blockSize,
                o = t.slice(e, e + i);
                n.decryptBlock(t, e),
                l.call(this, t, e, i),
                this._prevBlock = o
            }
        }),
        p = p.CBC = u,
        u = (e.pad = {}).Pkcs7 = {
            pad: function(t, e) {
                for (var n = 4 * e,
                n = n - t.sigBytes % n,
                i = n << 24 | n << 16 | n << 8 | n,
                o = [], r = 0; r < n; r += 4) o.push(i);
                n = s.create(o, n),
                t.concat(n)
            },
            unpad: function(t) {
                t.sigBytes -= 255 & t.words[t.sigBytes - 1 >>> 2]
            }
        },
        n.BlockCipher = d.extend({
            cfg: d.cfg.extend({
                mode: p,
                padding: u
            }),
            reset: function() {
                d.reset.call(this);
                var t = this.cfg,
                e = t.iv,
                t = t.mode;
                if (this._xformMode == this._ENC_XFORM_MODE) var n = t.createEncryptor;
                else n = t.createDecryptor,
                this._minBufferSize = 1;
                this._mode = n.call(t, this, e && e.words)
            },
            _doProcessBlock: function(t, e) {
                this._mode.processBlock(t, e)
            },
            _doFinalize: function() {
                var t = this.cfg.padding;
                if (this._xformMode == this._ENC_XFORM_MODE) {
                    t.pad(this._data, this.blockSize);
                    var e = this._process(!0)
                } else e = this._process(!0),
                t.unpad(e);
                return e
            },
            blockSize: 4
        });
        var h = n.CipherParams = o.extend({
            init: function(t) {
                this.mixIn(t)
            },
            toString: function(t) {
                return (t || this.formatter).stringify(this)
            }
        }),
        p = (e.format = {}).OpenSSL = {
            stringify: function(t) {
                var e = t.ciphertext;
                return t = t.salt,
                (t ? s.create([1398893684, 1701076831]).concat(t).concat(e) : e).toString(a)
            },
            parse: function(t) {
                t = a.parse(t);
                var e = t.words;
                if (1398893684 == e[0] && 1701076831 == e[1]) {
                    var n = s.create(e.slice(2, 4));
                    e.splice(0, 4),
                    t.sigBytes -= 16
                }
                return h.create({
                    ciphertext: t,
                    salt: n
                })
            }
        },
        f = n.SerializableCipher = o.extend({
            cfg: o.extend({
                format: p
            }),
            encrypt: function(t, e, n, i) {
                i = this.cfg.extend(i);
                var o = t.createEncryptor(n, i);
                return e = o.finalize(e),
                o = o.cfg,
                h.create({
                    ciphertext: e,
                    key: n,
                    iv: o.iv,
                    algorithm: t,
                    mode: o.mode,
                    padding: o.padding,
                    blockSize: t.blockSize,
                    formatter: i.format
                })
            },
            decrypt: function(t, e, n, i) {
                return i = this.cfg.extend(i),
                e = this._parse(e, i.format),
                t.createDecryptor(n, i).finalize(e.ciphertext)
            },
            _parse: function(t, e) {
                return "string" == typeof t ? e.parse(t, this) : t
            }
        }),
        e = (e.kdf = {}).OpenSSL = {
            execute: function(t, e, n, i) {
                return i || (i = s.random(8)),
                t = c.create({
                    keySize: e + n
                }).compute(t, i),
                n = s.create(t.words.slice(e), 4 * n),
                t.sigBytes = 4 * e,
                h.create({
                    key: t,
                    iv: n,
                    salt: i
                })
            }
        },
        y = n.PasswordBasedCipher = f.extend({
            cfg: f.cfg.extend({
                kdf: e
            }),
            encrypt: function(t, e, n, i) {
                return i = this.cfg.extend(i),
                n = i.kdf.execute(n, t.keySize, t.ivSize),
                i.iv = n.iv,
                t = f.encrypt.call(this, t, e, n.key, i),
                t.mixIn(n),
                t
            },
            decrypt: function(t, e, n, i) {
                return i = this.cfg.extend(i),
                e = this._parse(e, i.format),
                n = i.kdf.execute(n, t.keySize, t.ivSize, e.salt),
                i.iv = n.iv,
                f.decrypt.call(this, t, e, n.key, i)
            }
        })
    } (),
    function() {
        for (var t = i,
        e = t.lib.BlockCipher,
        n = t.algo,
        o = [], s = [], r = [], a = [], c = [], d = [], p = [], l = [], u = [], h = [], f = [], y = 0; 256 > y; y++) f[y] = 128 > y ? y << 1 : y << 1 ^ 283;
        for (var m = 0,
        w = 0,
        y = 0; 256 > y; y++) {
            var g = w ^ w << 1 ^ w << 2 ^ w << 3 ^ w << 4,
            g = g >>> 8 ^ 255 & g ^ 99;
            o[m] = g,
            s[g] = m;
            var _ = f[m],
            v = f[_],
            b = f[v],
            x = 257 * f[g] ^ 16843008 * g;
            r[m] = x << 24 | x >>> 8,
            a[m] = x << 16 | x >>> 16,
            c[m] = x << 8 | x >>> 24,
            d[m] = x,
            x = 16843009 * b ^ 65537 * v ^ 257 * _ ^ 16843008 * m,
            p[g] = x << 24 | x >>> 8,
            l[g] = x << 16 | x >>> 16,
            u[g] = x << 8 | x >>> 24,
            h[g] = x,
            m ? (m = _ ^ f[f[f[b ^ _]]], w ^= f[f[w]]) : m = w = 1
        }
        var k = [0, 1, 2, 4, 8, 16, 32, 64, 128, 27, 54],
        n = n.AES = e.extend({
            _doReset: function() {
                for (var t = this._key,
                e = t.words,
                n = t.sigBytes / 4,
                t = 4 * ((this._nRounds = n + 6) + 1), i = this._keySchedule = [], s = 0; s < t; s++) if (s < n) i[s] = e[s];
                else {
                    var r = i[s - 1];
                    s % n ? 6 < n && 4 == s % n && (r = o[r >>> 24] << 24 | o[r >>> 16 & 255] << 16 | o[r >>> 8 & 255] << 8 | o[255 & r]) : (r = r << 8 | r >>> 24, r = o[r >>> 24] << 24 | o[r >>> 16 & 255] << 16 | o[r >>> 8 & 255] << 8 | o[255 & r], r ^= k[s / n | 0] << 24),
                    i[s] = i[s - n] ^ r
                }
                for (e = this._invKeySchedule = [], n = 0; n < t; n++) s = t - n,
                r = n % 4 ? i[s] : i[s - 4],
                e[n] = 4 > n || 4 >= s ? r: p[o[r >>> 24]] ^ l[o[r >>> 16 & 255]] ^ u[o[r >>> 8 & 255]] ^ h[o[255 & r]]
            },
            encryptBlock: function(t, e) {
                this._doCryptBlock(t, e, this._keySchedule, r, a, c, d, o)
            },
            decryptBlock: function(t, e) {
                var n = t[e + 1];
                t[e + 1] = t[e + 3],
                t[e + 3] = n,
                this._doCryptBlock(t, e, this._invKeySchedule, p, l, u, h, s),
                n = t[e + 1],
                t[e + 1] = t[e + 3],
                t[e + 3] = n
            },
            _doCryptBlock: function(t, e, n, i, o, s, r, a) {
                for (var c = this._nRounds,
                d = t[e] ^ n[0], p = t[e + 1] ^ n[1], l = t[e + 2] ^ n[2], u = t[e + 3] ^ n[3], h = 4, f = 1; f < c; f++) var y = i[d >>> 24] ^ o[p >>> 16 & 255] ^ s[l >>> 8 & 255] ^ r[255 & u] ^ n[h++],
                m = i[p >>> 24] ^ o[l >>> 16 & 255] ^ s[u >>> 8 & 255] ^ r[255 & d] ^ n[h++],
                w = i[l >>> 24] ^ o[u >>> 16 & 255] ^ s[d >>> 8 & 255] ^ r[255 & p] ^ n[h++],
                u = i[u >>> 24] ^ o[d >>> 16 & 255] ^ s[p >>> 8 & 255] ^ r[255 & l] ^ n[h++],
                d = y,
                p = m,
                l = w;
                y = (a[d >>> 24] << 24 | a[p >>> 16 & 255] << 16 | a[l >>> 8 & 255] << 8 | a[255 & u]) ^ n[h++],
                m = (a[p >>> 24] << 24 | a[l >>> 16 & 255] << 16 | a[u >>> 8 & 255] << 8 | a[255 & d]) ^ n[h++],
                w = (a[l >>> 24] << 24 | a[u >>> 16 & 255] << 16 | a[d >>> 8 & 255] << 8 | a[255 & p]) ^ n[h++],
                u = (a[u >>> 24] << 24 | a[d >>> 16 & 255] << 16 | a[p >>> 8 & 255] << 8 | a[255 & l]) ^ n[h++],
                t[e] = y,
                t[e + 1] = m,
                t[e + 2] = w,
                t[e + 3] = u
            },
            keySize: 8
        });
        t.AES = e._createHelper(n)
    } (),
    i.pad.Iso10126 = {
        pad: function(t, e) {
            var n = 4 * e,
            n = n - t.sigBytes % n;
            t.concat(i.lib.WordArray.random(n - 1)).concat(i.lib.WordArray.create([n << 24], 1))
        },
        unpad: function(t) {
            t.sigBytes -= 255 & t.words[t.sigBytes - 1 >>> 2]
        }
    },
    n.exports = i
}),
define("bower_components/aes/main", ["require", "./aes"],
function(t) {
    var e = t("./aes"),
    n = e.enc.Utf8.parse("youzan.com.aesiv"),
    i = e.enc.Utf8.parse("youzan.com._key_");
    return {
        encrypt: function(t) {
            return t = e.enc.Utf8.parse(t),
            e.AES.encrypt(t, i, {
                mode: e.mode.CBC,
                padding: e.pad.Iso10126,
                iv: n
            }).toString()
        }
    }
}),
define("text!wap/components/pay/templates/password.html", [],
function() {
    return '<div class="header">\n\t安全验证\n</div>\n<span class="js-cancel close"></span>\n<div class="popout-content content password-content">\n\t<p class="font-size-12">为保证支付账户安全，请输入手机账户<%= account %>的登录密码</p>\n\t<p class="relative">\n\t\t<input type="password" class="js-password password" placeholder="请输入账户密码"/>\n\t\t<span class="clear-input js-clear-input hide"></span>\n\t</p>\n</div>\n<div class="action-container">\n\t<button class="js-ok btn btn-green btn-block">付款</button>\n</div>\n<div class="bottom-tips font-size-12" style="margin-top:12px">\n\t<span class="c-orange">不知道密码？请</span><a href="<%- changePwdUrl %>" class="js-change-pwd c-blue">点此找回密码</a>\n</div>'
}),
define("wap/components/pay/views/password", ["require", "text!wap/components/pay/templates/password.html"],
function(t) {
    var e = function() {},
    n = t("text!wap/components/pay/templates/password.html"),
    i = _.template(n);
    return Backbone.View.extend({
        initialize: function(t) {
            this.account = t.account || "",
            this.onConfirm = t.onConfirm || e
        },
        events: {
            "input .js-password": "onInputChange",
            "click .js-ok": "onOKClicked",
            "click .js-clear-input": "onClearClicked"
        },
        render: function() {
            return this.$el.html(i({
                account: function(t) {
                    return t = t.toString(),
                    t.slice(0, 3) + "****" + t.slice( - 3)
                } (this.account),
                changePwdUrl: window._global.wap_url.wap + "/buyer/auth/changePassword?redirect_uri=" + encodeURIComponent(window.location.href)
            })),
            this.nPassword = this.$(".js-password"),
            this.nClear = this.$(".js-clear-input"),
            this.nPassword.on("focus",
            function(t) {
                var e = $(this),
                n = $(window),
                i = n.scrollTop(),
                o = e.offset().top; (o - i < 0 || o - i > 100) && setTimeout(function() {
                    var t = o - 90;
                    n.scrollTop(t)
                },
                400)
            }),
            this
        },
        onInputChange: function() { (this.nPassword.val() || "").length > 0 ? this.nClear.removeClass("hide") : this.nClear.addClass("hide")
        },
        onClearClicked: function() {
            this.nPassword.val(""),
            this.onInputChange()
        },
        onOKClicked: function(t) {
            var e = this.nPassword.val();
            if (!e) return void motify.log("请输入密码");
            this.onConfirm({
                password: e
            })
        },
        startLoading: function() {
            this.$(".js-ok").addClass("btn-pay-loading")
        },
        stopLoading: function() {
            this.$(".js-ok").removeClass("btn-pay-loading")
        }
    })
}),
define("text!wap/components/pay/templates/wapwxpay.html", [],
function() {
    return '<div class="header center font-size-16 c-black">微信支付确认</div>\n<div class="content font-size-14 c-gray-dark">若你已付款成功，请点击“已完成支付”；若付款时遇到问题，可选择“其他支付方式”</div>\n<div class="action-container">\n    <button class="btn btn-l c-black js-cancel">其他支付方式</button>\n    <button class="btn btn-l c-green js-ok">已完成支付</button>\n</div>\n'
}),
define("text!bower_components/pop/pop_confirm/box.html", [],
function() {
    return '<% if (title) { %>\n<h1 class="center font-size-16"><%= title %></h1>\n<% } %>\n<div class="confirm-content font-size-14<% if (title) { %> c-gray-dark<% } %>" style="line-height: 20px; padding: 5px 5px 10px;">\n    <%= content %>\n</div>\n<hr style="margin: 9px -15px 10px;" />\n<div class="btn-2-1">\n    <p class="js-cancel center font-size-16" style="padding-top: 5px;"><%= cancelBtn %></p>\n</div><div class="btn-2-1">\n    <p class="js-ok center c-green font-size-16" style="padding-top: 5px;"><%= okBtn %></p>\n</div>'
}),
define("zenjs/util/template", ["require", "jquery"],
function(t) {
    var e = t("jquery"),
    n = function() {
        var t = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#x27;"
        },
        e = ["&", "<", ">", '"', "'"],
        n = new RegExp("[" + e.join("") + "]", "g");
        return function(e) {
            return null == e ? "": ("" + e).replace(n,
            function(e) {
                return t[e]
            })
        }
    } (),
    i = {
        evaluate: /<%([\s\S]+?)%>/g,
        interpolate: /<%=([\s\S]+?)%>/g,
        escape: /<%-([\s\S]+?)%>/g
    },
    o = /(.)^/,
    s = {
        "'": "'",
        "\\": "\\",
        "\r": "r",
        "\n": "n",
        "\t": "t",
        "\u2028": "u2028",
        "\u2029": "u2029"
    },
    r = /\\|'|\r|\n|\t|\u2028|\u2029/g;
    return function(t, a, c) {
        var d;
        c = e.extend({},
        i, c);
        var p = new RegExp([(c.escape || o).source, (c.interpolate || o).source, (c.evaluate || o).source].join("|") + "|$", "g"),
        l = 0,
        u = "__p+='";
        t.replace(p,
        function(e, n, i, o, a) {
            return u += t.slice(l, a).replace(r,
            function(t) {
                return "\\" + s[t]
            }),
            n && (u += "'+\n((__t=(" + n + "))==null?'':escapeFunc(__t))+\n'"),
            i && (u += "'+\n((__t=(" + i + "))==null?'':__t)+\n'"),
            o && (u += "';\n" + o + "\n__p+='"),
            l = a + e.length,
            e
        }),
        u += "';\n",
        c.variable || (u = "with(obj||{}){\n" + u + "}\n"),
        u = "var __t,__p='',__j=Array.prototype.join,print=function(){__p+=__j.call(arguments,'');};\n" + u + "return __p;\n";
        try {
            d = new Function(c.variable || "obj", "escapeFunc", u)
        } catch(t) {
            throw t.source = u,
            t
        }
        if (a) return d(a, n);
        var h = function(t) {
            return d.call(this, t, n)
        };
        return h.source = "function(" + (c.variable || "obj") + "){\n" + u + "}",
        h
    }
}),
define("bower_components/pop/pop_confirm/popout_confirm", ["require", "../popout_box", "text!../pop_confirm/box.html", "zenjs/util/template"],
function(t) {
    var e = t("../popout_box"),
    n = t("text!../pop_confirm/box.html"),
    i = t("zenjs/util/template"),
    o = i(n),
    s = e.extend({
        init: function(t) {
            this._super(t),
            this.isCanNotHide = !0,
            this.title = t.title || "",
            this.content = t.content || "",
            this.okBtn = t.okBtn || "确定",
            this.cancelBtn = t.cancelBtn || "取消"
        },
        render: function(t) {
            return this.html || (this.html = o({
                title: this.title,
                content: this.content,
                okBtn: this.okBtn,
                cancelBtn: this.cancelBtn
            })),
            this._super(t),
            this.nPopContainer.addClass("popout-confirm"),
            this
        }
    }),
    r = null;
    return {
        show: function(t, e) {
            e = e || {},
            r && this.hide(),
            e.content = t,
            r = new s(e).render().show()
        },
        hide: function() {
            r && (r.hide(), r = null)
        }
    }
}),
define("wap/components/pay/views/weixin-ready", [],
function() {
    "use strict";
    function t(t, i) {
        e || (e = !0, motify.log("微信支付初始化中，请稍候"), document.addEventListener("WeixinJSBridgeReady",
        function() {
            e && (t(), clearTimeout(n), motify.clear(), e = !1)
        },
        !1), n = setTimeout(function() {
            motify.clear(),
            e = !1,
            i()
        },
        1e4))
    }
    var e = !1,
    n = 0;
    return function(e, n) {
        "object" == typeof WeixinJSBridge && "function" == typeof window.WeixinJSBridge.invoke ? e() : t(e, n)
    }
}),
define("wap/components/pay/views/pay_action", ["require", "jquery", "underscore", "backbone", "vendor/zepto/form", "zenjs/util/ua", "bower_components/pop/popout_box", "bower_components/pop/popout_box_ios", "bower_components/aes/main", "wap/components/pay/views/password", "text!../templates/wapwxpay.html", "bower_components/pop/pop_confirm/popout_confirm", "./weixin-ready"],
function(t) {
    "use strict";
    var e = t("jquery"),
    n = t("underscore"),
    i = t("backbone");
    t("vendor/zepto/form");
    var o = t("zenjs/util/ua"),
    s = t("bower_components/pop/popout_box"),
    r = t("bower_components/pop/popout_box_ios"),
    a = t("bower_components/aes/main"),
    c = t("wap/components/pay/views/password"),
    d = t("text!../templates/wapwxpay.html"),
    p = t("bower_components/pop/pop_confirm/popout_confirm"),
    l = t("./weixin-ready"),
    u = function() {},
    h = function() {
        location.reload()
    };
    return i.View.extend({
        initialize: function(t) {
            this.payUrl = t.payUrl,
            this.kdt_id = t.kdt_id,
            this.order_no = t.order_no,
            this.account = t.account || "",
            this.wxPayResultUrl = t.wxPayResultUrl,
            this.getPayDataExtr = t.getPayDataExtr || u,
            this.onPayOrderCreated = t.onPayOrderCreated || u,
            this.beforeWxPayRender = t.beforeWxPayRender || u,
            this.onPayError = t.onPayError || u,
            this.onWxPayError = t.onWxPayError || u,
            this.wxWapPaySuccess = t.wxWapPaySuccess || h,
            this.wxWapPayFailure = t.wxWapPayFailure || h,
            this.className = t.className || ""
        },
        initFuncs: function(t) {
            this.doBeforeSync = t.doBeforeSync || u,
            this.doAfterSync = t.doAfterSync || u
        },
        doPayAction: function(t) {
            if (this.isPayProcessing) return void motify.log("支付处理中，请稍候再试");
            this.isPayProcessing = !0,
            this.initFuncs(t);
            var n = t.payType || "",
            i = t.payName || "",
            o = "",
            s = this.getPayDataExtr(n);
            if (!s) {
                this.isPayProcessing = !1;
                var r = new e.Deferred;
                return r.reject(),
                r.promise()
            }
            if ("codpay" === n) {
                var a = this;
                return o = "你正在选择货到付款，下单后由商家发货，送货上门并收款。",
                "到店付款" === i && (o = "你正在选择到店付款，下单后请自行到店领取并付款。"),
                p.show(o, {
                    title: "下单提醒",
                    onOKClicked: function() {
                        a.doPay(n, s)
                    }
                }),
                void(this.isPayProcessing = !1)
            }
            if ("ecard" === n || "prepaid_card" === n) return void this.getPasswordBeforePay(n, s);
            this.doPay(n, s)
        },
        getPasswordBeforePay: function(t, e) {
            var i = new s({
                contentViewClass: c,
                contentViewOptions: {
                    account: this.account,
                    onConfirm: n(function(n) {
                        var o = n.password;
                        if (o = a.encrypt(o), this.isPayProcessing) return void motify.log("支付处理中，请稍候再试");
                        this.isPayProcessing = !0,
                        i.contentView.startLoading(),
                        this.doPay(t, e, o).always(function() {
                            i.contentView.stopLoading()
                        })
                    }).bind(this)
                },
                isCanNotHide: !0,
                className: "pay-popout",
                transparent: ".6",
                preventHideOnOkClicked: !0
            });
            i.render().show(),
            this.isPayProcessing = !1
        },
        doPay: function(t, e, i) {
            var o = {
                order_no: this.order_no,
                kdt_id: this.kdt_id,
                buy_way: t
            };
            return window._global._model && (o.type = window._global._model.type || "", o.code = window._global._model.code || ""),
            i && (o.password = i),
            this.submitPay(n.extend(e, o), t)
        },
        submitPay: function(t, i) {
            var o = this;
            return e._ajax({
                url: "payUrl",
                type: "POST",
                dataType: "json",
                timeout: 15e3,
                data: t,
                cache: !1,
                beforeSend: function() {
                    o.doBeforeSync()
                },
                success: function(e) {
                    var a = e.code;
                    switch (o.isPayProcessing = !1, a) {
                    case 0:
                        o.onPayOrderCreated(t);
                        var c = e.data.pay_data,
                        l = e.data.redirect_url,
                        u = e.data.pay_return_url,
                        h = e.data.pay_return_data;
                        switch (i) {
                        case "wxapppay":
                            o.doFinishWxAppPay(i, c, h);
                            break;
                        case "wxpay":
                            o.doFinishWxPay(i, c, l, u, h);
                            break;
                        case "couponpay":
                        case "presentpay":
                            window.SafeLink.redirect({
                                url:
                                c.submit_url,
                                kdtId: window._global.kdt_id || window._global.kdtId
                            });
                            break;
                        case "ecard":
                        case "prepaid_card":
                            o.doFinishInnerPay(i, c, l);
                            break;
                        case "wxwappay":
                            return o.wapPayPopout = new r({
                                html: d,
                                width: "290px",
                                className: "pay-popout" + (o.className ? " " + o.className: ""),
                                onOKClicked: function() {
                                    o.wxWapPaySuccess(e, o)
                                },
                                onCancelClicked: function() {
                                    o.wxWapPayFailure(e, o)
                                }
                            }).render().show(),
                            void window.SafeLink.redirect({
                                url: c.deeplink,
                                kdtId: window._global.kdt_id || window._global.kdtId
                            });
                        default:
                            if (!c || !c.submit_url) return void motify.log("支付过程出错，请联系客服！");
                            o.doFinishOtherPay(i, c)
                        }
                        break;
                    case 11022:
                    case 11023:
                        window.wxReady && window.wxReady(function() {
                            window.WeixinJSBridge && window.WeixinJSBridge.invoke("closeWindow", {})
                        });
                        break;
                    case 11010:
                        p.show(e.msg, {
                            title: "改价提醒",
                            onOKClicked: function() {
                                t.accept_price = 1,
                                o.submitPay(t, i)
                            },
                            onCancelClicked: function() {
                                motify.log("支付已取消", 0),
                                window.location.reload()
                            }
                        });
                        break;
                    case 11012:
                    case 11024:
                    case 11026:
                    case 11027:
                        motify.log("正在跳转...");
                        var f = "wxpay" != i ? window._global.url.trade + "/trade/order/result?order_no=" + o.order_no + "&kdt_id=" + o.kdt_id + "#wechat_webview_type=1": o.wxPayResultUrl;
                        window.SafeLink.redirect({
                            url: f,
                            kdtId: window._global.kdt_id || window._global.kdtId
                        });
                        break;
                    case 21e3:
                        window.location.reload();
                        break;
                    case 90001:
                        var y = e.data.item_url,
                        m = n.template(['<div class="content">矮油，动作太慢了，已被抢光了</div>', '<div class="action-container">', '<div class="btn-2-1"><button class="btn btn-l btn-white js-ok">放弃</button></div>', '<div class="btn-2-1"><a href="<%= data.buyUrl %>" class="btn btn-l btn-orange-dark">我要买</a></div>', "</div>"].join(""));
                        o.errorPopout || (o.errorPopout = new s({
                            doNotRemoveOnHide: !0,
                            className: "pay-popout",
                            html: m({
                                data: {
                                    buyUrl: y
                                }
                            })
                        }).render()),
                        o.errorPopout.show();
                        break;
                    default:
                        motify.log(e.msg)
                    }
                    0 !== a && o.onPayError(a, e.msg || "", e),
                    0 !== a && "couponpay" == i && window.location.reload()
                },
                error: function(t, e, n) {
                    o.isPayProcessing = !1,
                    motify.log("生成支付单失败。")
                },
                complete: function(t, e) {
                    o.isPayProcessing = !1,
                    o.doAfterSync()
                }
            })
        },
        doFinishOtherPay: function(t, i) {
            if (!this.isSubmitting) {
                this.isSubmitting = !0;
                var o = '<form method="post" action="' + i.submit_url + '" style="display: none;">';
                delete i.submit_url,
                n(i).map(function(t, e) {
                    o += '<input type="hidden" name="' + e + '" value="' + t + '" />'
                }),
                o += "</form>";
                var s = e(o);
                e(document.body).append(s),
                s.submit(),
                this.isSubmitting = !1
            }
        },
        doFinishWxPay: function(t, i, s, r, a) {
            if (this.wxpayed) return void motify.log("支付数据处理中，请勿重复操作");
            this.wxpayed = !0,
            "string" == typeof i && (i = e.parseJSON(i)),
            l(n(function() {
                this.beforeWxPayRender(),
                window.WeixinJSBridge.invoke("getBrandWCPayRequest", i, n(function(t) {
                    var n = t.err_msg;
                    if (this.wxpayed = !1, "get_brand_wcpay_request:ok" === n) motify.log("支付成功，正在处理订单...", 0),
                    e._ajax({
                        url: r,
                        type: "POST",
                        dataType: "json",
                        timeout: 15e3,
                        data: a,
                        cache: !1,
                        success: function(t) {
                            window.SafeLink.redirect({
                                url: s,
                                kdtId: window._global.kdt_id || window._global.kdtId
                            })
                        }
                    });
                    else if ("get_brand_wcpay_request:cancel" === n) o.isIOS() || this.onWxPayError({
                        payReturnData: a,
                        model: this.model
                    });
                    else {
                        this.onWxPayError({
                            payReturnData: a,
                            model: this.model
                        }),
                        e._ajax({
                            url: "/v2/pay/api/recordwxjsfailmsg.json",
                            type: "POST",
                            dataType: "json",
                            data: e.extend({
                                wxpay_fail_order_url: s
                            },
                            t)
                        });
                        var i = "wxPayFailed",
                        c = t.err_desc || "";
                        c.indexOf("跨公众号") > -1 && (i = "wxPayAcrossApp"),
                        window.StackTraceLogger && window.StackTraceLogger.paasLog({
                            appName: "jserror-wap",
                            logIndex: "trade",
                            name: i,
                            message: JSON.stringify(t),
                            level: "info"
                        })
                    }
                }).bind(this))
            }).bind(this), n(function() {
                this.wxpayed = !1,
                motify.log("微信支付初始化失败，请稍后再试");
                var t = 0;
                window._initialRunTime && window._initialRunTime.getTime && (t = (new Date).getTime() - window._initialRunTime.getTime()),
                window.StackTraceLogger && window.StackTraceLogger.paasLog({
                    appName: "jserror-wap",
                    logIndex: "trade",
                    name: "WxPayBridgeInitFail",
                    message: "微信支付初始化失败, time=" + t,
                    level: "info"
                })
            }).bind(this))
        },
        doFinishWxAppPay: function() {
            function t(t, e) {
                e || (e = "weixin"),
                o.isIOS() ? (t = encodeURIComponent(t), document.location.hash = "#func=appWXPay&params=" + t) : o.isAndroid() && window.android && window.android.appWXPay(t)
            }
            return function(e, n, i) {
                o.isWxd() && o.getPlatformVersion() >= "1.5.0" ? window.YouzanJSBridge && window.YouzanJSBridge.doCall("doAction", {
                    action: "appWXPay",
                    kdt_id: n.kdt_id,
                    order_no: i.order_no,
                    inner_order_no: n.order_no
                }) : (window.Logger && window.Logger.log({
                    fm: "click",
                    title: "app_wx_pay"
                }), t("kdt_id=" + n.kdt_id + "&order_no=" + n.order_no), window.YouzanJSBridge && window.YouzanJSBridge.doCall("appWXPay", {
                    kdt_id: n.kdt_id,
                    order_no: n.order_no
                }))
            }
        } (),
        doFinishInnerPay: function(t, n, i) {
            n = n || {};
            var o = n.pay_return_url,
            s = n.pay_return_data;
            e._ajax({
                url: o,
                type: "POST",
                dataType: "json",
                timeout: 15e3,
                data: s,
                cache: !1,
                complete: function() {
                    window.SafeLink.redirect({
                        url: i,
                        kdtId: window._global.kdt_id || window._global.kdtId
                    })
                }
            })
        }
    })
}),
define("wap/trade/peerpay/pay/store/store", ["require", "underscore", "zenjs/util/money", "zenjs/util/money_cent", "wap/components/pay/views/pay_action"],
function(t) {
    var e = t("underscore"),
    n = t("zenjs/util/money"),
    i = t("zenjs/util/money_cent"),
    o = t("wap/components/pay/views/pay_action");
    return {
        model: {
            id: "",
            type: "",
            status_name: "",
            fans_nickname: "",
            fans_avatar: "",
            invite_message: "",
            order: {},
            pay_process: {},
            price_paid: 0,
            pay_messages: ""
        },
        init: function(t) {
            this.model = t || {}
        },
        getIsSinglePeerpay: function(t) {
            return "onepay" === this.model.peerpay_type
        },
        getInviteId: function() {
            return this.model.id || ""
        },
        getType: function() {
            return this.model.type || 0
        },
        getState: function() {
            return this.model.status_name || "ERROR"
        },
        getPeerpayStat: function() {
            var t = this.model.order.real_pay,
            e = i(this.model.price_paid),
            o = n.minus(t, e);
            return {
                total: t,
                paidTotal: e,
                totalPeer: this.model.paidList.total,
                leftTotal: o
            }
        },
        getDefaultPayMessage: function() {
            return this.model.pay_messages ? this.model.pay_messages.message || "": ""
        },
        getPayerNickname: function() {
            return window._global.fans_info ? window._global.fans_info.fans_nickname || "": ""
        },
        getPayData: function() {
            var t = n.toCent(this.getPeerpayStat().total) || 0,
            e = {
                id: this.model.invite_no,
                sign: this.model.invite_sign,
                name: "",
                comment: this.getDefaultPayMessage(),
                pay: t,
                isMyself: this.getType()
            };
            return this.model.payData ? this.model.payData: e
        },
        setPayData: function(t) {
            this.model.payData = {
                id: this.model.invite_no,
                sign: this.model.invite_sign,
                name: t.name,
                comment: t.message,
                pay: t.amount,
                isMyself: this.getType()
            }
        },
        validatePayData: function() {
            var t = this.getPayData(),
            e = n.toCent(this.getPeerpayStat().leftTotal);
            return !! (t && t.id && t.sign) && !(isNaN(i(t.pay)) || t.pay <= 0 || t.pay > e)
        },
        doPay: function(t) {
            var n = function() {};
            if (!this.checkWxPay()) return void window.motify.log("当前商家未开通微信支付，请联系商家开通后重试。");
            if (this.validatePayData()) {
                new o({
                    payUrl: "https://cashier.youzan.com/v2/pay/peerpay/pay.json",
                    kdt_id: this.model.kdt_id,
                    order_no: this.model.order_no,
                    getPayDataExtr: e(this.getPayData).bind(this),
                    onPayError: this.onPayError,
                    onWxPayError: t.showWxScanPay || n
                }).doPayAction({
                    payType: window._global.payWays[0].code,
                    payName: window._global.payWays[0].name
                })
            } else this.onPayError()
        },
        onPayError: function(t, e, n) {
            e && "" !== e || (e = "支付出错了，请稍后重试"),
            window.motify.log(e)
        },
        checkWxPay: function() {
            return ! (!window._global.payWays || 0 === window._global.payWays.length) && "wxpay" === window._global.payWays[0].code
        }
    }
}),
define("text!wap/trade/peerpay/pay/templates/pay_form.html", [],
function() {
    return '<div class="pay-form">\n  <div class="pay-amount-block block block-list">\n    <div class="block-item">\n      <p class="amount-block-title font-size-12 c-gray-darker">代付金额</p>\n      <div class="amount-details" v-if="store.getIsSinglePeerpay()">\n        <span class="c-black font-size-30">¥{{leftTotal}}</span>\n      </div>\n      <div class="amount-details" v-if="!store.getIsSinglePeerpay()">\n        <span class="c-black font-size-30">¥</span>\n        <input type="number" ref="amountInput" class="item-text text-input font-size-30"\n             :placeholder="amountPlaceholderText" v-model="amount" @focus="onAmountFocus" @blur="onAmountBlur">\n        <button class="tag btn-change-amount" @click="onChangeAmountClick">修改</button>\n      </div>\n    </div>\n  </div>\n  <div class="pay-msg-block block block-list">\n    <div class="block-item">\n      <p class="amount-block-title font-size-12 c-gray-darker">给好友留言</p>\n      <textarea class="msg-textarea js-msg-textarea c-black font-size-14"\n            maxlength="30" v-model="message" :placeholder="message" rows="1"></textarea>\n    </div>\n  </div>\n  <div class="pay-nickname-block block block-list ">\n    <div class="block-item">\n      <span class="c-black font-size-14">显示昵称</span>\n      <div class="switch-wrapper">\n        <vm-switch class="switch-nickname" :checked="showNickname" :onChange="onNicknameClick" />\n      </div>\n    </div>\n    <div class="block-item" v-show="showNickname">\n      <span class="c-black font-size-14">编辑昵称：</span>\n      <input type="text" class="item-text text-input font-size-14" placeholder="请输入昵称"\n      v-model="nickname">\n    </div>\n  </div>\n  <div class="nickname-help-text" v-show="!showNickname">\n    <p class="font-size-12 c-gray">代付成功后付款人列表为匿名状态哦。</p>\n  </div>\n  <button type="button" v-if="isWeixin" class="btn bottom-btn btn-block btn-wx-green peerpay-pay-btn" @click="onPayClick">微信支付</button>\n  <button v-else type="button js-footer-auto-ele" class="btn bottom-btn btn-block btn-disabled">代付功能仅支持微信浏览器</button>\n</div>\n'
}),
define("text!wap/trade/peerpay/pay/utils/switch.html", [],
function() {
    return '<div class="switch" :class="[switchState]" @click="toggleState">\n\t<div class="switch-node"></div>\n</div>\n'
}),
define("wap/trade/peerpay/pay/utils/switch", ["require", "text!./switch.html"],
function(t) {
    "use strict";
    return {
        template: t("text!./switch.html"),
        props: {
            checked: {
                type: Boolean,
            default:
                !1
            },
            disabled: {
                type: Boolean,
            default:
                !1
            },
            loading: {
                type: Boolean,
            default:
                !1
            },
            onChange: {
                type: Function,
            default:
                function() {}
            }
        },
        computed: {
            switchState: function() {
                return this.disabled ? "disabled": this.loading ? "loading": this.checked ? "on": "off"
            }
        },
        methods: {
            setState: function(t) {
                this.checked = t
            },
            toggleState: function() {
                this.onChange(!this.checked)
            },
            getState: function() {
                return this.checked
            }
        }
    }
}),
define("wap/components/autosize_textarea/index", ["require", "exports", "module", "jquery", "underscore"],
function(t, e, n) {
    var i = t("jquery"),
    o = t("underscore"),
    s = function(t) {
        var e = {
            "overflow-x": "hidden"
        };
        if (o.extend(this, t), !this.el) throw new Error("缺少el参数");
        this.$el = this.el instanceof i ? this.el: i(this.el),
        this.el = this.$el[0],
        this.style && (e = o.extend(e, this.style)),
        this.$el.css(e),
        this.init()
    };
    o.extend(s.prototype, {
        init: function() {
            var t = this.$el,
            e = parseInt(t.css("padding-bottom"), 10) + parseInt(t.css("padding-top"), 10) || 0;
            i.trim(t.val()).length && t.height(t[0].scrollHeight - e),
            this.$el.on("input",
            function() {
                i(this).height(0).height(this.scrollHeight - e)
            })
        }
    }),
    n.exports = s
}),
define("wap/trade/peerpay/pay/views/pay_form", ["require", "zenjs/util/money", "text!../templates/pay_form.html", "bower_components/pop/popout_box", "zenjs/util/ua", "../utils/switch", "wap/components/autosize_textarea/index"],
function(t) {
    "use strict";
    var e = t("zenjs/util/money"),
    n = t("text!../templates/pay_form.html"),
    i = (t("bower_components/pop/popout_box"), t("zenjs/util/ua")),
    o = t("../utils/switch"),
    s = t("wap/components/autosize_textarea/index");
    Vue.component("vm-pay", {
        template: n,
        props: ["store"],
        components: {
            "vm-switch": o
        },
        data: function() {
            var t = this.store.getPayerNickname(),
            e = !1;
            return "" !== t && (e = !0),
            {
                showNickname: e,
                amount: this.store.getPeerpayStat().leftTotal,
                nickname: t,
                message: this.store.getDefaultPayMessage(),
                leftTotal: this.store.getPeerpayStat().leftTotal,
                readyToPay: !0
            }
        },
        computed: {
            title: function() {
                return 1 === this.store.getType() ? "自己先垫点": "立即支持"
            },
            amountPlaceholderText: function() {
                return "最高" + this.leftTotal
            },
            isWeixin: function() {
                return i.isWeixin()
            }
        },
        methods: {
            onChangeAmountClick: function() {
                this.amount = "",
                this.$refs.amountInput.focus(),
                this.readyToPay = !1
            },
            onNicknameClick: function(t, e) {
                this.showNickname = t
            },
            onAmountBlur: function() {
                var t = this;
                this.validateAmount() ? this.readyToPay = !0 : window.setTimeout(function() {
                    t.readyToPay = !0
                },
                100)
            },
            onAmountFocus: function() {
                this.amount = "",
                this.readyToPay = !1
            },
            validateAmount: function() {
                var t = e.toCent(this.amount) || 0,
                n = e.toCent(this.leftTotal) || 0;
                if (!this.store.getIsSinglePeerpay()) {
                    if (!this.amount || isNaN(this.amount)) return this.amount = this.leftTotal,
                    window.motify.log("请输入正确的筹款金额哦"),
                    !1;
                    if (t > n) return this.amount = this.leftTotal,
                    window.motify.log("你输入的金额大于筹款尾款喽"),
                    !1;
                    if (t < 1) return this.amount = this.leftTotal,
                    window.motify.log("最少支付 ¥0.01哦"),
                    !1
                }
                return this.updatePayData(),
                !0
            },
            validateName: function() {
                if (this.showNickname) {
                    if (0 === this.nickname.length) return window.motify.log("请输入昵称"),
                    !1;
                    if (this.nickname.length > 8) return window.motify.log("昵称不能超过8个字哦"),
                    !1;
                    if (this.message.length > 30) return window.motify.log("留言不能超过30个字哦"),
                    !1
                }
                return this.updatePayData(),
                !0
            },
            updatePayData: function() {
                var t = {
                    name: this.showNickname ? this.nickname: "",
                    message: this.message,
                    amount: e.toCent(this.amount) || 0
                };
                this.store.setPayData(t)
            },
            onPayClick: function() {
                this.readyToPay && this.validateName() && this.validateAmount() && this.store.doPay({
                    showWxScanPay: this.showWxScanPay
                })
            },
            showWxScanPay: function(t) {
                window.motify.log("当前支付方式不可用，请换一种支付方式重新支付")
            },
            onSubmit: function(t) {
                t.preventDefault()
            }
        },
        mounted: function() {
            new s({
                el: $(".js-msg-textarea")[0]
            })
        }
    })
}),
require(["wap/trade/peerpay/pay/store/store", "wap/trade/peerpay/pay/views/pay_form"],
function(t) {
    "use strict";
    t.init(window._global.invite);
    var e = new Vue({
        el: "#pay-container",
        data: {
            store: t
        }
    }),
    n = $(".js-head-block-bg");
    return n.click(function() {
        n.toggleClass("on-top")
    }),
    e
}),
define("main",
function() {});
