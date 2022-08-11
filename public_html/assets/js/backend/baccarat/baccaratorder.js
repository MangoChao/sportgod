define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'baccarat/baccaratorder/index',
                    table: 'baccarat_order',
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
                        {field: 'ip', title: __('ip')},
                        {field: 'order_no', title: __('order_no')},
                        {field: 'trans_order_no', title: __('trans_order_no')},
                        {field: 'amount', title: __('amount')},
                        {field: 'msg', title: __('msg')},
                        {field: 'create_time', title: __('create_time')},
                        {field: 'end_time', title: __('end_time')},
                        {field: 'name', title: __('name')},
                        {field: 'bank_card_number', title: __('bank_card_number')},
                        {field: 'bank_name', title: __('bank_name')},
                        {field: 'bank_zhihang', title: __('bank_zhihang')},
                        {field: 'checkout_url', title: __('checkout_url'), formatter: Controller.api.formatter.checkout_url},
                        {field: 'status', title: __("status"), searchList: {"0":__('status 0'),"1":__('status 1'),"2":__('status 2'),"3":__('status 3')}, formatter: Controller.api.formatter.status},
                        {field: 'request', title: __('request'), visible: false},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
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
                status: function (value, row, index, custom) {
                    var colorArr = {'0':'orange','1':'success','1':'danger','1':'gray'};
                    var valueArr = {'0':__('status 0'),'1':__('status 1'),'2':__('status 2'),'3':__('status 3')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                checkout_url: function (value, row, index) {
                    if(row.ordernum){
                        return '<a href="' + row.checkout_link + '" target="_blank" class="label bg-green">金流付款單</a>';
                    }else{
                        return '-';
                    }
                },
            }
        }
    };
    return Controller;
});