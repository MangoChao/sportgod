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
            // Form.api.bindevent($("#add_msg_form"));

            // $(document).on("click", "#btn_add_msg", function () {
            //     let article_id = $('#article_id').val();
            //     let msg_content = $('#msg_content').val();
            //     let options = {url: Config.url.api+'/article/addmsg', data: {id:article_id,msg:msg_content}};
            //     if(msg_content == ""){
            //         Toastr.error('留言不可為空');
            //     }else{
            //         Fast.api.ajax(options, function (mthis, data, ret) {
            //             location.reload();
            //         });
            //     }
            // });
            
            $(document).on("click", ".btn_set_fav", function () {
                let atag = $(this);
                let article_id = $(this).data('id');
                let options = {url: Config.url.api+'/article/setfav', data: {id:article_id,type:2}};
                Fast.api.ajax(options, function (mthis, data, ret) {
                    if(data.data.active == 1){
                        atag.addClass('active');
                    }else{
                        atag.removeClass('active');
                    }
                });
            });

            $(document).on("click", ".btn_dotnet", function () {
                let article_id = $(this).data('id');
                let options = {url: Config.url.api+'/article/dotnet', data: {id:article_id}};
                
                layer.confirm('確定贊助100點?', {
                    title: false,
                    closeBtn: false,
                    shadeClose:true,
                    btn: ['確定','取消'] 
                }, function(index){
                    layer.close(index);
                    var mload = layer.load();
                    Fast.api.ajax(options, function (mthis, result, ret) {
                        layer.close(mload);
                    },function (mthis, result, ret) {
                        layer.close(mload);
                    });
                });
            });

        }
    };
    return Controller;
});
