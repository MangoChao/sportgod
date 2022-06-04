define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'code', title: __('代碼'), operate: 'LIKE'},
                        {field: 'bid', title: __('球版ID'), operate: 'LIKE'},
                        {field: 'nickname', title: __('暱稱'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'ptime1', title: __('區間開始'), formatter: Table.api.formatter.datetime, addclass: 'datetimerange', datetimeFormat: 'YYYY-MM-DD', sortable: true},
                        {field: 'ptime2', title: __('區間結束'), formatter: Table.api.formatter.datetime, addclass: 'datetimerange', datetimeFormat: 'YYYY-MM-DD', sortable: true},
                        {field: 'pred', title: __('預測限次'), operate: 'LIKE'},
                        {field: 'pred2', title: __('剩餘次數'), operate: 'LIKE'},
                        {field: 'line_user_id', title: __('LINE UID'), operate: 'LIKE', visible: false},
                        {field: 'service.nickname', title: __('客服'), operate: 'LIKE'},
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
                level: function (value, row, index, custom) {
                    var colorArr = {'0':'black','1':'orange'};
                    var valueArr = {'0':__('level 0'),'1':__('level 1')};
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