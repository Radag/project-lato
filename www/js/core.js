$(function() {
    $("#main-progress-loader").hide();
    
    $.nette.ext('ajax-loader', {   
        before: function (jqXHR, settings) {
            $("#main-progress-loader").show();
        },
        success: function (data) {
            $("#main-progress-loader").hide();
        },
        error: function(data) {
            $("#main-progress-loader").hide();
        }
    });
    
    $("#flashMessagesWrapper .flash").each(function(index, value){
        Materialize.toast($(value).text(), 4000);
    });
   
});
