jQuery(document).ready(function($) {
  $('.widgets-holder-wrap .hz-add-feed').live('click',function() {
    var widget = $(this).parents('.widgets-holder-wrap');
    var lastField = widget.find('.hz-feed-fields').children().last();
    var newField = lastField.clone();
    newField.find('label').text('Feed ' + (widget.find('.hz-feed-fields').children().length + 1) + ':').parent().find('input').val('').parent().insertAfter(lastField);
	});
});