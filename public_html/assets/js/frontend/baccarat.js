define(['jquery', 'bootstrap', 'frontend', 'form', 'template'], function ($, undefined, Frontend, Form, Template) {
    var validatoroptions = {
        invalid: function (form, errors) {
            $.each(errors, function (i, j) {
                Layer.msg(j);
            });
        }
    };
    var Controller = {
        checkout: function () {
        },
        confirmpage: function () {
            Form.api.bindevent($("#confirm_form"));
        }
    };
    return Controller;
});
