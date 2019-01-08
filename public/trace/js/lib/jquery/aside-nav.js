$(function() {
    /*var asideNav = function(el) {
        this.el = el || {};
        var asideNav_link = this.el.find('.asideNav_link');
        var submenu = this.el.find('.submenu li');
        asideNav_link.on('click', {el: this.el}, this.dropdown);
        submenu.on('click',{el: this.el}, this.choosed);
    };

    asideNav.prototype.dropdown = function(e) {
        var $this = $(this),
            $next = $this.next();
        if($next.is(':hidden')){
            $('.asideNav_link').next().slideUp();
            $next.slideDown();
        }else{
            $next.slideUp();
            $('*').removeClass('asideChoose borderLeft');
            return
        }
        $('*').removeClass('asideChoose borderLeft');
        $this.parent().addClass('asideChoose').addClass('borderLeft');
    };
    asideNav.prototype.choosed = function(e) {
        $('*').removeClass('asideChoose borderLeft');
        $(this).addClass('asideChoose');
        $(this).parent().parent().addClass('borderLeft');
    };
    setTimeout(function () {
        var asideNavObj = new asideNav($('#asideNav'));
    },500);*/

    setTimeout(function () {
        //一、外层点击事件
        $('#asideNav').on("click" , ".asideNav_link" , function (e) {
            //1.给自身设置class
            if($(this).parent()[0].tagName == "A"){
                $(this).parent().parent().siblings().removeClass("asideChoose");
                $(this).parent().parent().siblings().removeClass("borderLeft");
                $(this).parent().parent().addClass("asideChoose");
                $(this).parent().parent().addClass("borderLeft");
            }else{
                $(this).parent().siblings().removeClass("asideChoose");
                $(this).parent().siblings().removeClass("borderLeft");
                $(this).parent().addClass("asideChoose");
                $(this).parent().addClass("borderLeft");
            }

            //2.设置子元素显示
            if($(this).parent()[0].tagName != "A"){
                $(this).parent().siblings().each(function (i,o) {
                    $(o).find(".submenu").slideUp();
                });
                $(this).next().slideDown();
            }else{
                $(this).parent().parent().siblings().each(function (i,o) {
                    $(o).find(".submenu").slideUp();
                })
            }

        });

        //二、给每个子元素设置点击事件
        $("#asideNav").on("click" , ".submenu li a" , function (e) {
            $(".submenu li a").removeClass("submenu-a");
            $(this).addClass("submenu-a");
        });
        //三、根据高度设置左侧滚动
        if(window.screen.height <= 910 ){
			
            //$("#aside").css("overflow","scroll");
        }


    },800);

});