define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'frontend/adbanner/index',
                    add_url: 'frontend/adbanner/add',
                    edit_url: 'frontend/adbanner/edit',
                    del_url: 'frontend/adbanner/del',
                    table: 'ad_banner',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                pagination: false,
                commonSearch: false,
                search: false,
                showExport: false,
                showToggle: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'url', title: __('url'), operate: 'LIKE'},
                        {field: 'img', title: __('img'), events: Table.api.events.image, formatter: Table.api.formatter.images, operate: false},
                        {field: 'type', title: __('type'), formatter: Controller.api.formatter.type, searchList: {0: __('type 0'), 1: __('type 1')}},
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