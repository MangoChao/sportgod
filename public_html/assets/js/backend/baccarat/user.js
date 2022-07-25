define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'baccarat/user/index',
                    add_url: 'baccarat/user/add',
                    edit_url: 'baccarat/user/edit',
                    del_url: 'baccarat/user/del',
                    table: 'baccarat',
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
                        {field: 'code', title: __('code')},
                        {field: 'remark', title: __('remark')},
                        {field: 'debt', title: __('debt')},
                        {field: 'repay', title: __('repay')},
                        {field: 'checkout_link', title: __('checkout_link'), formatter: Controller.api.formatter.checkout_link},
                        {field: 'ordernum', title: __('ordernum')},
                        {field: 'ACTCode', title: __('ACTCode')},
                        {field: 'Bank1', title: __('Bank1')},
                        {field: 'Bank2', title: __('Bank2')},
                        {field: 'Bank3', title: __('Bank3')},
                        {field: 'QRCode', title: __('QRCode')},
                        {field: 'take', title: __("take"), searchList: {"0":__('take 0'),"1":__('take 1')}, formatter: Controller.api.formatter.take},
                        {field: 'locked', title: __("locked"), searchList: {"0":__('locked 0'),"1":__('locked 1')}, formatter: Controller.api.formatter.locked},
                        {field: 'status', title: __("Status"), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Controller.api.formatter.status},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                take: function (value, row, index, custom) {
                    var colorArr = {'0':'danger','1':'success'};
                    var valueArr = {'0':__('take 0'),'1':__('take 1')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                locked: function (value, row, index, custom) {
                    var colorArr = {'0':'success','1':'danger'};
                    var valueArr = {'0':__('locked 0'),'1':__('locked 1')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                status: function (value, row, index, custom) {
                    var colorArr = {'0':'gray','1':'success'};
                    var valueArr = {'0':__('Status 0'),'1':__('Status 1')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + valueArr[value] + '</span>';
                },
                checkout_link: function (value, row, index) {
                    if(row.ordernum){
                        return '<a href="' + row.checkout_link + '" target="_blank" class="label bg-green">打開付款單</a>';
                    }else{
                        return '-';
                    }
                },
            }
        }
    };
    return Controller;
});