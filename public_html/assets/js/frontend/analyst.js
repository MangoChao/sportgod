define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {
    var validatoroptions = {
        invalid: function (form, errors) {
            $.each(errors, function (i, j) {
                Layer.msg(j);
            });
        }
    };
    var Controller = {
        index: function () {
        },
        profile: function () {
            
            $(document).on("click", ".btn_buy_analyst_pred", function () {
                let id = $(this).data('id');
                let cat_id = $(this).data('cat_id');
                let sdate = $(this).data('sdate');
                layer.confirm('確定購買預測?', {
                    title: false,
                    closeBtn: false,
                    shadeClose:true,
                    btn: ['確定','取消'] 
                }, function(index){
                    layer.close(index);
                    var mload = layer.load();

                    let options = {url: Config.url.api+'/user/buyAnalystPred/id/'+id+'/cat_id/'+cat_id+'/sdate/'+sdate};
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
        }
    };
    return Controller;
});
