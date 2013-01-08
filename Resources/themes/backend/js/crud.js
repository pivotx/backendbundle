
var crud_tables = {};
var crud_last_toggle = -1;
var crud_clear_selection = true;

var crud_unique_timer = false; // @todo we support only 1 slug update


/**
 * Activate the right text and update the count
 */
function updateTriSelect(el, value)
{
    var html = '';

    switch (value) {
        case 0:
            $('.tri-1, .tri-x', el).hide();
            html = $('.tri-0', el).show().html();
            break;
        case 1:
            $('.tri-0, .tri-x', el).hide();
            html = $('.tri-1', el).show().html();
            break;
        default:
            $('.tri-0, .tri-1', el).hide();
            $('.tri-x .count', el).html(value);
            html = $('.tri-x', el).show().html();
            break;
    }

    return html;
}

function updateCrudSelectionFromStorage(crud_id, crud_table_el)
{
    if (!sessionStorage) {
        return false;
    }

    var storage = sessionStorage;

    if (crud_table = storage.getItem('crud-table')) {
        var selection = crud_table.split(';');
        crud_tables[crud_id].selection = selection;
        crud_tables[crud_id].invisible_count = 0;

        visible_count = 0;
        for(var i=0; i < selection.length; i++) {
            var el = $('input[data-id="'+selection[i]+'"]', crud_table_el);
            if (el.length > 0) {
                el.attr('checked',true);
                visible_count++;
            }
        }

        crud_tables[crud_id].invisible_count = selection.length - visible_count;

        if (crud_tables[crud_id].invisible_count > 0) {
            var el = $('.crud-selection-'+crud_id+' .invisible-selection');
            el.show();
            updateTriSelect(el, crud_tables[crud_id].invisible_count);
        }
    }
}

function updateCrudSelectionToStorage(crud_id)
{
    if (!sessionStorage) {
        return false;
    }

    var storage = sessionStorage;

    storage.setItem('crud-table', crud_tables[crud_id].selection.join(';'));
}

function getCrudLinkWithSelection(href, crud_id)
{
    crud_clear_selection = false;

    return href + '&table-selection=' + crud_tables[crud_id].selection.join(';');
}

/**
 * Update the CRUD Meta information part
 * Update the toggle-checkbox
 */
function updateCrudSelectionMeta(crud_id)
{
    $('.crud-meta[data-crud-table="'+crud_id+'"]').each(function(){
        var html = updateTriSelect($('.crud-selection-'+crud_id+' .visible-selection'), crud_tables[crud_id].selection.length);

        // @todo the selection-summary text is just the number of items selected for now
        $('.crud-selection-summary-'+crud_id)
            .html(html)
            .attr('ids', crud_tables[crud_id].selection.join(';'))
        ;

        if (crud_tables[crud_id].selection.length > 0) {
            $('.crud-selection-'+crud_id+' a.btn').removeClass('disabled');
            $('.crud-selection-'+crud_id+' .show-when-selection').show();
        }
        else {
            $('.crud-selection-'+crud_id+' a.btn').removeClass('disabled').addClass('disabled');
            $('.crud-selection-'+crud_id+' .show-when-selection').hide();
        }
    });

    $('table[data-crud-table="'+crud_id+'"]').each(function(){
        if (crud_tables[crud_id].selection.length == $(this).attr('data-crud-page-length')) {
            $('thead th.select-checkbox input', this).attr('checked',true);
        }
        else {
            $('thead th.select-checkbox input', this).attr('checked',false);
        }
    });

    updateCrudSelectionToStorage(crud_id);
}

/**
 * Update files information
 */
function updateCrudFilesFieldRow(field_row_el)
{
    var files = [];
    $('ul.files li', field_row_el).each(function(){
        var data = $(this).attr('data-json');
        if (typeof(data) != 'undefined') {
            var json = $.parseJSON($(this).attr('data-json'));
            if (json.valid) {
                files.push(json);
            }
        }
    });
    var json = JSON.stringify(files, null, 2);
    $('input', field_row_el).each(function(){
        var id = $(this).attr('id');
        if ((new String(id)).indexOf('filesinfo') > 0) {
            $(this).val(json);
        }
    });
}


/**
 * Update file information
 */
function updateCrudFileFieldRow(field_row_el, args, file_info, progress, filestatus)
{
    if (!args.multiple) {
        $('ul.files li[class!="no-file"]', field_row_el).remove();
    }

    console.log(file_info);
    console.log(file_info.message, filestatus);

    var file_el = $('li[data-name="'+file_info.name+'"]', field_row_el);

    if (file_el.length == 0) {
        var html = $('li.no-file', field_row_el).html();
        html = '<li data-name="' + file_info.name + '">' + html + '</li>';

        $('ul.files', field_row_el).prepend(html);

        file_el = $('li[data-name="'+file_info.name+'"]', field_row_el);

        $('span.name', file_el).html(file_info.name);
    }

    var json = JSON.stringify(file_info, null, 2);
    $(file_el).attr('data-json', json);

    if (filestatus < 200) {
        $('div.bar', file_el).css('width', progress+'%');
    }
    else {
        $('div.progress', file_el).hide();

        if ((filestatus == 200) && (file_info.valid)) {
            $('span.status', file_el)
                .addClass('label label-success')
                .html(args.textDone)
                ;

            var title_input_el = $(field_row_el).closest('form').find('#form_title');
            if ((title_input_el.length > 0) && (title_input_el.val() == '')) {
                // set the default title to the filename
                $(title_input_el).val(file_info.name);
            }
        }
        else {
            if (file_info.message) {
                showNotification({
                    title: 'Upload error',
                    text: file_info.message,
                    type: 'error'
                });
            }

            $('span.status', file_el)
                .addClass('label label-important')
                .html(args.textFail)
                ;
        }

        $(file_el).attr('data-name', '');
    }

    updateCrudFilesFieldRow(field_row_el);
}

function activateJQueryFileUpload(args)
{
    var files = [];
    var input_el = $('input[name="' + args.name + '"]');
    var field_row_el = $(input_el).closest('.crud-field-row');
    var options = {
        url: args.url,
        dropZone: $('div.file-drop-target', field_row_el),
        dataType: 'json',
        autoUpload: true
    }
    args.multiple = true;
    if (!$(input_el).attr('multiple')) {
        args.multiple = false;
        options.maxNumberOfFiles = 1;
    }
    input_el.fileupload(options);
    input_el.bind('fileuploadadd', function (e, data){
        var file_name = data.files[0].name;
        var progress = parseInt(data.loaded / data.total * 100, 10);

        $(document).trigger('dragleave');

        var file_info = { 'name': file_name };
        updateCrudFileFieldRow(field_row_el, args, file_info, 10, 100);

    });
    input_el.bind('fileuploadprogress', function (e, data){
        var file_name = data.files[0].name;
        var progress = new String(parseInt(data.loaded / data.total * 80, 10) + 10);
        var file_info = { 'name': file_name };
        updateCrudFileFieldRow(field_row_el, args, file_info, progress, 100);
    });
    input_el.bind('fileuploaddone', function (e, data){
        var file_info = data.result[0];
        updateCrudFileFieldRow(field_row_el, args, file_info, 100, 100);
        setTimeout(function(){
            updateCrudFileFieldRow(field_row_el, args, file_info, 100, 200);
        }, 1500);

        /*
        // we should not do this, instead we should remove the previous file
        if (options.maxNumberOfFiles == 1) {
            $('li.no-file', files_el).addClass('crud-hide');
            $(input_el).closest('span.btn').addClass('disabled');
            $(field_el).parent().find('span.btn').addClass('disabled');
        }
        //*/
    });
    input_el.bind('fileuploadfail', function (e, data){
        var file_name = data.files[0].name;
        var file_info = { 'name': file_name };
        updateCrudFileFieldRow(field_row_el, args, file_info, 100, 500);
    });

    if ($('span.crud-fileselection', field_row_el).length > 0) {
        activateFileSelection(field_row_el, args);
    }

    updateCrudFilesFieldRow(field_row_el);
}

function activateFileSelection(field_row_el, args)
{
    var field_el = $('.crud-fileselection', field_row_el);
    var selection_el = $('div.file-selection[data-selection-target="' + args.name + '"]');
    var drop_target_el = $('div.file-drop-target[data-drop-target="' + args.name + '"]');

    $(field_el).on('click', function(e){
        if (!$(selection_el).is(':visible')) {
            $(selection_el).show();
            $(drop_target_el).hide();
        }
        else {
            $(selection_el).hide();
            $(drop_target_el).show();
        }
    });

    $('span.file-add', field_row_el).on('click', function(e){
        var file_info = {};
        file_info.valid   = true;
        file_info.id      = $(this).attr('data-id');
        file_info.name    = $(this).attr('data-name');
        file_info.nessage = 'Added';

        updateCrudFileFieldRow(field_row_el, args, file_info, 100, 200);

        $(selection_el).hide();
        $(drop_target_el).show();
    });
}


/**
 * Handler to update the unique value
 * 
 * @todo only works for input's as sources
 * @todo fix the URL thing!
 * @todo somehow transport join rules
 */
function updateCrudUniqueField(unique_el, field, sources)
{
    var post = {};
    var url = '';
    var automatic = $(unique_el).attr('data-automatic');

    if (automatic == 'keep') {
        // no need to do anything
        return;
    }

    for(var i=0; i < sources.length; i++) {
        var source = sources[i];
        post[source] = $('input[name="form['+source+']"]').val();
    }

    var my_location = new String(document.location);
    var re = my_location.match(/(.+)\/[0-9]+$/);
    var url = re[1] + '/suggest/' + field;

    $.ajax({
        type: 'POST',
        url: url,
        data: post,
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            if (data.length > 0) {
                var value = data[0];

                $('.value', unique_el).html(value);
                $('input[type="text"]', unique_el).val(value);
            }
        },
        error: function(data, textStatus, jqXHR){
        }
    });
}

/**
 * Handle to verify unique value
 */
function verifyCrudUniqueField(unique_el, value)
{
    var field = $(unique_el).attr('data-field');
    var post = {};

    post[field] = value;

    var my_location = new String(document.location);
    var re = my_location.match(/(.+)\/([0-9]+)$/);
    var url = re[1] + '/suggest/' + field;

    post['id'] = re[2];

    $.ajax({
        type: 'POST',
        url: url,
        data: post,
        dataType: 'json',
        success: function(data, textStatus, jqXHR){
            if (data.length > 0) {
                var suggested_value = data[0];
                if (suggested_value != value) {
                    showNotification({
                        title: 'Suggestion update',
                        text: 'The value you entered was not unique.<br/>We changed it slightly to be unique.<br/><br/>Old value: <strong>' + value + '</strong><br/>New value: <strong>' + suggested_value + '</strong>',
                        type:'info'}
                    );
                }
                $('.value-auto .value', unique_el).html(suggested_value);
                $('.value-edit input').val(suggested_value);
                $('.value-edit input').hide();
                $('.value-auto').show();
            }
        },
        error: function(data, textStatus, jqXHR){
        }
    });
}

/**
 * Add a handler to each field which can possible edit the unique field
 * 
 * @todo currently only works for input's as sources
 */
function fixCrudUniqueSources(form_el, unique_el, field, sources_text)
{
    var sources = sources_text.split(/ /);

    if (sources.length > 0) {
        for(var i=0; i < sources.length; i++) {
            var source = sources[i];

            $('input[name="form['+source+']"]').on('change keyup', function(e){
                if ($(this).attr('data-previous') != $(this).val()) {
                    if (crud_unique_timer !== false) {
                        clearTimeout(crud_unique_timer);
                    }

                    crud_unique_timer = setTimeout(function(){ updateCrudUniqueField(unique_el, field, sources); }, 500);

                    $(this).attr('data-previous', $(this).val());
                }
            });
        }

        $('input[name="form['+sources[0]+']"]').trigger('change');
    }
}

$(window).on('unload', function(e){
    if ((crud_clear_selection) && (sessionStorage)) {
        // clearing selection unless value was cleared
        sessionStorage.removeItem('crud-table');
    }
});

$(function(){
    // Handler for .ready() called.

    $('table.crud').each(function(){
        var table_el = this;
        var crud_id = $(this).attr('data-crud-table');
        crud_tables[crud_id] = {
            selection: [],
            invisible_count: 0,
            last_toggle: -1
        }

        // clear all (for browsers that 'cache' this)
        $('td.select-checkbox input',this).attr('checked',false);

        updateCrudSelectionFromStorage(crud_id, table_el);

        // count all
        var length = $('td.select-checkbox input',this).length;
        $(this).attr('data-crud-page-length',length);

        $('thead th.select-checkbox input',this).on('click',function(e){
            var checked = $(this).is(':checked');

            crud_tables[crud_id].selection = [];
            $('tbody td.select-checkbox input',table_el).each(function(){
                var id = $(this).attr('data-id');
                $(this).attr('checked', checked);
                if (checked) {
                    crud_tables[crud_id].selection.push(id);
                }
            });
            updateCrudSelectionMeta(crud_id);
        });

        $('tbody td.select-checkbox input',this).on('click',function(e){
            var id = $(this).attr('data-id');
            if ($(this).is(':checked')) {
                crud_tables[crud_id].selection.push(id);
            }
            else {
                var idx = -1;
                for(var i=0; i < crud_tables[crud_id].selection.length; i++) {
                    if (crud_tables[crud_id].selection[i] == id) {
                        idx = i;
                        break;
                    }
                }
                if (idx >= 0) {
                    crud_tables[crud_id].selection.splice(idx,1);
                }
            }

            updateCrudSelectionMeta(crud_id);

            crud_tables[crud_id].last_toggle = id;
        });

        updateCrudSelectionMeta(crud_id);

        $('a.crud-action', this).on('click', function(e){
            var action_el = this;

            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: $(action_el).attr('href'),
                dataType: 'json',
                success: function(data, textStatus, jqXHR){
                },
                error: function(data, textStatus, jqXHR){
                }
            });
        });

        $('th.sort-by', this).each(function(){
            var th_el = this;
            var tr_el = $(this).closest('tr');
            var order_href = $(tr_el).attr('data-order-href');


            // current

            var order_current = $(tr_el).attr('data-order-current');
            var order_current_normalized = order_current;
            var order_current_reverse = '';
            if (typeof order_current != 'undefined') {
                if (order_current_normalized.charAt(0) == '!') {
                    order_current_normalized = order_current.substring(1);
                    order_current_reverse = '!';
                }
            }


            // add icons

            var html = $(th_el).html();

            html += ' <span class="on-icon-button active-up icon-arrow-up"></span>';
            html +=  '<span class="on-icon-button active-down icon-arrow-down"></span>';

            $(th_el).html(html);


            // column order

            var data_order = $(this).attr('data-order');
            var data_order_normalized = data_order;
            if (data_order_normalized.charAt(0) == '!') {
                data_order_normalized = data_order.substring(1);
            }


            // active icon

            if (order_current_normalized == data_order_normalized) {
                if (order_current_reverse == '!') {
                    $('.active-up', th_el).addClass('on-icon-active');
                }
                else {
                    $('.active-down', th_el).addClass('on-icon-active');
                }
            }

            // hover stuff

            $('.on-icon-button', th_el).hover(
                function(){
                    $(this).addClass('icon-white').css('background-color', '#000');
                },
                function(){
                    $(this).removeClass('icon-white').css('background-color', '');
                }
            );


            // clicks

            $('.on-icon-button', th_el).on('click', function(e){
                e.stopPropagation();
                var href = order_href;
                if ($(this).hasClass('active-up')) {
                    href += '!';
                }
                href += data_order_normalized;
                document.location = href;
            });
            $(th_el).on('click', function(e){
                e.stopPropagation();
                var href = order_href;
                if (data_order_normalized == order_current_normalized) {
                    if (order_current_reverse == '!') {
                        href += order_current_normalized;
                    }
                    else {
                        href += '!' + order_current_normalized;
                    }
                }
                else {
                    href += data_order;
                }
                document.location = href;
            });
        });
    });

    // just a regular link, we reset after a certain period (for new window/tab)
    $('a.crud-button-link').on('click', function(e){
        var click_el = this;

        crud_clear_selection = false;

        $(click_el).closest('.on-row-hover').addClass('on-row-active');

        if ($(click_el).attr('data-loading-text') === undefined) {
            $(click_el).attr('data-loading-text', $(click_el).text().trim().capitalize() + '&hellip;');
        }

        if ($(click_el).attr('data-loading-text') !== undefined) {
            $(click_el).button('loading');

            setTimeout(
                function(){
                    $(click_el).closest('.on-row-hover').removeClass('on-row-active');
                    $(click_el).button('reset');
                },
                2500
            );
        }
    });

    // give a modal window
    $('a.crud-button-text').on('click', function(e){
        var click_el = this;

        e.preventDefault();

        var title = $(click_el).text().trim().capitalize();
        var text = $(click_el).text().trim().capitalize() + '?';

        if ((a_title = $(click_el).attr('data-modal-title-text')) !== undefined) {
            title = a_title;
        }
        if ((dmt_selector = $(click_el).attr('data-modal-text-selector')) !== undefined) {
            // @todo we assume the selector will only match one element
            text = $(dmt_selector).html();
        }

        var dm_el = modalAll(title, text);
        $(dm_el).modal();
    });

    // give a modal confirmation
    $('a.crud-button-confirm').on('click', function(e){
        var click_el = this;
        var click_href = $(click_el).attr('href');
        var crud_id = $(this).closest('.crud-selection').attr('data-crud-table');

        if (typeof(crud_id) != 'undefined') {
            click_href = getCrudLinkWithSelection(click_href,crud_id);
        }

        e.preventDefault();
        $(click_el).closest('.on-row-hover').addClass('on-row-active');

        var title = $(click_el).text().trim().capitalize();
        var text = $(click_el).text().trim().capitalize() + '?';

        if ((a_title = $(click_el).attr('data-modal-title-text')) !== undefined) {
            title = a_title;
        }
        if ((dmt_selector = $(click_el).attr('data-modal-text-selector')) !== undefined) {
            // @todo we assume the selector will only match one element
            text = $(dmt_selector).html();
        }

        // not DRY (see crud-button-link)
        if ($(click_el).attr('data-loading-text') === undefined) {
            $(click_el).attr('data-loading-text', $(click_el).text().trim().capitalize() + '&hellip;');
        }

        if ($(click_el).attr('data-loading-text') !== undefined) {
            $(click_el).button('loading');
        }

        modalConfirm(
            title, text,
            function(){
                document.location = click_href;
                $(click_el).closest('.on-row-hover').removeClass('on-row-active');
                $(click_el).button('reset');
            },
            function(){
                $(click_el).closest('.on-row-hover').removeClass('on-row-active');
                $(click_el).button('reset');
            }
        );
    });

    $('a.crud-button-post-form').on('click', function(e){
        var click_el = this;
        var form_selector = $(click_el).attr('data-form-selector');
        var form_selector_el = $(form_selector);

        e.preventDefault();

        var title = $(click_el).text().trim().capitalize();
        var text = $(click_el).text().trim().capitalize() + '?';

        if ((a_title = $(click_el).attr('data-modal-title-text')) !== undefined) {
            title = a_title;
        }
        if ((dmt_selector = $(click_el).attr('data-modal-text-selector')) !== undefined) {
            // @todo we assume the selector will only match one element
            text = $(dmt_selector).html();
        }

        // not DRY (see crud-button-link)
        if ($(click_el).attr('data-loading-text') === undefined) {
            $(click_el).attr('data-loading-text', $(click_el).text().trim().capitalize() + '&hellip;');
        }

        if ($(click_el).attr('data-loading-text') !== undefined) {
            $(click_el).button('loading');
        }

        var post = {};
        $('select, input, textarea', form_selector_el).each(function(){
            var name = $(this).attr('name');
            var value = $(this).val();

            if ($(this).attr('type') == 'checkbox') {
                if (!$(this).is(':checked')) {
                    value = null;
                }
            }

            if (value !== null) {
                post[name] = value;
            }
        });

        var url = document.location;
        if ($(form_selector_el).attr('action') !== undefined) {
            url = $(form_selector_el).attr('action');
        }

        $.ajax({
            type: 'PUT',
            url: url,
            data: post,
            dataType: 'html',
            success: function(data, textStatus, jqXHR){
                switch (jqXHR.status) {
                    case 201:
                        // content created
                    case 204:
                        // no content (accepted)
                        if (!$(click_el).hasClass('form-no-redirect')) {
                            handleAjaxResponse(data, textStatus, jqXHR);
                        }
                        else {
                            $(click_el).button('reset');
                        }
                        break;

                    case 200:
                        // new content
                        var html = $(form_selector, data);
                        $(form_selector).replaceWith(html);
                        $(click_el).button('reset');
                        triggerElementLoad();
                        break;

                    default:
                        $(click_el).button('reset');
                        break;
                }
            },
            error: function(data, textStatus, jqXHR){
                $(click_el).button('reset');
            }
        });
    });

    $('.crud-pagination a.crud-button-link').on('click', function(e){
        crud_clear_selection = false;
    });

    $('.crud-selection a.crud-button-link').on('click', function(e){
        var href = $(this).attr('href');
        var crud_id = $(this).closest('.crud-selection').attr('data-crud-table');

        $(this).attr('href', getCrudLinkWithSelection(href,crud_id));
    });

    $('.crud-edit-form input, .crud-edit-form textarea').on('focus', function(e){
        //$(this).select();
    });
    $('.crud-edit-form input.primary-focus, .crud-edit-form textarea.primary-focus').each(function(){
        $(this).focus();
    });


    // unique field
    $('.crud-edit-form div.unique').each(function(){
        fixCrudUniqueSources($(this).closest('.crud-edit-form'), this, $(this).attr('data-field'), $(this).attr('data-sources'));
    });
    $('.crud-edit-form').on('click', 'a.value-edit', function(e){
        var unique_el = $(this).closest('div.unique');

        e.preventDefault();

        $('.value-auto').hide();
        $('.value-edit input').show();
    });
    $('.crud-edit-form').on('blur', '.value-edit input', function(e){
        var unique_el = $(this).closest('div.unique');
        var input_el = $('.value-edit input', unique_el);
        correctInputType(input_el, 'slug');
        var value = input_el.val();
        verifyCrudUniqueField(unique_el, value);
    });

    /*
    $('.crud-edit-form div.unique').each(function(){
        fixCrudUniqueSources($(this).closest('.crud-edit-form'), this, $(this).attr('data-field'), $(this).attr('data-sources'));
    });
    $('.crud-edit-form').on('click', 'a.unique-select', function(e){
        e.preventDefault();
        var unique_el = $(this).closest('div.unique');
        var field = $(unique_el).attr('data-field');
        if ($(this).hasClass('select-this')) {
            var value = $(this).text();
            $('li.edit-self', unique_el).show();
            $('li.select-this-first', unique_el).hide();
            $('span.current', unique_el).html(value).show();
            $('input', unique_el).val(value).hide();
        }
        else {
            // edit-self
            $('li.edit-self', unique_el).hide();
            $('li.select-this-first', unique_el).show();
            $('span.current', unique_el).hide();
            $('input', unique_el).show().focus().addClass('correct-type').attr('data-type', 'slug');
        }
    });
    */


    // @todo only works because of stuff inside base.js
    $(document).on('elementload', 'textarea.wysiwyg-redactor', function(e){
        $(this).redactor({
            buttons: ['html', '|', 'formatting', '|', 'bold', 'italic', 'deleted', '|',
            'unorderedlist', 'orderedlist', '|',
            'table', 'link', '|',
            'alignleft', 'aligncenter', 'alignright', 'justify', '|',
            'horizontalrule', 'fullscreen']
        });
    });
    $(document).on('elementload', 'div.execute-function', function(e){
        var data = $(this).data();
        var str  = 'var func = function(){'+data.function+'(data)}; func();';

        //console.log('executing', str);
        eval(str);
    });


    // generic dragover/leave events
    $(document).on('dragover', function(e){
        $('div.file-drop-target').each(function(){
            if (!$(this).hasClass('crud-hide')) {
                $(this).addClass('alert alert-success drag-target');
            }
        });
    });
    $(document).on('dragleave', function(e){
        $('div.file-drop-target').each(function(){
            $(this).removeClass('alert alert-success drag-target');
            if ($(this).hasClass('crud-hide')) {
                $(this).hide();
            }
        });
    });
});

