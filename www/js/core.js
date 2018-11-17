var latoAfterAjaxStart = [];

$(function() {
    $("#main-progress-loader").hide();
    
    $(".modal-trigger").on('click', function(e) {
        var input =  $($(this).attr('href')).find("input[type='text']");
        if(input.length > 0) {
            setTimeout(function() { 
                input.get(0).focus() 
            }, 300);
        }        
    });
    
    if($("form.leave-warning").length > 0) {
        var formModified = false;
        $('form.leave-warning').on('keyup change paste', 'input, select, textarea', function(){
            formModified = true;
        });
        $('form.leave-warning').on('submit', function(event) {
            formModified = false;
        });

        window.onbeforeunload = function(){
            if(formModified) {
                return 'Máte neuložené změny. Opravdu chcete stránku opustit ?';
            }
        };
    }
    
    
    $(".delete-confirm").on('click', function(e) {
        e.preventDefault();
        $("#delete-confirm-modal .delete-title").text($(this).data('title'));
        $("#delete-confirm-modal .delete-text").html($(this).data('text'));
        $("#delete-confirm-modal input[name='confirm-delete-url']").val($(this).data('url'));
        $("#delete-confirm-modal button.confirm").off('click').on('click', function(e) {
            $(this).html($("#button-loader .preloader-wrapper").clone());
            $(this).prop('disabled', true);
            window.location.href = $("#delete-confirm-modal input[name='confirm-delete-url']").val();
        });
        $("#delete-confirm-modal").modal('open');
    });
    
    $("form:not(.ajax) button[type='submit']").on('click', function() {
        $(this).parents('form').submit();
        $(this).html($("#button-loader .preloader-wrapper").clone());
        $(this).prop('disabled', true);
    });    
    
    $.nette.ext('ajax-loader', {   
        before: function (jqXHR, settings) {
            $("#main-progress-loader").show();
        },
        success: function (data) {
            $("#main-progress-loader").hide();
            $('.dropdown-trigger').dropdown();
            $('.tooltipped').tooltip();
            $('select').formSelect();
            if(data.reloadModal) {
                $('.modal').modal();
            }
            M.updateTextFields();
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
           
            if(settings.nette !== undefined && $(settings.nette.ui).is('button')) {
                var buttonText = $(settings.nette.ui).text();
                $(settings.nette.ui).html($("#button-loader .preloader-wrapper").clone());
                $(settings.nette.ui).prop('disabled', true);
                jqXHR.done(function( data, textStatus, jqXHR ) {   
                    if(data.invalidForm !== undefined) {
                        $(settings.nette.ui).html(buttonText);
                        $(settings.nette.ui).prop('disabled', false);
                    }
                });
            } 
            
            if(settings.nette !== undefined && $(settings.nette.ui).hasClass('hide-modal-ajax-submit')) {
                var modal = $(settings.nette.ui).closest('div.modal');
                jqXHR.done(function( data, textStatus, jqXHR ) {
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

function startModalLoading(ele)
{   
    $(ele).modal('open');
    $(ele + ' div').hide();
    $(ele).addClass('modal-loader');
    $(ele).append($(".full-screen-loader-modal").clone());
    
}

function showModalLoading(ele)
{
    $(ele + ' div').fadeIn();
    $(ele).removeClass('modal-loader');
    $(ele + ' .full-screen-loader-modal').remove();
}

function datepicker()
{
    $('.datepicker').datepicker({
        container: '#date-picker-container',
        format: "d. m. yyyy",
        showClearBtn: false,
        firstDay: 1,
        i18n: {
            cancel:	'Zrušit',
            clear:	'Smazat',
            done:	'Ok',
            previousMonth:	'‹',
            nextMonth:	'›',
            months: [
              'Leden',
              'Únor',
              'Březen',
              'Duben',
              'Květen',
              'červen',
              'Červenec',
              'Srpen',
              'Září',
              'Říjen',
              'Listopad',
              'Prosinec'
            ],
            monthsShort: [
              'Led',
              'Úno',
              'Bře',
              'Dub',
              'Kvě',
              'Čer',
              'Čvn',
              'Srp',
              'Zář',
              'Říj',
              'Lis',
              'Pro'
            ],
            weekdays: [
              'Neděle',
              'Pondělí',
              'Úterý',
              'Středa',
              'Čtvrtek',
              'Pátek',
              'Sobota'
            ],
            weekdaysShort: [
              'Po',
              'Út',
              'St',
              'Čt',
              'Pá',
              'So',
              'Ne'
            ],
            weekdaysAbbrev:	['N','P','Ú','S','Č','P','S']
        }
    });
}


function latoReloadMessageWall()
{
//    $('.masonry-grid').masonry('destroy');
//    $('.masonry-grid').masonry({
//        itemSelector: '.grid-item',
//    });masonry
}

function latoShowAllComments(comment) {
    $(comment).closest(".card-comments").find(".comments-content .comment-row").show();
    $(comment).closest(".card-comments").find(".comments-content .comment-row .comment-text").removeClass('truncate');
    latoReloadMessageWall();
}

function latoShowUpFlashMessages() {
    $("#flashMessagesWrapper .flash").each(function(index, value){
        M.toast({html: $(value).text(), displayLength: 4000, classes: $(value).data('type')});
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
