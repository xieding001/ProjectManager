!(function (w) {
    w.getCookie = function (name) {
        var strCookie = document.cookie;
        var arrCookie = strCookie.split("; ");
        for (var i = 0; i < arrCookie.length; i++) {
            var arr = arrCookie[i].split("=");
            if (arr[0] == name)return arr[1];
        }
        return "";
    };

    w.setCookie = function (k, v, t, p) {
        t = arguments[2] ? arguments[2] : 3600;
        p = arguments[3] ? arguments[3] : "/";
        var d = new Date();
        d.setTime(d.getTime() + t);
        var expires = "expires=" + d.toUTCString();
        document.cookie = k + "=" + v + "; " + expires + ";path=" + p;
    };

    w.getRequest = function () {
        var url = location.search; //获取url中"?"符后的字串
        var theRequest = {};
        if (url.indexOf("?") != -1) {
            var str = url.substr(1);
            strs = str.split("&");
            for (var i = 0; i < strs.length; i++) {
                theRequest[strs[i].split("=")[0]] = unescape(strs[i].split("=")[1]);
            }
        }
        return theRequest;
    };

    w.getResponse = function (data, cb) {
        if (data.error_code == 0) {
            typeof cb == "function" && cb()
        }
        else if (data.error_code == 20001) {
            //登陆错误
            if (!getCookie(window.location.href)) {
                setCookie(window.location.href, 1);
                //window.location.href = "/t/login/login.html?loginUrl="+encodeURIComponent(location.href);
            }
        } else {
            alert(data.error_msg);
        }
    };

    w.deepCopy = function (obj) {
        if (typeof obj != 'object') {
            return obj;
        }
        var newobj = {};
        for (var attr in obj) {
            newobj[attr] = deepCopy(obj[attr]);
        }
        return newobj;
    };
    w.xx = function (obj) {
        console.log(obj);
    };
    w.requestAnimationFrame = (function () {

        return window.requestAnimationFrame ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame ||
            function (callback) {
                window.setTimeout(callback, 6000 / 60);
            };
    })();
    w.redict = function (url) {
        window.location.href = url;
        url = document.location.protocol + "//" + location.host + url.replace('#', '');

        $("#content-left-url").remove();
        $(".content-left").append('<iframe id="content-left-url" src="' + url + '" frameborder="0" style="width: 100%" height="860px"></iframe>');
    };
    w.redict_iframe = function (url) {
        window.parent.location.href = url;
        url = document.location.protocol + "//" + location.host + url.replace('#', '');
        // window.location.href = url;
        var node = window.parent.document.getElementById("content-left-url");
        var pnode = node.parentNode;
        pnode.removeChild(node);
        $(pnode).append('<iframe id="content-left-url" src="' + url + '" frameborder="0" style="width: 100%" height="860px"></iframe>');

    };
    w.alert = function (e) {
        $("#msg").remove();
        $("body").append('<div id="msg"><div id="msg_top">信息<span class="msg_close">×</span></div><div id="msg_cont">' + e + '</div><div class="msg_close" id="msg_clear">确定</div></div>');
        $(".msg_close").click(function () {
            $("#msg").remove();
        });
    };
    w.jsonEmptyVerify = function (json, keys) {
        for(var i in keys){
            if(!json[keys[i].k]){
                alert(keys[i].v+"不能为空。");
                return 1;
            }
        }
        return 0;
    }
})(window);
