define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/event/index',
                    param_url: 'event/event/param',
                    edit_url: 'event/event/edit',
                    table: 'event',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                exportTypes: ['csv', 'txt', 'doc', 'excel'],
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'event_category_id', title: __('類別ID'), visible: false},
                        {field: 'eventcat.title', title: __('類別'), operate: 'LIKE', sortable: true, align: 'left'},
                        {field: 'starttime', title: __('比賽時間'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', datetimeFormat: 'YYYY-MM-DD HH:mm', sortable: true},
                        {field: 'score', title: __('比分'), operate: false, formatter: Controller.api.formatter.score, align: 'right'},
                        {field: 'ballformatter_refund', title: __('全場讓分'), operate: false, formatter: Controller.api.formatter.ballformatter_refund, align: 'right'},
                        {field: 'ballformatter_name', title: __('主客隊'), operate: false, formatter: Controller.api.formatter.ballformatter_name , align: 'left'},
                        {field: 'ballformatter_bigscore', title: __('全場大小'), operate: false, formatter: Controller.api.formatter.ballformatter_bigscore, align: 'right'},
                        {field: 'master', title: __('主場'), operate: 'LIKE', sortable: true, visible: false, align: 'left'},
                        {field: 'master_refund', title: __('主場讓分'), operate: 'LIKE', sortable: true, visible: false},
                        {field: 'guests', title: __('客場'), operate: 'LIKE', sortable: true, visible: false, align: 'left'},
                        {field: 'guests_refund', title: __('客場讓分'), operate: 'LIKE', sortable: true, visible: false, align: 'left'},
                        {field: 'bigscore', title: __('大小分'), operate: 'LIKE', sortable: true, visible: false, align: 'left'},
                        {field: 'pred', title: __('預測'), operate: 'LIKE', sortable: true},
                        {field: 'createtime', title: __('createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'updatetime', title: __('updatetime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table, events: Controller.api.events.operate, formatter: Controller.api.formatter.operate}
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
        param: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter:{
                score: function (value, row, index, custom) {
                    var guests_score = "-";
                    var master_score = "-";
                    if(row.guests_score != null) guests_score = row.guests_score;
                    if(row.master_score != null) master_score = row.master_score;
                    var content = `<span class="text-success">`+guests_score+`&nbsp;</span><br><span class="text-success">`+master_score+`&nbsp;</span>`;
                    return content;
                },
                ballformatter_name: function (value, row, index, custom) {
                    var content = `<span class="">`+row.guests+`</span><br><span class="text-info">`+row.master+`</span><span class="text-danger">(主)</span>`;
                    return content;
                },
                ballformatter_refund: function (value, row, index, custom) {
                    var content = `<span class="text-info">`+row.guests_refund+`&nbsp;</span><br><span class="text-info">`+row.master_refund+`&nbsp;</span>`;
                    return content;
                },
                ballformatter_bigscore: function (value, row, index, custom) {
                    var content = `<span class="text-info">`+row.bigscore+`</span><span class="">&nbsp;大</span><br><span class="">&nbsp;小</span>`;
                    return content;
                },
                operate: function (value, row, index) {
                    var table = this.table;
                    // 操作配置
                    var options = table ? table.bootstrapTable('getOptions') : {};
                    // 默认按钮组
                    if(row.hasparam == 1){
                        var mbuttons = [{
                            name: 'param',
                            text: '變動',
                            classname: 'btn btn-warning btn-xs btn-param',
                        }]
                    }else{
                        var mbuttons = [];
                    }
                    var buttons = $.extend([], mbuttons || []);
                    // 所有按钮名称
                    var names = [];
                    buttons.forEach(function (item) {
                        names.push(item.name);
                    });
                    if (options.extend.edit_url !== '' && names.indexOf('edit') === -1) {
                        Table.button.edit.url = options.extend.edit_url;
                        buttons.push(Table.button.edit);
                    }
                    return Table.api.buttonlink(this, buttons, value, row, index, 'operate');
                },
            },
            events:{
                operate: {
                    'click .btn-param': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.param_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), '變動', $(this).data() || {});
                    },
                    'click .btn-editone': function (e, value, row, index) {
                        e.stopPropagation();
                        e.preventDefault();
                        var table = $(this).closest('table');
                        var options = table.bootstrapTable('getOptions');
                        var ids = row[options.pk];
                        row = $.extend({}, row ? row : {}, {ids: ids});
                        var url = options.extend.edit_url;
                        Fast.api.open(Table.api.replaceurl(url, row, table), __('Edit'), $(this).data() || {});
                    },
                }
            }
        }
    };
    return Controller;
});