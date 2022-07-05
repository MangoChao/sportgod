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
            layer.photos({
                photos: '.luckyshare_list'
                ,anim: 5 //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
              }); 
        },
        add: function () {
            Form.api.bindevent($("#luckyshare_form"), function (data) {
                location.href = Config.url.furl+"/index/luckyshare";
            });
        }
    };
    return Controller;
});
