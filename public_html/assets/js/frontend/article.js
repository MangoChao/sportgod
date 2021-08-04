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
        detail: function () {
            $(document).on("click", "#btn_add_msg", function () {
                let article_id = $('#article_id').val();
                let msg_content = $('#msg_content').val();
                let options = {url: Config.url.api+'/article/addmsg', data: {id:article_id,msg:msg_content}};
                if(msg_content == ""){
                    Toastr.error('留言不可為空');
                }else{
                    Fast.api.ajax(options, function (mthis, data, ret) {
                        location.reload();
                    });
                }
            });
        }
    };
    return Controller;
});
