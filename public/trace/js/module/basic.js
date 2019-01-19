/**
 * Created by Administrator on 2017/9/22.
 */
var prompt_goods_release, ms_left, ms_page, ms_prompt_reset;
avalon.component('ms-left', {
    template: '<div class="aside-content">' +
    '<div class="personalCenter">' +
    '<div class="personalCenter_wapper">' +
    '<img src="/img/noneInfor.png" alt="none" width="67" height="67" />' +
    '<p class="nowrap" style="width:100%;">{{ userInfo.username }}</p>' +
    '</div>' +
    '</div>' +
    '<ul id="asideNav" class="asideNav" :if="menu.length>0">' +
    '<!--ms-for: (key,item) in menu-->' +
    '<li :if="item.activity" class="asideChoose borderLeft">' +
    '<div class="asideNav_link" :if="item.son.length>0">' +
    '<img ms-attr="{src:\'/img/icon//\'+(key+1)+\'.png\'}" alt="">' +
    '<p>{{ item.name }}<span :if="item.url.indexOf(\'check\')>0 && userInfo.check>0">{{ userInfo.check }}</span></p>' +
    '</div>' +
    '<a :click="redict(item.url)" :if="item.son.length==0">' +
    '<div class="asideNav_link">' +
    '<img ms-attr="{src:\'/img/icon//\'+(key+1)+\'.png\'}" alt="">' +
    '<p>{{ item.name }}<span :if="item.url.indexOf(\'check\')>0 && userInfo.check>0">{{ userInfo.check }}</span></p>' +
    '</div></a>' +
    '<ul class="submenu" style="display: block;">' +
    '<!--ms-for: item2 in @item.son-->' +
    '<li :if="item2.activity">' +
    '<a :click="redict(item2.url)" class="submenu-a">{{ item2.name }}</a>' +
    '</li>' +
    '<li :if="!item2.activity">' +
    '<a :click="redict(item2.url)">{{ item2.name }}</a>' +
    '</li>' +
    '<!--ms-for: item in @menu-->' +
    '</ul>' +
    '</li>' +
    '<li :if="!item.activity">' +
    '<div class="asideNav_link" :if="item.son.length>0">' +
    '<img ms-attr="{src:\'/img/icon//\'+(key+1)+\'.png\'}" alt="">' +
    '<p>{{ item.name }}<span :if="item.url.indexOf(\'check\')>0 && userInfo.check>0">{{ userInfo.check }}</span></p>' +
    '</div>' +
    '<a :click="redict(item.url)" :if="item.son.length==0">' +
    '<div class="asideNav_link">' +
    '<img ms-attr="{src:\'/img/icon//\'+(key+1)+\'.png\'}" alt="">' +
    '<p>{{ item.name }}<span :if="item.url.indexOf(\'check\')>0 && userInfo.check>0">{{ userInfo.check }}</span></p>' +
    '</div>' +
    '</a>' +
    '<ul class="submenu">' +
    '<li ms-for="item2 in @item.son">' +
    '<a :click="redict(item2.url)">{{ item2.name }}</a>' +
    '</li>' +
    '</ul>' +
    '</li>' +
    '<!--ms-for-end:-->' +
    '</ul>' +
    '</div>',
    defaults: {
        menu: [],
        userInfo: {
            username: "",
            avatar: "",
            role: "",
            menu: [],
            check:0
        },
        userAlready: false,

        onReady: function (e) {
            ms_left = e.vmodel;
            var that = this;
            if (sessionStorage.username) {
                that.userInfo.username = sessionStorage.username;
                that.userInfo.role = sessionStorage.role;
                that.menu.clear();
                that.menu.pushArray(JSON.parse(sessionStorage.menu));
                this.setMenuActivity();
            } else {
                that.userInfo = {
                    username: "登陆中...",
                    avatar: "/img/noneInfor.png"
                };
                setTimeout(function () {
                    that.getLoginDetail();
                }, 300);
            }

            $.ajaxGet({
                url: "/trace/trace/list?pageSize=10&pageNo=1&rid="
            }, function (data) {
                that.userInfo.check = data.count;
            });

        },
        getLoginDetail: function () {
            var that = this;
            $.ajaxGet({
                url: "/trace/login/detail"
            }, function (data) {
                console.log(data);
                that.userInfo = data.detail;
                that.userAlready = true;
                that.menu.clear();
                that.menu.pushArray(data.menu);

                sessionStorage.username = data.detail.username;
                sessionStorage.role = data.detail.role;
                sessionStorage.menu = JSON.stringify(data.menu);
                that.setMenuActivity();
            });
        },
        setMenuActivity: function () {
            var url = window.location.href;
            for (var i in this.menu) {
                this.menu[i].activity = 0;
                for (var j in this.menu[i].son) {
                    this.menu[i].activity = 0;
                    this.menu[i].son[j].activity = 0;
                }
            }
            for (var i in this.menu) {
                if (this.menu[i].url && url.indexOf(this.menu[i].url) > -1) {
                    this.menu[i].activity = 1;
                }
                for (var j in this.menu[i].son) {
                    if (this.menu[i].son[j].url && url.indexOf(this.menu[i].son[j].url) > -1) {
                        this.menu[i].activity = 2;
                        this.menu[i].son[j].activity = true;
                    }
                }
            }
        },
        redict: function (url) {
            redict(url);
        }
    }
});

avalon.component('ms-header', {
    // template: '<div id="content-header"><div class="header"><div class="header_left clear"><img src="/img/icon//header_logo.png" alt=""><p>积生活商务中心3.0</p></div><div class="header_right clear" ms-controller="message"><div :if="@unread" class="unread">{{@unread.length}}</div><div class="message"><img src="/img/icon//message_icon.png" alt=""><a href="//t/news/messageCenter.html"><p style="margin-left: 18px;">消息中心<span>({{@message.length}})</span></p></a></div><div class="loginOut"><img src="/img/icon//loginOut_icon.png" alt=""><a ms-click="@unLogin()"><p>退出</p></a></div></div></div></div>',
    template: '<div id="content-header">' +
    '<div class="header"><div class="header_left clear">' +
    '<p style="margin-left: 20px">重点工业项目</p></div><div class="header_right clear" ms-controller="message">' +
    '<a ms-click="reset()" style="float:left;">' +
    '<img src="/trace/img/message_icon.png" alt="">' +
    '<p style="margin-right: 18px;font-size:13px;">修改密码</p>' +
    '</a>' +
    '<a ms-click="@unLogin()" style="float:left;">' +
    '<div class="loginOut" style="height:40px;">' +
    '<img src="/trace/img/loginOut_icon.png" alt="">' +
    '<p style="font-size:13px;margin-right:15px;">退出</p>' +
    '</div>' +
    '</a>' +
    '</div>' +
    '</div>' +
    '</div>',
    defaults: {
        unLogin: function () {
            $.ajaxPost({
                url: "/trace/login/un"
            }, function (data) {
                setCookie("login_union", 0, -3600);
                setCookie("avatar", 0, -3600);
                sessionStorage.username = "";
                window.location.href = "/t/login/login.html?loginUrl=" + encodeURIComponent(location.href);
            });
        },
        reset: function () {
            ms_prompt_reset.isshow = true;
        }
    }
});

avalon.component('ms-footer', {
    template: '<div id="footer"><div class="footer"><p style="margin-left: -10%;">溧阳市智慧城市建设运营有限公司版权所有</p></div></div>',
    defaults: {}
});


avalon.component('ms-prompt-login', {
    template: '<div class="shade" :if="isshow"><div class="main"><div class="title">提示</div><div class="text">您还未登陆，请先跳转到登陆页。</div><div class="btns"><span :click="sure()">确定</span><span :click="isshow=!isshow">取消</span></div></div></div>',
    defaults: {
        isshow: false,
        sure: function () {
            window.location.href = "/t/login/login.html?loginUrl=" + encodeURIComponent(location.href);
        },
        onReady: function () {
            var that = this;
            setInterval(function () {
                if (!getCookie("login_union")) {
                    that.isshow = true;
                }
            }, 3000);
        }
    }
});

avalon.component('ms-prompt-reset', {
    template: '<div class="shade" :if="isshow">' +
    '<div class="main">' +
    '<div class="title">修改密码</div>' +
    '<div style="margin-top: 20px;">' +
    '<div class="form-horizontal">' +
    '<div class="form-group"><label for="inputEmail3" class="col-sm-2 control-label">原密码</label>' +
    '<div class="col-sm-10"><input type="password" class="form-control" id="inputEmail3" placeholder="" :duplex="pwd1"></div></div>' +
    '<div class="form-group"><label for="inputPassword3" class="col-sm-2 control-label">新密码</label>' +
    '<div class="col-sm-10"><input type="password" class="form-control" id="inputPassword3" placeholder="" :duplex="pwd2"></div></div>' +
    '<div class="form-group"><label for="inputPassword3" class="col-sm-2 control-label">确认新密码</label>' +
    '<div class="col-sm-10"><input type="password" class="form-control" id="inputPassword3" placeholder="" :duplex="pwd3"></div></div>' +
    '<div class="form-group"><div class="col-sm-offset-2 col-sm-10">' +
    '<button type="submit" class="btn btn-default" :click="reset()">确定</button>' +
    '<button type="submit" class="btn btn-default" :click="isshow=false">取消</button>' +
    '</div></div></div>' +
    '</div>' +
    '</div>' +
    '</div>',
    defaults: {
        isshow: false,
        pwd1: "",
        pwd2: "",
        pwd3: "",
        sure: function () {
            window.location.href = "/t/login/login.html?loginUrl=" + encodeURIComponent(location.href);
        },

        reset: function () {
            var that = this;
            if (that.pwd3 != that.pwd2) {
                alert("前后密码输入不一致");
                return;
            }
            $.ajaxPost({
                url: "/trace/user/password",
                pwd1: that.pwd1,
                pwd2: that.pwd2
            }, function (data) {
                alert("密码修改成功");
                that.isshow = false;
            });
        },
        onInit: function (e) {
            ms_prompt_reset = e.vmodel
        }
    }
});

avalon.component('ms-prompt-goods-release', {
    template: '<div class="shade" :if="isshow"><div class="main" style="width: 500px;"><div class="title">请选择要发布的商品类型</div><div class="text"><a href="goodsRelease_lineDown.html"><div class="ms-prompt-botton"><p>优惠买单</p></div></a><a href="goodsRelease.html?goods_type=2"><div class="ms-prompt-botton"><p>代金券</p></div></a><a href="goodsRelease.html?goods_type=1"><div class="ms-prompt-botton"><p>团购</p></div></a></div><div class="btns"><span :click="isshow=!isshow">取消</span></div></div></div>',
    defaults: {
        isshow: false,
        sure: function () {
            window.location.href = "/t/login/login.html?loginUrl=" + encodeURIComponent(location.href);
        },
        onInit: function (e) {
            prompt_goods_release = e.vmodel
        },
    }
});

avalon.config({
    debug: false,
})