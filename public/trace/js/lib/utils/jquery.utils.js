$.extend({
    ajaxGet:function(obj , cb){
        var obj2 = deepCopy(obj);
        delete obj.url;
        $.ajax({
            type: "GET",
            url: obj2.url,
            data: obj,
            dataType: "json",
            success: function(data) {
                getResponse(data, function () {
                    cb(data.data);
                });
            }
        });
    },
    ajaxPost:function(obj , cb){
        var obj2 = deepCopy(obj);
        delete obj.url;
        $.ajax({
            type: "POST",
            url: obj2.url,
            data: obj,
            dataType: "json",
            success: function(data) {
                getResponse(data, function () {
                    cb(data);
                });
            }
        });
    },

    /**
     *  page:{
     *      pageNo:0,
     *      pageSize:10,
     *      pageNums:[],
     *      pageBegin:0,
     *      pageEnd:0,
     *      count:0,
     *  },
     *
     */
    page:function (page) {
        var no = page.no,
            begin = page.begin,
            end = page.end,
            count = page.count,
            size = page.size,
            doing = page.doing,
            nums = [];

        end = Math.ceil(count/size);

        if(no > 9){
            if((no+5) > end){
                //begin = no+10-end;
                begin = end - 9;
                if(begin < 1){
                    begin = 1;
                }
            }

            if((no+5) <= end)
                begin = no-4;
        }
        if(no <= 9){
            if((end-no)<5){
                begin = 1;
            }else{
                begin = no-4;
                if(begin < 1) begin = 1;
            }
        }
        if((begin+9) < end){
            end = begin+9;
        }

        for(var i=begin;i<=end;i++){
            nums.push(i);
        }
        page = {
            no:no,
            begin:begin,
            end:end,
            size:size,
            count:count,
            nums:nums,
            doing:doing
        };
        return page;
    }
});
