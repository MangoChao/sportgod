define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {
    var validatoroptions = {
        invalid: function (form, errors) {
            $.each(errors, function (i, j) {
                Layer.msg(j);
            });
        }
    };
    var Controller = {
        index:function(){
            Controller.liffinit();
        },
        register:function(){
            Toastr.options.positionClass = 'toast-bottom-center';
            Controller.liffinit(function(line_user_id){
                let options = {
                    url: Config.url.api+'/line/checkrichmenu/line_user_id/'+line_user_id, 
                    success: function (ret) {
                        if(ret == 1){
                            Layer.msg('已註冊, 即將關閉視窗');
                            setTimeout(function () {
                                liff.closeWindow();
                            }, 1000);
                        }else{
                            $('#step02_box').hide();
                            $('#step01_box').slideDown();
                        }
                    },
                    error: function (ret) {
                        $('#step02_box_error').text(ret.msg);
                    }
                };
                $.ajax(options);
                // let user_id = $('#user_id').val();
                // let options = {url: Config.url.api+'/customer/check', data: {line_user_id: line_user_id, user_id: user_id}};
                // Fast.api.ajax(options, function (mthis, result, ret) {
                //     let code = result.data.code;
                //     if(code == 0){
                //         $('#step02_box').hide();
                //         $('#step01_box').slideDown();
                //     }else if(code == 1){
                //         location.href = 'https://liff.line.me/'+Config.site.liffid+'/subscriptionpage/';
                //     }else if(code == 2){
                //         Layer.msg(result.msg);
                //         liff.closeWindow();
                //     }else{
                //         Layer.msg('發生錯誤');
                //         return false;
                //     }
                // },function (mthis, result, ret){
                //     $('#step02_box_error').text(result.msg);
                // });
            });
            
            //本地验证未通过时提示
            $("#mform").data("validator-options", validatoroptions);

            //为表单绑定事件
            Form.api.bindevent($("#mform"), function (data, ret) {
                setTimeout(function () {
                    liff.closeWindow();
                }, 1000);
            });
        },
        login:function(){
            Toastr.options.positionClass = 'toast-bottom-center';
            Controller.liffinit(function(line_user_id){
                $('#step02_box').hide();
                $('#step01_box').slideDown();
            });
            
            Form.api.bindevent($("#mform"), function (data, ret) {
                setTimeout(function () {
                    liff.closeWindow();
                }, 1000);
            });
        },
        pred:function(){
            Controller.liffinit(function(line_user_id){
                let options = {
                    url: Config.url.api+'/line/checkrichmenu/line_user_id/'+line_user_id, 
                    success: function (ret) {
                        // if(ret == 1){
                        //     Layer.msg('已註冊, 即將關閉視窗');
                        //     setTimeout(function () {
                        //         liff.closeWindow();
                        //     }, 1000);
                        // }else{
                        //     $('#step02_box').hide();
                        //     $('#step01_box').slideDown();
                        // }
                        
                        options = {
                            url: Config.url.furl+'/index/liff/getpred/uid/'+line_user_id, 
                            success: function (ret) {
                                $('#step02_box').hide();
                                $('#step01_box').html(ret).slideDown();

                                
                                Form.api.bindevent($("#s_analyst_form"),function (mthis, result, ret) {
                                    var mload = layer.load();
                                    let id = result.data.id;
                                    let options = {
                                        url: Config.url.furl+'/index/liff/analystinfo/id/'+id, 
                                        success: function (ret) {
                                            layer.close(mload);
                                            if(ret == 0){
                                                Layer.msg('發生錯誤');
                                            }else{
                                                layer.open({
                                                    title: '分析師',
                                                    type: 1,
                                                    area : '90%',
                                                    offset: '5%',
                                                    shadeClose: true,
                                                    content: ret 
                                                }, function () {
                                                    $(document).resize();
                                                });
                                            }
                                        },
                                        error: function (ret) {
                                            layer.close(mload);
                                            Layer.msg(ret.msg);
                                        }
                                    };
                                    $.ajax(options);
                                });
                            },
                            error: function (ret) {
                                $('#step02_box_error').text(ret.msg);
                            }
                        };
                        $.ajax(options);


                    },
                    error: function (ret) {
                        $('#step02_box_error').text(ret.msg);
                    }
                };
                $.ajax(options);
            });

            
            $(document).on("click", "[name='eventcatlist']", function () {
                var mload = layer.load();
                let cid = $(this).val(); 
                options = {
                    url: Config.url.furl+'/index/liff/eventlist/cid/'+cid, 
                    success: function (ret) {
                        $('#eventlist').html(ret);
                        layer.close(mload);
                    },
                    error: function (ret) {
                        Layer.msg(ret.msg);
                        layer.close(mload);
                    }
                };
                $.ajax(options);
            });

            $(document).on("click", ".btn_eventinfo", function () {
                var mload = layer.load();
                let id = $(this).data('id'); 
                let line_user_id = $('#line_user_id').val();
                let options = {
                    url: Config.url.furl+'/index/liff/eventinfo', 
                    data: {id: id, line_user_id: line_user_id},
                    success: function (ret) {
                        layer.close(mload);
                        if(ret == 0){
                            Layer.msg('發生錯誤, 請重啟視窗');
                            setTimeout(function () {
                                liff.closeWindow();
                            }, 1000);
                        }else{
                            layer.open({
                                title: '看預測',
                                type: 1,
                                area : '90%',
                                offset: '5%',
                                shadeClose: true,
                                content: ret 
                            }, function () {
                                $(document).resize();
                            });
                            
                            Form.api.bindevent($("#mform"),function (mthis, result, ret) {
                                var mload = layer.load();
                                let id = $('#id').val(); 
                                let line_user_id = $('#line_user_id').val();
                                let options = {
                                    url: Config.url.furl+'/index/liff/eventinfo', 
                                    data: {id: id, line_user_id: line_user_id},
                                    success: function (ret) {
                                        layer.close(mload);
                                        if(ret == 0){
                                            Layer.msg('發生錯誤, 請重啟視窗');
                                            setTimeout(function () {
                                                liff.closeWindow();
                                            }, 1000);
                                        }else{
                                            $('.layui-layer-content').html(ret);
                                        }
                                    },
                                    error: function (ret) {
                                        Layer.msg(ret.msg);
                                    }
                                };
                                $.ajax(options);
                            });
                        }
                    },
                    error: function (ret) {
                        Layer.msg(ret.msg);
                    }
                };
                $.ajax(options);
            });
            
            $(document).on("click", ".btn_setcode", function () {
                Toastr.error('請綁定代碼');
                $('.setcode_box').slideDown();
                $('.pred_box').slideUp();
            });

            $(document).on("click", ".btn_pred", function () {
                var mload = layer.load();
                let type = $(this).data('type');
                let id = $('#id').val(); 
                let line_user_id = $('#line_user_id').val();
                let options = {url: Config.url.api+'/user/getpred/id/'+id, data: {line_user_id: line_user_id, type: type}};
                Fast.api.ajax(options, function (mthis, result, ret) {
                    layer.close(mload);
                    let predstr = result.data.predstr;
                    let linemsg = result.data.linemsg;
                    // let freepred = result.data.freepred;
                    let lastpred = result.data.lastpred;
                    // $('.freepred_box').text(freepred+'次');
                    $('.lastpred_box').text(lastpred+'次');
                    if(type == 1){
                        $('.btn_type1').removeClass("btn_pred").addClass("btn_haspred").text("讓分已預測");
                        $('.pred_type1_str').html(predstr);
                        $('.pred_type1_box').show();
                    }else if(type == 2){
                        $('.btn_type2').removeClass("btn_pred").addClass("btn_haspred").text("大小已預測");
                        $('.pred_type2_str').html(predstr);
                        $('.pred_type2_box').show();
                    }
                    
                    liff.sendMessages([{
                        type: 'text',
                        text: linemsg
                    }]).then(() => {
                        // Layer.msg('message sent');
                    }).catch((err) => {
                        // console.log('error', err);
                        // Layer.msg('');
                    });
                },function (mthis, result, ret) {
                    layer.close(mload);
                    if(result.data == 'setcode'){
                        $('.setcode_box').slideDown();
                        $('.pred_box').slideUp();
                    }
                });
            });

            $(document).on("click", ".btn_haspred", function () {
                Toastr.success('預測已顯示在上方, 若有顯示錯誤請洽客服');
            });

            $(document).on("click", ".btn_nopred", function () {
                Toastr.error('預測次數已用完，請洽客服');
            });
            
            // $(document).on("click", ".btn_setcode", function () {
                // liff.sendMessages([{
                //     type: 'text',
                //     text: 'Hello, World!'
                // }]).then(() => {
                //     console.log('message sent');
                //     Layer.msg('message sent');
                // }).catch((err) => {
                //     console.log('error', err);
                //     Layer.msg('');
                // });
            // });

            $(document).on("click", ".btn_rank", function () {
                var mload = layer.load();
                let options = {
                    url: Config.url.furl+'/index/liff/rank', 
                    success: function (ret) {
                        layer.close(mload);
                        if(ret == 0){
                            Layer.msg('發生錯誤');
                        }else{
                            layer.open({
                                title: '排行榜',
                                type: 1,
                                area : '90%',
                                offset: '5%',
                                shadeClose: true,
                                content: ret 
                            }, function () {
                                $(document).resize();
                            });
                        }
                    },
                    error: function (ret) {
                        Layer.msg(ret.msg);
                    }
                };
                $.ajax(options);
            });
            
            $(document).on("click", ".analyst_btn", function () {
                var mload = layer.load();
                let id = $(this).data('id');
                let options = {
                    url: Config.url.furl+'/index/liff/analystinfo/id/'+id, 
                    success: function (ret) {
                        layer.close(mload);
                        if(ret == 0){
                            Layer.msg('發生錯誤');
                        }else{
                            layer.open({
                                title: '分析師',
                                type: 1,
                                area : '90%',
                                offset: '5%',
                                shadeClose: true,
                                content: ret 
                            }, function () {
                                $(document).resize();
                            });
                        }
                    },
                    error: function (ret) {
                        layer.close(mload);
                        Layer.msg(ret.msg);
                    }
                };
                $.ajax(options);
            });
            
            
            $(document).on("click", ".btn_analyst_pred", function () {
                let id = $(this).data('id');
                let line_user_id = $('#line_user_id').val();

                let options = {url: Config.url.api+'/user/getanalystpred/id/'+id, data: {line_user_id: line_user_id}};
                Fast.api.ajax(options, function (mthis, result, ret) {
                    let eid = result.data.eid; 
                    let linemsg = result.data.linemsg;
                    liff.sendMessages([{
                        type: 'text',
                        text: linemsg
                    }]).then(() => {
                        // Layer.msg('message sent');
                    }).catch((err) => {
                        // console.log('error', err);
                        // Layer.msg('');
                    });
                    let options2 = {
                        url: Config.url.furl+'/index/liff/eventinfo', 
                        data: {id: eid, line_user_id: line_user_id},
                        success: function (ret) {
                            if(ret == 0){
                                Layer.msg('發生錯誤, 請重啟視窗');
                                setTimeout(function () {
                                    liff.closeWindow();
                                }, 1000);
                            }else{
                                layer.open({
                                    title: '看預測',
                                    type: 1,
                                    area : '90%',
                                    offset: '5%',
                                    shadeClose: true,
                                    content: ret 
                                }, function () {
                                    $(document).resize();
                                });
                                
                                Form.api.bindevent($("#mform"),function (mthis, result, ret) {
                                    let id = $('#id').val(); 
                                    let line_user_id = $('#line_user_id').val();
                                    let options3 = {
                                        url: Config.url.furl+'/index/liff/eventinfo', 
                                        data: {id: id, line_user_id: line_user_id},
                                        success: function (ret) {
                                            if(ret == 0){
                                                Layer.msg('發生錯誤, 請重啟視窗');
                                                setTimeout(function () {
                                                    liff.closeWindow();
                                                }, 1000);
                                            }else{
                                                $('.layui-layer-content').html(ret);
                                            }
                                        },
                                        error: function (ret) {
                                            Layer.msg(ret.msg);
                                        }
                                    };
                                    $.ajax(options3);
                                });
                            }
                        },
                        error: function (ret) {
                            Layer.msg(ret.msg);
                        }
                    };
                    $.ajax(options2);
                },function (mthis, result, ret) {
                    if(result.data == 'setcode'){
                        $('.setcode_box').slideDown();
                        $('.pred_box').slideUp();
                    }
                });
            });
        },
        // analystinfo:function(){

        // },
        liffinit:function(callback){
            liff.init({liffId: Config.site.liffid}).then(() => {
                var liffContext = liff.getContext();
                if(Config.suid != '----'){
                    liffContext.userId = Config.suid;
                }
                if(liffContext.userId){
                    $('#line_user_id').val(liffContext.userId);
                    if($('#form_line_user_id')) $('#form_line_user_id').val(liffContext.userId);
                    $('#get_uid_loding').hide();
                    $('#content-container').show();
                    
                    if (typeof callback === 'function') {
                        callback(liffContext.userId);
                    }
                }else{
                    $('#error_msg').text('發生錯誤');
                    Layer.msg('發生錯誤');
                }
            });
            $(document).on("click", ".panel-body .checkbox label", function () {
                $(this).closest('.checkbox').find('label').removeClass('active');
                $(this).closest('.checkbox').find('label').each(function(){
                    if($(this).find('input').prop("checked")){
                        $(this).addClass('active');
                    }
                });
            });
            $(document).on("click", ".panel-body .radio label", function () {
                $(this).closest('.radio').find('label').removeClass('active');
                $(this).closest('.radio').find('label').each(function(){
                    if($(this).find('input').prop("checked")){
                        $(this).addClass('active');
                    }
                });
            });
        }
    };
    return Controller;
});
