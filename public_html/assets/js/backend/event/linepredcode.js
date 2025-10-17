define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Controller.api.bindevent();

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/linepredcode/index',
                    add_url: 'event/linepredcode/add',
                    del_url: 'event/linepredcode/del',
                    table: 'line_pred_code',
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
                        {field: 'code', title: __('code'), operate: 'LIKE', formatter: Controller.api.formatter.copytext, sortable: false},
                        {field: 'analyst.analyst_name', title: __('分析師'), operate: 'LIKE', sortable: false},
                        {field: 'status', title: __('Status'), formatter: Controller.api.formatter.status, searchList: {0: __('line_pred_code status 0'), 1: __('line_pred_code status 1')}},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            $(document).on("click", ".btn-copy", function () {
                $(this).closest('span').siblings('[type="text"]').select();
                document.execCommand("Copy");
                Layer.msg('已複製');
            });
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
                    var colorArr = {'0':'success','1':'orange'};
                    var valueArr = {'0':__('line_pred_code status 0'),'1':__('line_pred_code status 1')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '">' + valueArr[value] + '</span>';
                },
                copytext: function (value, row, index) {
                    value = value === null ? '' : value.toString();
                    return '<div class="input-group input-group-sm" style="width:250px;margin:0 auto;"><input type="text" class="form-control input-sm " value="' + value + '"><span class="input-group-btn input-group-sm"><a href="javascript:;" class="btn btn-default btn-sm btn-copy"><i class="fa fa-link"></i></a></span></div>';
                },
            }
        }
    };
    return Controller;
});