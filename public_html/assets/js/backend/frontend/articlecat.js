define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'frontend/articlecat/index',
                    add_url: 'frontend/articlecat/add',
                    edit_url: 'frontend/articlecat/edit',
                    del_url: 'frontend/articlecat/del',
                    dragsort_url: 'ajax/weigh',
                    table: 'article_cat',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                pagination: false,
                commonSearch: false,
                search: false,
                showExport: false,
                showToggle: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'cat_name', title: __('cat_name'), operate: 'LIKE'},
                        {field: 'weigh', title: __('weigh'), operate: 'LIKE', visible: false},
                        {field: 'type', title: __('type'), formatter: Controller.api.formatter.type, searchList: {0: __('type 0'), 1: __('type 1')}},
                        {field: 'status', title: __('Status'), formatter: Controller.api.formatter.status, searchList: {0: __('Status 0'), 1: __('Status 1')}},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
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
                    var colorArr = {'0':'gray','1':'success'};
                    var valueArr = {'0':__('Status 0'),'1':__('Status 1')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + valueArr[value] + '</span>';
                },
                type: function (value, row, index, custom) {
                    var colorArr = {'0':'orange','1':'success'};
                    var valueArr = {'0':__('type 0'),'1':__('type 1')};
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