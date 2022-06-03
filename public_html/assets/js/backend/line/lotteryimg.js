define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'line/lotteryimg/index',
                    add_url: 'line/lotteryimg/add',
                    table: 'lottery_img',
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
                        {field: 'img', title: '照片', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
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
                // isblank: function (value, row, index, custom) {
                //     var colorArr = {'0':'black','1':'black'};
                //     var valueArr = {'0':__('isblank 0'),'1':__('isblank 1')};
                //     if (typeof custom !== 'undefined') {
                //         colorArr = $.extend(colorArr, custom);
                //     }
                //     var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                //     return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                // },
                // type: function (value, row, index, custom) {
                //     var colorArr = {'0':'black','1':'black'};
                //     var valueArr = {'0':__('type 0'),'1':__('type 1')};
                //     if (typeof custom !== 'undefined') {
                //         colorArr = $.extend(colorArr, custom);
                //     }
                //     var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                //     return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                // },
                // status: function (value, row, index, custom) {
                //     var colorArr = {'0':'gray','1':'success'};
                //     var valueArr = {'0':__('Status 0'),'1':__('Status 1')};
                //     if (typeof custom !== 'undefined') {
                //         colorArr = $.extend(colorArr, custom);
                //     }
                //     var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                //     return '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + valueArr[value] + '</span>';
                // },
                // url: function (value, row, index) {
                //     return '<a href="' + row.fullurl + '" target="_blank" class="label bg-green">' + value + '</a>';
                // },
            }
        }
    };
    return Controller;
});