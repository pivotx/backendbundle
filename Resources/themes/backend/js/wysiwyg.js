
$(function(){
    $(document).on('elementload', 'textarea.wysiwyg-normal', function(e){
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
                    'removeformat',
                    'indent',
                    'outdent',
                    'hr'
                    // 'image',
                    // 'upload',
                    //'link',
                    //'unlink'
                ]
            }).panelInstance($(this).attr('id'));
        }
        // area2.removeInstance('myArea2');
    });
});
