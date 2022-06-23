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
        add: function () {
            Form.api.bindevent($("#luckyshare_form"), function (data) {
                location.href = Config.url.furl+"/index/luckyshare";
            });
        }
    };
    return Controller;
});
