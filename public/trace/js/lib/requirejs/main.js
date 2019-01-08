/**
 * Created by Administrator on 2017/9/22.
 */
require.config({
    baseUrl: '/',
    map: {
        '*': {
            'css': 'js/lib/requirejs/css.min'
        }
    },

    urlArgs: function(id, url) { //除指定文件外，别的文件缓存全清除
        var args = "r=" + (new Date()).getTime();
        var arr = [
            "jquery.min", "jquery.circliful", "jquery.jedate", "echarts", "avalon", "bootstrap.min", "netdna"
        ];
        for (var item in arr) {
            if (url.indexOf(arr[item]) !== -1 && url.indexOf("utils") === -1) {
                args = 'v=2'
            }
        }
        return (url.indexOf('?') === -1 ? '?' : '&') + args;
    },

    paths: {
        //js
        "jquery": ["js/lib/jquery/jquery.min"],
        "jquery.jedate": ["js/lib/jquery/jedate/jedate.min"],
        "jquery.fileUpload": ["js/lib/jquery/fileUpload/fileUpload"],
        "nav": ["js/lib/jquery/aside-nav"],

        "echarts": ["js/lib/jquery/echart/echarts.min"],
        "echarts_b": ["http://echarts.baidu.com/gallery/vendors/echarts/echarts.min"],//饼状图
        "echarts_c": ["http://echarts.baidu.com/gallery/vendors/echarts/echarts.min"],//饼状图

        "flow": ["js/lib/jquery/flow/flow"],

        "time": ["js/lib/jquery/timeLine/time"],
        "timeLine": ["js/lib/jquery/timeLine/timeLine"],

        "avalon": ["js/lib/avalon/avalon.min"],

        "ie7": ["js/lib/avalon/IE7"], //ie7--json3--promise 为兼容ie8
        "json3": ["js/lib/avalon/json3"],
        "promise": ["js/lib/avalon/promise"],


        "basic": ["js/module/basic"],
        "component": ["js/module/component"],

        "window.utils": ["js/lib/utils/window.utils"],
        "jquery.utils": ["js/lib/utils/jquery.utils"],

        //css
        "index.css": ["js/css/null"],
        "baseUiIndex.css": ["js/css/null2"],
        "bootstrap": ["js/lib/bootstrap/bootstrap.min"],
        "summernote": ["js/lib/bootstrap/bootstrap-summernote/summernote"],
        "summernote.lang": ["js/lib/bootstrap/bootstrap-summernote/summernote-zh-CN"],
        "font-awesome.css": ["js/css/null4"],
    },
    shim: {
        "jquery": {
            exports: "$",
            deps: [
                "window.utils"
            ]
        },
        "jquery.circliful": {
            deps: [
                "jquery",
                'css!js/lib/jquery/circliful/jquery.circliful.css'
            ]
        },
        "jquery.jedate": {
            deps: [
                "jquery",
                'css!/js/lib/jquery/jedate/jedate.css'
            ]
        },
        "jquery.utils": {
            deps: [
                "jquery"
            ]
        },
        "jquery.fileUpload": {
            deps: [
                "jquery",
                'css!/js/lib/jquery/fileUpload/fileUpload.css'
            ]
        },
        "jquery.fileUpload_single": {
            deps: [
                "jquery",
                'css!js/lib/jquery/fileUpload/fileUpload.css'
            ]
        },

        "flow": {
            deps: [
                "jquery",
                'css!/js/lib/jquery/flow/flow.css'
            ]
        },
        "timeLine": {
            deps: [
                "jquery",
                "time",
                'css!/js/lib/jquery/timeLine/time.css'
            ]
        },


        "echarts": {
            exports: ["echarts"],
            deps: ["jquery"]
        },
        "echarts_c": {
            exports: ["echarts"],
            deps: ["jquery"]
        },
        "avalon": {
            exports: ["avalon"],
            deps: [
                "jquery.utils"
            ]
        },

        "basic": {
            deps: [
                "jquery",
                "avalon",
                'css!/css/left.css',
                'css!/css/header.css',
                'css!/css/right.css',
                'css!/css/bottom.css',
            ]
        },
        "nav": {
            deps: ["basic"]
        },

        //纯粹css依赖
        "index.css": {
            deps: ["jquery", 'css!/css/index.css']
        },
        "baseUiIndex.css": {
            deps: ["jquery", 'css!/css/base-ui-index.css']
        },
        "bootstrap": {
            deps: ["jquery", 'css!js/lib/bootstrap/bootstrap.min.css']
        },
        "summernote": {
            deps: [
                "bootstrap",
                'css!js/lib/bootstrap/bootstrap-summernote/summernote.css',
            ]
        },
        "summernote.lang": {
            deps: ["summernote"]
        },
        "font-awesome.css": {
            deps: ["jquery", 'css!//netdna.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css']
        },

    }
})