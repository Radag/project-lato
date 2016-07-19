$(function() {
    $(".progress").hide();
    
    $.nette.ext('ajax-loader', {   
        before: function (jqXHR, settings) {
            $(".progress").show();
        },
        success: function (data) {
            $(".progress").hide();
        },
        error: function(data) {
            $(".progress").hide();
        }
    });
});