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
            
            $(document).on("click", ".btn_analyst_pred_all", function () {
                var mload = layer.load();
                let id = $(this).data('id');

                let options = {url: Config.url.api+'/user/getanalystpredall/id/'+id};
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
