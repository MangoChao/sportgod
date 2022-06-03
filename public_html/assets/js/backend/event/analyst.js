define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/analyst/index',
                    add_url: 'event/analyst/add',
                    edit_url: 'event/analyst/edit',
                    del_url: 'event/analyst/del',
                    table: 'analyst',
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
                        {field: 'analyst_name', title: __('名稱'), operate: 'LIKE', sortable: true, align: 'left'},
                        {field: 'pred_cat', title: '預測類別', align: 'left', operate:false, formatter: Table.api.formatter.label},
                        {field: 'autopred', title: __('自動預測'), formatter: Controller.api.formatter.autopred, searchList: {1: __('autopred 1'), 0: __('autopred 0')}},
                        {field: 'autopred_count', title: __('自動預測上限'), operate: 'LIKE', sortable: true},
                        {field: 'autopred_today', title: __('今日已預測'), operate: 'LIKE', sortable: true},
                        {field: 'status', title: __('Status'), formatter: Controller.api.formatter.status, searchList: {1: __('Status 1'), 0: __('Status 0'), 2: __('Status 2')}},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                status: function (value, row, index, custom) {
                    var colorArr = {'0':'gray','1':'success','2':'danger','3':'danger'};
                    var valueArr = {'0':__('Status 0'),'1':__('Status 1'),'2':__('Status 2'),'3':__('Status 3')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                autopred: function (value, row, index, custom) {
                    var colorArr = {'0':'gray','1':'success'};
                    var valueArr = {'0':__('autopred 0'),'1':__('autopred 1')};
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