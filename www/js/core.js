$(function() {
    $("#main-progress-loader").hide();
    
    $.nette.ext('ajax-loader', {   
        before: function (jqXHR, settings) {
            $("#main-progress-loader").show();
        },
        success: function (data) {
            $("#main-progress-loader").hide();
            $('.dropdown-button').dropdown();
        },
        error: function(data) {
            $("#main-progress-loader").hide();
        }
    });
    
    
   latoShowUpFlashMessages();
});

function latoShowUpFlashMessages() {
    $("#flashMessagesWrapper .flash").each(function(index, value){
        Materialize.toast($(value).text(), 4000, $(value).data('type'));
    });
}

function latoLoadDatePicker() {
    $('.datepicker').pickadate({
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
