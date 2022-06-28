define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'baccarat/baccaratlog/index',
                    table: 'baccarat_log',
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
                        {field: 'baccarat_id', title: __('baccarat_id')},
                        {field: 'baccarat.code', title: __('code')},
                        {field: 'baccarat.remark', title: __('remark')},
                        {field: 'msg', title: __('msg')},
                        {field: 'ip', title: __('ip')},
                        {field: 'request', title: __('request')},
                        {field: 'Ordernum', title: __('Ordernum')},
                        {field: 'ACTCode', title: __('ACTCode')},
                        {field: 'bkid', title: __('bkid')},
                        {field: 'Total', title: __('Total')},
                        {field: 'Status', title: __('Status')},
                        {field: 'PoliceReport', title: __("PoliceReport"), searchList: {"0":__('PoliceReport 0'),"1":__('PoliceReport 1')}, formatter: Controller.api.formatter.PoliceReport},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
                PoliceReport: function (value, row, index, custom) {
                    var colorArr = {'0':'success','1':'danger'};
                    var valueArr = {'0':__('PoliceReport 0'),'1':__('PoliceReport 1')};
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