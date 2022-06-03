define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/userpred/index',
                    table: 'user_to_pred',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true, visible: false},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user.bid', title: __('球版ID'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('暱稱'), operate: 'LIKE'},
                        {field: 'pred.pred_type', title: __('預測類型'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.pred_type, searchList: {1: __('讓分'), 2: __('大小')}},
                        {field: 'pred.comply', title: __('預測結果'), operate: 'LIKE', sortable: true, formatter: Controller.api.formatter.comply, searchList: {0: __('未確認'), 1: __('贏'), 2: __('輸')}},
                        {field: 'pred.predtime', title: __('預測時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
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
                pred_type: function (value, row, index, custom) {
                    var colorArr = {'1':'black','2':'black'};
                    var valueArr = {'1':__('讓分'),'2':__('大小')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
            }
        }
    };
    return Controller;
});