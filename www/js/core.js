var latoAfterAjaxStart = [];

$(function() {
    $("#main-progress-loader").hide();
    
    $.nette.ext('ajax-loader', {   
        before: function (jqXHR, settings) {
            $("#main-progress-loader").show();
        },
        success: function (data) {
            $("#main-progress-loader").hide();
            $('.dropdown-button').dropdown();
            Materialize.updateTextFields();
        },
        error: function(data) {
            $("#main-progress-loader").hide();
        }
    });
    
    
    latoShowUpFlashMessages();

    /*
     * Obecná funckce pro skrytí modalu po správném odeslání
     */
    /*
    latoAddAfterStartMethod({
        submitClass: 'hide-modal-ajax-submit',
        beginFunction: function(settings) {
            $('#full-screen-loader-modal').modal('open');
            return $(settings.nette.ui).closest('div.modal');
        },
        doneFunction: function(data, beforeParam) {
            if(data.invalidForm === undefined || !data.invalidForm) {
                beforeParam.modal('close');
            }
            $('#full-screen-loader-modal').modal('close');
        }
    });
    */
    

    $.nette.ext('hideAjaxSubmitPopup', {
        start: function (jqXHR, settings) {
            if(settings.nette !== undefined && $(settings.nette.ui).hasClass('hide-modal-ajax-submit')) { 
                $('#full-screen-loader-modal').modal('open');
                var modal = $(settings.nette.ui).closest('div.modal');
                jqXHR.done(function( data, textStatus, jqXHR ) {
                    $('#full-screen-loader-modal').modal('close');     
                    if(data.invalidForm === undefined || !data.invalidForm) {
                        modal.modal('close');
                    }
                });
            }
            /*
            if(settings.nette !== undefined && $(settings.nette.ui).data('show-popup-after')) {
                showLoader();
                $popup = $($(settings.nette.ui).data('show-popup-after'));
                jqXHR.done(function( data, textStatus, jqXHR ) {
                    hideLoader()
                    if(data.invalidForm === undefined || !data.invalidForm) {
                        $popup.trigger('show');
                    }
                });
            }
            */
        }
    });
 
    /*  
    $.nette.ext('afterStartAjax', {
        start: function (jqXHR, settings) {
            var doneFunction = [];
            if(settings.nette !== undefined) {
                for(var i=0; i<(latoAfterAjaxStart.length); i++) {
                    if($(settings.nette.ui).hasClass(latoAfterAjaxStart[i].submitClass)) {
                        var beforeParam = latoAfterAjaxStart[i].beginFunction(settings);
                        doneFunction.push(latoAfterAjaxStart[i].doneFunction);
                        jqXHR.done(function( data, textStatus, jqXHR ) {
                            for(var k=0; k<(doneFunction.length); k++) {
                                doneFunction[k](data, beforeParam);
                            }
                        });
                    }
                }
            }
        }
    });
    */
});

function latoReloadMessageWall()
{
    $('.masonry-grid').masonry('destroy');
    $('.masonry-grid').masonry({
        itemSelector: '.grid-item',
    });
}

function latoShowAllComments(comment) {
    $(comment).closest(".card-comments").find(".comments-content .comment-row").show();
    $(comment).closest(".card-comments").find(".comments-content .comment-row .comment-text").removeClass('truncate');
    latoReloadMessageWall();
}

function latoShowUpFlashMessages() {
    $("#flashMessagesWrapper .flash").each(function(index, value){
        Materialize.toast($(value).text(), 4000, $(value).data('type'));
        $(this).remove();
    });
}

function latoLoadDatePicker() {
    $('.datepicker').datepicker({
        selectMonths: true, // Creates a dropdown to control month
        selectYears: 15, // Creates a dropdown of 15 years to control year
        container: 'body',
        // Strings and translations
        monthsFull: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
        monthsShort: ['Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čer', 'Črv', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'],
        weekdaysFull: ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
        weekdaysShort: ['Ned', 'Pon', 'Úte', 'Stř', 'Čtv', 'Pát', 'Sob'],
        showMonthsShort: undefined,
        showWeekdaysFull: undefined,

        // Buttons
        today: 'Dnes',
        clear: 'Smazat',
        close: 'Zavřít',

        // Accessibility labels
        labelMonthNext: 'Další měsíc',
        labelMonthPrev: 'Předchozí měsíc',
        labelMonthSelect: 'Vyberte měsíc',
        labelYearSelect: 'Vyberte rok',

        // Formats
        format: 'dd. mm. yyyy'
    });
}

function latoAddAfterStartMethod(data)
{  
    var exist = false;
    for(var i=0; i<(latoAfterAjaxStart.length); i++) {
        if(latoAfterAjaxStart[i].submitClass === data.submitClass) {
            exist = true;
        }
    }
    if(!exist) {
        latoAfterAjaxStart.push(data);
    }
}
