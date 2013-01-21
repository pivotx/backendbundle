
$(function(){
    $(document).on('elementfinalize', 'textarea.wysiwyg-normal', function(e){
        if ($(this).attr('wysiwyg-init') == 'yes') {
            var id = $(this).attr('id');
            var data = CKEDITOR.instances[id].getData();
            $(this).val(data);
        }
    });

    $(document).on('elementload', 'textarea.wysiwyg-normal', function(e){
        if ($(this).attr('wysiwyg-init') != 'yes') {
            $(this).attr('wysiwyg-init', 'yes');

            CKEDITOR.replace($(this).attr('id'));
        }

        /*
        var area;

        if ($(this).attr('wysiwyg-init') != 'yes') {
            $(this).attr('wysiwyg-init', 'yes');

            area = new nicEditor({
                buttonList: [
                    'bold',
                    'italic',
                    'underline',
                    'left',
                    'center',
                    'right',
                    'justify',
                    'ol',
                    'ul',
                    'subscript',
                    'superscript',
                    'strikethrough',
                    'indent',
                    'outdent',
                    'hr',
                    // 'image',
                    // 'upload',
                    'link',
                    'unlink',
                    'removeformat'
                ]
            }).panelInstance($(this).attr('id'));
        }
        // area2.removeInstance('myArea2');
        */
    });
});
