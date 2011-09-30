jQuery(document).ready(function($) {

  $('.widgets-holder-wrap .hz-add-feed').live('click',function() {
    var parent = $(this).parents('.widgets-holder-wrap');
    var fields = $(parent).find('.hz-feed-fields input');
    var fieldName = $(parent).find('.field-name-prefix').text();
    fieldName = fieldName.replace('[]','[tfeed-' + parseInt(fields.length+1) + ']');
    var class = "hz-feed-" + parseInt(fields.length+1);
          
    $(parent).find('.hz-feed-fields').append('<div class="hz-field-wrapper"><label>Feed ' + parseInt(fields.length + 1) + ':</label><input id="' + class + '"type="text" name="' + fieldName + '" value="" /></div>');

	});  

});