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
                var mload = layer.load();
                let id = $(this).data('id');
                let cat_id = $(this).data('cat_id');

                let options = {url: Config.url.api+'/user/buyAnalystPred/id/'+id+'/cat_id/'+cat_id};
                Fast.api.ajax(options, function (mthis, result, ret) {
                    setTimeout(function () {
                        layer.close(mload);
                        location.reload();
                    }, 1000);
                },function (mthis, result, ret) {
                    layer.close(mload);
                });
            });
        }
    };
    return Controller;
});
