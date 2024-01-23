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
            $("a.a_title").hover(function(){
                let k = $(this).data('k');
                let a_img_box = $(this).closest('.banner').find('.a_img_box');
                a_img_box.each(function(){
                    if($(this).data('k') != k){
                        $(this).hide();
                    }else{
                        $(this).show();
                    }
                });
            });
        },
        teach: function () {
            
        },
        teach_detail: function () {
            
        }
    };
    return Controller;
});
