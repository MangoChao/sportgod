define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/pred/index',
                    add_url: 'event/pred/add',
                    edit_url: 'event/pred/edit',
                    del_url: 'event/pred/del',
                    table: 'pred',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                exportTypes: ['csv', 'txt', 'doc', 'excel'],
                // sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        // {field: 'event_title', title: __('賽事'), visible: false},
                        {field: 'analyst.analyst_name', title: __('分析師'), operate: 'LIKE', sortable: true},
                        {field: 'event_id', title: __('賽事ID'), visible: false},
                        {field: 'mevent.starttime', title: __('比賽時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'ballformatter_name', title: __('主客隊'), operate: false, formatter: Controller.api.formatter.ballformatter_name , align: 'left'},
                        {field: 'master_refund', title: __('主場讓')},
                        {field: 'guests_refund', title: __('客場讓')},
                        {field: 'bigscore', title: __('大小分')},
                        {field: 'master_score', title: __('主場得分')},
                        {field: 'guests_score', title: __('客場得分')},
                        {field: 'comply', title: __('預測結果'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.comply, searchList: {0: __('未確認'), 1: __('贏'), 2: __('輸')}},
                        {field: 'winteam', title: __('讓分'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.winteam, searchList: {0: __('客場'), 1: __('主場')}},
                        {field: 'bigsmall', title: __('大小'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.bigsmall, searchList: {0: __('小'), 1: __('大')}},
                        {field: 'pred_type', title: __('預測類型'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.pred_type, searchList: {1: __('讓分'), 2: __('大小')}},
                        {field: 'isauto', title: __('自動預測'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.isauto, searchList: {0: __('否'), 1: __('是')}},
                        {field: 'predtime', title: __('預測時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'isreadwin', title: __('已看讓分'), operate: 'LIKE', sortable: true, visible: false},
                        {field: 'isreadbig', title: __('已看大小'), operate: 'LIKE', sortable: true, visible: false},
                        {field: 'admin_id', title: __('管理員ID'), operate: 'LIKE', sortable: true, visible: false},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
                comply: function (value, row, index, custom) {
                    var colorArr = {'0':'black','1':'success','2':'danger'};
                    var valueArr = {'0': __('未確認'), '1': __('贏'), '2': __('輸')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                isauto: function (value, row, index, custom) {
                    var colorArr = {'0':'black','1':'success'};
                    var valueArr = {'0':__('否'),'1':__('是')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                winteam: function (value, row, index, custom) {
                    if(row.pred_type == 1){
                        var colorArr = {'0':'info','1':'danger'};
                        var valueArr = {'0':__('客場'),'1':__('主場')};
                        if (typeof custom !== 'undefined') {
                            colorArr = $.extend(colorArr, custom);
                        }
                        var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                        return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                    }else{
                        return '-';
                    }
                },
                bigsmall: function (value, row, index, custom) {
                    if(row.pred_type == 2){
                        var colorArr = {'0':'info','1':'danger'};
                        var valueArr = {'0':__('小'),'1':__('大')};
                        if (typeof custom !== 'undefined') {
                            colorArr = $.extend(colorArr, custom);
                        }
                        var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                        return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                    }else{
                        return '-';
                    }
                },
                pred_type: function (value, row, index, custom) {
                    var colorArr = {'1':'black','2':'black'};
                    var valueArr = {'1':__('讓分'),'2':__('大小')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                ballformatter_name: function (value, row, index, custom) {
                    var content = `<span class="">`+row.mevent.guests+`</span><br><span class="text-info">`+row.mevent.master+`</span><span class="text-danger">(主)</span>`;
                    return content;
                },
                ballformatter_refund: function (value, row, index, custom) {
                    var content = `<span class="text-info">`+row.guests_refund+`&nbsp;</span><br><span class="text-info">`+row.master_refund+`&nbsp;</span>`;
                    return content;
                },
                ballformatter_bigscore: function (value, row, index, custom) {
                    var content = `<span class="text-info">`+row.bigscore+`</span><span class="">&nbsp;大</span><br><span class="">&nbsp;小</span>`;
                    return content;
                },
            }
        }
    };
    return Controller;
});