function timeAxis(params) {
    $(".flowChart").append('<div class="startNode"></div>');
    var colorArr = [];

    //给每个对象添加color属性，并赋给一个随机的颜色
    for (var i = 0; i < params.length; i++) {
        //params[i].color=getRandomColor();
        if(i == 0){
            params[i].color = "#1ac8b5";
        }else{
            params[i].color = "#ccc";
        }
    }
    for (var i = 0; i < params.length; i++) {

        //将所有相同value值的对象赋予相同的颜色
        /*        for(var j=0;j<params.length;j++){
         if(params[i].value==params[j].value){
         params[j].color=params[i].color
         }
         }*/

        $(".flowChart").append('<div class="oneNode ' + params[i].color + '" >' +
            '        <div class="solid"></div>' +
            '        <div class="check" onclick="showTrace(this,' + i + ')" style="background:' + params[i].color + ';border-color: ' + params[i].color + ' ">' + (i + 1) + '</div>' +
            '    </div>'
        );

    }
    $(".flowChart").append('<div class="oneNode ' + 1 + '" >' +
        '        <div class="solid"></div>' +
        '        <div class="tag-boder"></div>' +
        '    </div>'
    );

    $(".flowChart").append('<div class="endNode"></div>');
    $(".flowChart .oneNode").addClass('upNode');

    $('.dot').dotdotdot();

    var width = 100 / (params.length + 1);
    var oneNode = $(".oneNode"),
        solid = $(".solid");
    if(params.length == 1){
        oneNode.css("width", (width - 2) + "%");
    }else{
        oneNode.css("width", (width - 1) + "%");
    }
    var width_px = oneNode.width();
    $(".check").css("left", (width_px - 20) + "px")
    solid.css("width", width_px + "px");
    var left = solid.css("width");
    left = left.replace('px', '') / 2;
}

//获得随机颜色
function getRandomColor() {
    var c = '#';
    var cArray = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F'];
    for (var i = 0; i < 6; i++) {
        var cIndex = Math.round(Math.random() * 15);
        c += cArray[cIndex];
    }
    return c;
}
//处理颜色
var reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/;
String.prototype.colorRgba = function () {
    var sColor = this.toLowerCase();
    if (sColor && reg.test(sColor)) {
        if (sColor.length === 4) {
            var sColorNew = "#";
            for (var i = 1; i < 4; i += 1) {
                sColorNew += sColor.slice(i, i + 1).concat(sColor.slice(i, i + 1));
            }
            sColor = sColorNew;
        }
        //处理六位的颜色值
        var sColorChange = [];
        for (var i = 1; i < 7; i += 2) {
            sColorChange.push(parseInt("0x" + sColor.slice(i, i + 2)));
        }
        return "rgba(" + sColorChange.join(",") + ",0.8)";
    } else {
        return sColor;
    }
};

$(".flowChart").on("mouseover", ".check", function () {
    var t = $(this).next();
    $(this).parent().siblings().each(function () {
        $(this).find(".tag-boder").hide();
        $(this).find(".NodeDetail").hide();
    });
    var u = t.next();
    t.show();
    u.show();
});

