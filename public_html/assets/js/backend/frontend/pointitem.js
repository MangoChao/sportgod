define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'frontend/pointitem/index',
                    add_url: 'frontend/pointitem/add',
                    edit_url: 'frontend/pointitem/edit',
                    del_url: 'frontend/pointitem/del',
                    table: 'point_item',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'point',
                sortOrder: 'asc',
                pagination: false,
                commonSearch: false,
                search: false,
                showExport: false,
                showToggle: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true, visible: false},
                        {field: 'point', title: __('point'), operate: 'LIKE'},
                        {field: 'price', title: __('price'), operate: 'LIKE'},
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
            }
        }
    };
    return Controller;
});