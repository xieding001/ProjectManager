/**
 * Created by Administrator on 2017/9/22.
 */
var ms_page;
avalon.component('ms-page', {
    template: `<div style="width: 100%;text-align: center;">
                <ul class="pagination fr" :visible="page.count > page.size">
                    <li class="disabled">
                        <span>总记录数：{{ page.count }}</span>
                    </li>
                    <li><a href="javascript:;" :click="list(1)">首页</a></li>
                    <li><a href="javascript:;" :click="list(page.no-1)">上一页</a></li>
                    <!--ms-for: el in page.nums-->
                    <li class="active" :if="el==page.no"><a href="javascript:;" :click="list(el)">{{ el }}</a></li>
                    <li :if="el!=page.no"><a href="javascript:;" :click="list(el)">{{ el }}</a></li>
                    <!--ms-for-end:-->
                    <li><a href="javascript:;" :click="list(page.no+1)">下一页</a></li>
                    <li><a href="javascript:;" :click="list(Math.ceil(page.count/page.size))">尾页</a></li>
                </ul>
                <div class="pagination-no-data" :visible="page.count == 0">您还没有此类数据...</div>

                <div class="clear"></div>
            </div>`,
    defaults: {
        page:{
            no:1,
            begin:0,
            end:0,
            size:9,
            count:1,
            nums:[],
            doing:false
        },
        list: function(pageNo) {
            model.list(pageNo);
        },
        onReady: function(e) {
            ms_page = e.vmodel;
        }
    }
});
