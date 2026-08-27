(function ($) {
    'use strict';


	
	jQuery(document).on( 'click', '.uacf7-column-select', function(){
		jQuery(this).siblings().removeClass('example-active');
		jQuery(this).addClass('example-active');
		
		var uacf7ColumnTag = jQuery(this).attr('data-column-codes');
		jQuery('.uacf7-column-tag-insert').val(uacf7ColumnTag);
		
		jQuery('.insert-tag.uacf7-column-insert-tag').trigger('click');
	});

})(jQuery);
