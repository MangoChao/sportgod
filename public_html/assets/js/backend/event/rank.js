define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/rank/index',
                    content_url: 'event/rank/content',
                    table: 'rank',
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
                        {field: 'rtime1', title: __('開始時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', datetimeFormat: 'YYYY-MM-DD', sortable: true},
                        {field: 'rtime2', title: __('結束時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', datetimeFormat: 'YYYY-MM-DD', sortable: true},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'operate', title: __('Operate'), table: table, events: Controller.api.events.operate, formatter: Controller.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        content: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    var mbuttons = [{
                        name: 'content',
                        text: '名單',
                        classname: 'btn btn-warning btn-xs btn-content',
                    }]
                    var buttons = $.extend([], mbuttons || []);
                    // 所有按钮名称
                    var names = [];
                    buttons.forEach(function (item) {
                        names.push(item.name);
                    });
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                },
            },
            events:{
                operate: {
                    'click .btn-content': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.content_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), '名單', $(this).data() || {});
                    },
                }
            }
        }
    };
    return Controller;
});