
/**
 * Update files information
 */
function updateCrudFilesFieldRow(field_row_el)
{
    var files = [];
    $('ul.files li:not(.no-file)', field_row_el).each(function(){
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
        $('ul.files li:not(.no-file)', field_row_el).remove();
    }

    var file_el = $('li[data-name="'+file_info.name+'"]', field_row_el);

    if (file_el.length == 0) {
        var html = $('li.progress-template', field_row_el).html();
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

            var html = $('li.file-template', field_row_el).html();
            $(file_el).html(html);
            $('.name', file_el).html(file_info.name);
            $('.type', file_el).html(file_info.mimetype);
            $('.size', file_el).html(file_info.size);

            if ((typeof file_info.embed_url != 'undefined') && (file_info.embed_url != '')) {
                $.ajax({
                    type: 'GET',
                    url: file_info.embed_url,
                    success: function(data, textStatus, jqXHR){
                        $('div.preview', file_el).html(data);
                    }
                });
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

        var file_info = { 'name': file_name };
        updateCrudFileFieldRow(field_row_el, args, file_info, 10, 100);

        $(document).trigger('dragleave');

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



$(function(){
    // generic dragover/leave events
    $(document).on('dragover', function(e){
        $('div.file-drop-target').each(function(){
            $(this).show();
        });
    });
    $(document).on('dragleave', function(e){
        $('div.file-drop-target').each(function(){
            if ($('ul.files li:not(.no-file)').length > 0) {
                $(this).hide();
            }
        });
    });

    $(document).on('click', '.remove-link', function(e){
        var field_row_el = $(this).closest('.crud-field-row');
        var li_el = $(this).closest('li');

        li_el.remove();

        if ($('ul.files li:not(.no-file)').length == 0) {
            $('div.file-drop-target', field_row_el).show();
        }

        updateCrudFilesFieldRow(field_row_el);
    });
});
