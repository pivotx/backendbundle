
/**
 * Simple way to capitalize
 */
String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

/**
 * Simple way to trim
 */
String.prototype.trim = function() {
    return this.replace(/^([ \t\r\n]*)(.+?)([ \t\r\n]*)$/, '$2');
}



/**
 * Simplified modal setter
 */
function modalAll(title, text)
{
    var dm_el = $('#default-modal');

    $('h3', dm_el).html(title);
    $('div.modal-body', dm_el).html(text);
    $('a.btn', dm_el).hide().off('click');
    //$('a[data-modal-kind="close"]').show();

    return dm_el;
}

function modalMessageError(title, text)
{
    var dm_el = modalAll(title, text);
    var btn_el = $('a[data-modal-kind="ok"]', dm_el);

    btn_el.show();
    $(dm_el).modal();
}


var modal_progress = {
    timer: false,
    percentage: 0,
    to: 0,
    steps: 0
};

function modalProgressOpen(title, text)
{
    text += '<div class="progress progress-striped active"> <div class="bar" style="width: 60%;"></div> </div>';

    var dm_el = modalAll(title, text);

    $(dm_el).modal();
}

function modalProgressTicker()
{
    modal_progress.timer = false;

    var dm_el = $('#default-modal');

    $('.progress .bar', dm_el).width(Math.round(modal_progress.percentage) + '%');

    if (modal_progress.percentage < modal_progress.to) {
        modal_progress.percentage += modal_progress.steps;
        if (modal_progress.percentage > modal_progress.to) {
            modal_progress.percentage = modal_progress.to;
        }
        modal_progress.timer = setTimeout('modalProgressTicker()', 250);
    }

    if (modal_progress.percentage >= 100) {
        modalProgressClose();
    }
}

function modalProgressUpdate(percentage, percentage_to, mseconds)
{
    if (modal_progress.timer != false) {
        clearTimeout(modal_progress.timer);
        modal_progress.timer = false;
    }

    var dm_el = $('#default-modal');

    $('.progress .bar', dm_el).width(percentage + '%');

    if ((percentage_to > percentage) && (mseconds > 0)) {
        modal_progress.percentage = percentage;
        modal_progress.to         = percentage_to;
        modal_progress.steps      = (percentage_to - percentage) / (mseconds/250);

        modalProgressTicker();
    }
}

function modalProgressClose()
{
    if (modal_progress.timer != false) {
        clearTimeout(modal_progress.timer);
        modal_progress.timer = false;
    }

    $('#default-modal').modal('hide');
}

function modalDelete(title, text, delete_callback)
{
    var dm_el = modalAll(title, text);
    var can_el = $('a[data-modal-kind="cancel"]', dm_el);
    var del_el = $('a[data-modal-kind="delete"]', dm_el);

    del_el.on('click', function(e){
        e.preventDefault();

        $(dm_el).modal('hide');

        delete_callback();
    });

    can_el.show();
    del_el.show();
    $(dm_el).modal();
}

function modalConfirm(title, text, confirm_callback, cancel_callback)
{
    var dm_el = modalAll(title, text);
    var can_el = $('a[data-modal-kind="cancel"]', dm_el);
    var con_el = $('a[data-modal-kind="confirm"]', dm_el);

    $(dm_el).on('hidden', function(e){
        cancel_callback();
    });

    can_el.on('click', function(e){
        e.preventDefault();

        $(dm_el).modal('hide');
    });
    con_el.on('click', function(e){
        e.preventDefault();

        $(dm_el).modal('hide');

        confirm_callback();
    });

    can_el.show();
    con_el.show();
    $(dm_el).modal();
}


/**
 * Complicated (too) way to call a specific event for a 'dynamic' object
 */

/**
 *
 */
function triggerElementLoad()
{
    $('#elementload').trigger('elementloader');
}

$(window).load(function(){
    triggerElementLoad();
});

$(document).ready(function(){
    $('body').append('<div id="elementload" style="display:none"></div>');

    $(document).on('elementloader', '#elementload', function(e){
        // @todo should not be hardcoded like this

        $('textarea.wysiwyg-redactor').trigger('elementload');
        $('div.execute-function').trigger('elementload');
    });
});
