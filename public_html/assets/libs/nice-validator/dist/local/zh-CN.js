/*********************************
 * Themes, rules, and i18n support
 * Locale: Chinese; 中文
 *********************************/
(function(factory) {
    typeof module === "object" && module.exports ? module.exports = factory( require( "jquery" ) ) :
    typeof define === 'function' && define.amd ? define(['jquery'], factory) :
    factory(jQuery);
}(function($) {

    /* Global configuration
     */
    $.validator.config({
        //stopOnError: true,
        //focusCleanup: true,
        //theme: 'yellow_right',
        //timely: 2,

        // Custom rules
        rules: {
            digits: [/^\d+$/, "請填寫數字"]
            ,decimal: [/^(([1-9]{1}\d*)|(0{1}))(\.\d{0,1})?$/, "請填寫數字或1位小數"]
            ,letters: [/^[a-z]+$/i, "請填寫字母"]
            ,date: [/^\d{4}-\d{2}-\d{2}$/, "請填寫有效日期，格式:yyyy-mm-dd"]
            ,time: [/^([01]\d|2[0-3])(:[0-5]\d){1,2}$/, "請填寫有效時間，00:00到23:59之間"]
            ,email: [/^[\w\+\-]+(\.[\w\+\-]+)*@[a-z\d\-]+(\.[a-z\d\-]+)*\.([a-z]{2,4})$/i, "請填寫有效的信箱"]
            ,url: [/^(https?|s?ftp):\/\/\S+$/i, "請填寫有效的網址"]
            ,qq: [/^[1-9]\d{4,}$/, "請填寫有效的QQ"]
            ,IDcard: [/^\d{6}(19|2\d)?\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)?$/, "請填寫有效的身分證號碼"]
            ,tel: [/^(?:(?:0\d{2,3}[\- ]?[1-9]\d{6,7})|(?:[48]00[\- ]?[1-9]\d{6}))$/, "請填寫有效的電話號碼"]
            ,mobile: [/^09\d{2}-?\d{3}-?\d{3}$/, "請填寫有效的手機"]
            ,zipcode: [/^\d{6}$/, "請檢查郵遞區號格式"]
            ,chinese: [/^[\u0391-\uFFE5]+$/, "請填寫中文"]
            ,username: [/^[\S]{2,10}$/, "請填寫2-10位字元、底線，不能包含空格"]
            ,nickname: [/^[\S]{2,10}$/, "請填寫2-10位字元、底線，不能包含空格"]
            ,password: [/^[\S]{6,16}$/, "請填寫6-16位字符，不能包含空格"]
            ,sitename: [/^[\S]{2,7}$/, "請填寫2-7位字元，不能包含空格"]
            ,accept: function (element, params){
                if (!params) return true;
                var ext = params[0],
                    value = $(element).val();
                return (ext === '*') ||
                       (new RegExp(".(?:" + ext + ")$", "i")).test(value) ||
                       this.renderMsg("只接受{1}后缀的文件", ext.replace(/\|/g, ','));
            }
            
        },

        // Default error messages
        messages: {
            0: "此處",
            fallback: "{0}格式不正确",
            loading: "驗證中...",
            error: "網路異常",
            timeout: "請求超時",
            required: "{0}不能為空",
            remote: "{0}已被使用",
            integer: {
                '*': "請填寫整數",
                '+': "請填寫正整數",
                '+0': "請填寫正整數或0",
                '-': "請填寫負整數",
                '-0': "請填寫負整數或0"
            },
            match: {
                eq: "{0}與{1}不一致",
                neq: "{0}與{1}不能相同",
                lt: "{0}必須小於{1}",
                gt: "{0}必須大於{1}",
                lte: "{0}不能大於{1}",
                gte: "{0}不能小於{1}"
            },
            range: {
                rg: "請填寫{1}到{2}的數",
                gte: "請填寫不小於{1}的數",
                lte: "請填寫最大{1}的數",
                gtlt: "請填寫{1}到{2}之間的數",
                gt: "請填寫小於{1}的數",
                lt: "請填寫小於{1}的數"
            },
            checked: {
                eq: "請選擇{1}項",
                rg: "請選擇{1}到{2}項",
                gte: "請至少選擇{1}項",
                lte: "請最多選擇{1}項"
            },
            length: {
                eq: "請填寫{1}個字符",
                rg: "請填寫{1}到{2}個字符",
                gte: "請至少填寫{1}個字符",
                lte: "請最多填寫{1}個字符",
                eq_2: "",
                rg_2: "",
                gte_2: "",
                lte_2: ""
            }
        }
    });

    /* Themes
     */
    var TPL_ARROW = '<span class="n-arrow"><b>◆</b><i>◆</i></span>';
    $.validator.setTheme({
        'simple_right': {
            formClass: 'n-simple',
            msgClass: 'n-right'
        },
        'simple_bottom': {
            formClass: 'n-simple',
            msgClass: 'n-bottom'
        },
        'yellow_top': {
            formClass: 'n-yellow',
            msgClass: 'n-top',
            msgArrow: TPL_ARROW
        },
        'yellow_right': {
            formClass: 'n-yellow',
            msgClass: 'n-right',
            msgArrow: TPL_ARROW
        },
        'yellow_right_effect': {
            formClass: 'n-yellow',
            msgClass: 'n-right',
            msgArrow: TPL_ARROW,
            msgShow: function($msgbox, type){
                var $el = $msgbox.children();
                if ($el.is(':animated')) return;
                if (type === 'error') {
                    $el.css({left: '20px', opacity: 0})
                        .delay(100).show().stop()
                        .animate({left: '-4px', opacity: 1}, 150)
                        .animate({left: '3px'}, 80)
                        .animate({left: 0}, 80);
                } else {
                    $el.css({left: 0, opacity: 1}).fadeIn(200);
                }
            },
            msgHide: function($msgbox, type){
                var $el = $msgbox.children();
                $el.stop().delay(100).show()
                    .animate({left: '20px', opacity: 0}, 300, function(){
                        $msgbox.hide();
                    });
            }
        }
    });
}));
