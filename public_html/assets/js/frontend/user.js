define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {
    var validatoroptions = {
        invalid: function (form, errors) {
            $.each(errors, function (i, j) {
                Layer.msg(j);
            });
        }
    };
    var Controller = {
        myhome: function () {

        },
        notify: function () {

        },
        favorites: function () {

        },
        favoritesg: function () {

        },
        analysttitle: function () {
            $(document).on("click", ".btn_set_at", function () {
                $(this).closest(".check_style").find("a").removeClass("active");
                $(this).addClass("active");
                let id = $(this).data('id');
                let options = {url: Config.url.api+'/user/setat/id/'+id};
                Fast.api.ajax(options, function (mthis, data, ret) {
                    
                });
            });
        },
        article: function () {
            $(document).on("click", ".btn_del", function () {
                let id = $(this).data('id');
                layer.confirm('確定刪除?', {
                    title: false,
                    closeBtn: false,
                    shadeClose:true,
                    btn: ['確定','取消'] 
                }, function(index){
                    layer.close(index);
                    var mload = layer.load();

                    let options = {url: Config.url.api+'/article/delarticle/id/'+id};
                    Fast.api.ajax(options, function (mthis, result, ret) {
                        setTimeout(function () {
                            layer.close(mload);
                            location.reload();
                        }, 1000);
                    },function (mthis, result, ret) {
                        layer.close(mload);
                    });
                });
            });
        },
        godarticle: function () {
            $(document).on("click", ".btn_del", function () {
                let id = $(this).data('id');
                layer.confirm('確定刪除?', {
                    title: false,
                    closeBtn: false,
                    shadeClose:true,
                    btn: ['確定','取消'] 
                }, function(index){
                    layer.close(index);
                    var mload = layer.load();

                    let options = {url: Config.url.api+'/article/delgodarticle/id/'+id};
                    Fast.api.ajax(options, function (mthis, result, ret) {
                        setTimeout(function () {
                            layer.close(mload);
                            location.reload();
                        }, 1000);
                    },function (mthis, result, ret) {
                        layer.close(mload);
                    });
                });
            });
        },
        pred: function () {
            $(document).on("click", ".pred_radio label", function (e) {
                let id = $(this).data('id');
                let afor = $(this).attr('for');
                $('[data-id="'+id+'"]').removeClass('active');
                if($('#'+afor).prop("checked")){
                    $('#'+afor).attr("checked",false); 
                    e.preventDefault();
                }else{
                    $(this).addClass('active');
                }
            });

            let aid = $("#aid").val();
            Form.api.bindevent($("#pred_form"), function (data) {
                setTimeout(function () {
                    location.href = Config.url.furl+"/index/analyst/profile/id/"+aid+"/pt/1";
                }, 1000);
            });
        },
        buypoint: function () {
            $(document).on("click", ".btn_butpoint", function () {
                let id = $(this).data('id');
                layer.confirm('確定購買?', {
                    title: false,
                    closeBtn: false,
                    shadeClose:true,
                    btn: ['確定','取消'] 
                }, function(index){
                    layer.close(index);
                    var mload = layer.load();

                    let options = {url: Config.url.api+'/user/buyPoint/id/'+id};
                    Fast.api.ajax(options, function (mthis, result, ret){
                        let ajaxop = {
                            url: Config.url.furl+'/index/user/orderpoint', 
                            success: function (ret) {
                                $('#aj_orderpoint').html(ret);
                            },
                            error: function (ret) {
                                Layer.msg(ret.msg);
                            }
                        };
                        $.ajax(ajaxop);

                        let ajaxop_checkout = {
                            url: Config.url.furl+'/index/user/checkoutlayer/id/'+result.data, 
                            success: function (ret) {
                                op_box_tmpname = layer.open({
                                    title: '繳費單',
                                    type: 1,
                                    shadeClose: true,
                                    area : ['300','auto'],
                                    offset: '10%',
                                    content: ret 
                                }, function (index) {
                                    $(document).resize();
                                });
                            },
                            error: function (ret) {
                                Layer.msg(ret.msg);
                            }
                        };
                        $.ajax(ajaxop_checkout);
                        layer.close(mload);
                    },function (mthis, result, ret) {
                        layer.close(mload);
                    });
                });
            });
            $(document).on("click", ".btn_checkout", function () {
                let id = $(this).data('id');
                let ajaxop_checkout = {
                    url: Config.url.furl+'/index/user/checkoutlayer/id/'+id, 
                    success: function (ret) {
                        op_box_tmpname = layer.open({
                            title: '繳費單',
                            type: 1,
                            shadeClose: true,
                            area : ['300','auto'],
                            offset: '10%',
                            content: ret 
                        }, function (index) {
                            $(document).resize();
                        });
                    },
                    error: function (ret) {
                        Layer.msg(ret.msg);
                    }
                };
                $.ajax(ajaxop_checkout);
            });
        },
        addarticle: function () {
            Form.api.bindevent($("#addarticle_form"), function (data) {
                location.href = Config.url.furl+"/index/user/article";
            });
        },
        addgodarticle: function () {
            Form.api.bindevent($("#addgodarticle_form"), function (data) {
                location.href = Config.url.furl+"/index/user/godarticle";
            });
        },
        editarticle: function () {
            Form.api.bindevent($("#editarticle_form"), function (data) {
                location.href = Config.url.furl+"/index/user/article";
            });
        },
        editgodarticle: function () {
            Form.api.bindevent($("#editgodarticle_form"), function (data) {
                location.href = Config.url.furl+"/index/user/godarticle";
            });
        },
        login: function () {
            //本地验证未通过时提示
            $("#login-form").data("validator-options", validatoroptions);

            $(document).on("change", "input[name=type]", function () {
                var type = $(this).val();
                $("div.form-group[data-type]").addClass("hide");
                $("div.form-group[data-type='" + type + "']").removeClass("hide");
                $('#resetpwd-form').validator("setField", {
                    captcha: "required;length(4);integer[+];remote(" + $(this).data("check-url") + ", event=resetpwd, " + type + ":#" + type + ")",
                });
                $(".btn-captcha").data("url", $(this).data("send-url")).data("type", type);
            });

            //为表单绑定事件
            Form.api.bindevent($("#login-form"), function (data, ret) {
                setTimeout(function () {
                    location.href = ret.url ? ret.url : "/";
                }, 1000);
            });

            Form.api.bindevent($("#resetpwd-form"), function (data) {
                Layer.closeAll();
            });

            $(document).on("click", ".btn-forgot", function () {
                var id = "resetpwdtpl";
                var content = Template(id, {});
                Layer.open({
                    type: 1,
                    title: __('Reset password'),
                    area: ["450px", "355px"],
                    content: content,
                    success: function (layero) {
                        Form.api.bindevent($("#resetpwd-form", layero), function (data) {
                            Layer.closeAll();
                        });
                    }
                });
            });
        },
        register: function () {
            //本地验证未通过时提示
            $("#register-form").data("validator-options", validatoroptions);

            //为表单绑定事件
            Form.api.bindevent($("#register-form"), function (data, ret) {
                setTimeout(function () {
                    location.href = ret.url ? ret.url : "/";
                }, 1000);
            }, function (data) {
                $("input[name=captcha]").next(".input-group-addon").find("img").trigger("click");
            });
        },
        changepwd: function () {
            //本地验证未通过时提示
            $("#changepwd-form").data("validator-options", validatoroptions);

            //为表单绑定事件
            Form.api.bindevent($("#changepwd-form"), function (data, ret) {
                setTimeout(function () {
                    location.href = ret.url ? ret.url : "/";
                }, 1000);
            });
        },
        profile: function () {
            // 给上传按钮添加上传成功事件
            $("#faupload-avatar").data("upload-success", function (data) {
                var url = Fast.api.cdnurl(data.url);
                $(".profile-user-img").prop("src", url);
                Toastr.success(__('Uploaded successful'));
            });
            Form.api.bindevent($("#profile-form"));
            $(document).on("click", ".btn-change", function () {
                var that = this;
                var id = $(this).data("type") + "tpl";
                var content = Template(id, {});
                Layer.open({
                    type: 1,
                    title: "修改",
                    area: ["400px", "250px"],
                    content: content,
                    success: function (layero) {
                        var form = $("form", layero);
                        Form.api.bindevent(form, function (data) {
                            location.reload();
                            Layer.closeAll();
                        });
                    }
                });
            });
        },
        attachment: function () {
            require(['table'], function (Table) {

                // 初始化表格参数配置
                Table.api.init({
                    extend: {
                        index_url: 'user/attachment',
                    }
                });
                var urlArr = [];
                var multiple = Fast.api.query('multiple');
                multiple = multiple == 'true' ? true : false;

                var table = $("#table");

                table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function (e, row) {
                    if (e.type == 'check' || e.type == 'uncheck') {
                        row = [row];
                    } else {
                        urlArr = [];
                    }
                    $.each(row, function (i, j) {
                        if (e.type.indexOf("uncheck") > -1) {
                            var index = urlArr.indexOf(j.url);
                            if (index > -1) {
                                urlArr.splice(index, 1);
                            }
                        } else {
                            urlArr.indexOf(j.url) == -1 && urlArr.push(j.url);
                        }
                    });
                });

                // 初始化表格
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url,
                    sortName: 'id',
                    showToggle: false,
                    showExport: false,
                    columns: [
                        [
                            {field: 'state', checkbox: multiple, visible: multiple, operate:false},
                            {field: 'id', title: __('Id')},
                            {field: 'url', title: __('Preview'), formatter: function (value, row, index) {
                                    if (row.mimetype.indexOf("image") > -1) {
                                        var style = row.storage === 'upyun' ? '!/fwfh/120x90' : '';
                                        return '<a href="' + row.fullurl + '" target="_blank"><img src="' + row.fullurl + style + '" alt="" style="max-height:90px;max-width:120px"></a>';
                                    } else {
                                        return '<a href="' + row.fullurl + '" target="_blank"><img src="' + Fast.api.fixurl("ajax/icon") + "?suffix=" + row.imagetype + '" alt="" style="max-height:90px;max-width:120px"></a>';
                                    }
                                }, operate: false},
                            {field: 'filename', title: __('Filename'), formatter: Table.api.formatter.search, operate: 'like'},
                            {field: 'imagewidth', title: __('Imagewidth'), operate: false},
                            {field: 'imageheight', title: __('Imageheight'), operate: false},
                            {
                                field: 'mimetype', title: __('Mimetype'), operate: 'LIKE %...%',
                                process: function (value, arg) {
                                    return value.replace(/\*/g, '%');
                                }
                            },
                            {field: 'createtime', title: __('Createtime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                            {
                                field: 'operate', title: __('Operate'), events: {
                                    'click .btn-chooseone': function (e, value, row, index) {
                                        Fast.api.close({url: row.url, multiple: multiple});
                                    },
                                }, formatter: function () {
                                    return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                                }
                            }
                        ]
                    ]
                });

                // 选中多个
                $(document).on("click", ".btn-choose-multi", function () {
                    Fast.api.close({url: urlArr.join(","), multiple: multiple});
                });

                // 为表格绑定事件
                Table.api.bindevent(table);
                require(['upload'], function (Upload) {
                    Upload.api.upload($("#toolbar .faupload"), function () {
                        $(".btn-refresh").trigger("click");
                    });
                });

            });
        }
    };
    return Controller;
});
