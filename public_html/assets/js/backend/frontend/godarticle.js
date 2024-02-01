define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'frontend/godarticle/index',
                    add_url: 'frontend/godarticle/add',
                    edit_url: 'frontend/godarticle/edit',
                    del_url: 'frontend/godarticle/del',
                    table: 'godarticle',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true, visible: false},
                        {field: 'godtype.type_name', title: __('type_name'), operate: 'LIKE'},
                        {field: 'cat.cat_name', title: __('cat_name')},
                        {field: 'title', title: __('title'), operate: 'LIKE', align: 'left'},
                        {field: 'fav', title: __('fav'), operate: 'LIKE'},
                        {field: 'user.nickname', title: __('user_name'), operate: 'LIKE'},
                        {field: 'status', title: __('Status'), formatter: Controller.api.formatter.status, searchList: {0: __('Status 0'), 1: __('Status 1'), 2: __('Status 2'), 3: __('Status 3')}},
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

                $(document).on("change", "[name='row[god_type]']", function () {
                    let god_type = $("[name='row[god_type]']").val();
                    if(god_type == 2){
                        $(".type-base").hide();
                        $(".type-teach").show();
                    }else{
                        $(".type-base").show();
                        $(".type-teach").hide();
                    }

                    if(god_type == 2 || god_type == 4){
                        $(".type-onlyvideo").hide();
                    }else{
                        $(".type-onlyvideo").show();
                    }
                });
            },
            formatter:{
                status: function (value, row, index, custom) {
                    var colorArr = {'0':'orange','1':'success','2':'danger','3':'gray'};
                    var valueArr = {'0':__('Status 0'),'1':__('Status 1'),'2':__('Status 2'),'3':__('Status 3')};
                    if (typeof custom !== 'undefined') {
                        colorArr = $.extend(colorArr, custom);
                    }
                    var color = typeof colorArr[value] !== 'undefined' ? colorArr[value] : 'orange';
                    return '<span class="text-' + color + '"><i class="fa fa-circle"></i> ' + valueArr[value] + '</span>';
                },
            }
        }
    };
    return Controller;
});