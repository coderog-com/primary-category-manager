jQuery(document).ready(function($) {
    // $('.primary-category-name').on('click', function() {
    //     var $this = $(this);
    //     // $this.hide();
    //     $this.siblings('.primary-category-dropdown').show();
    // });

    $('.primary-category-dropdown').on('change', function() {
        var $this = $(this);
        var postId = $this.data('post-id');
        var categoryId = $this.val();
        var $loadingImg = $('.primary-category-dropdown-loading-img[data-post-id="' + postId + '"]');
        var $statusImg = $('.primary-category-dropdown-status-img[data-post-id="' + postId + '"]');
        var $primaryCategoryNameYoast = $('.primary-category-name-yoast[data-post-id="' + postId + '"]');
        
        $loadingImg.show();

        $.ajax({
            type: 'POST',
            url: primaryCategoryAjax.ajax_url,
            data: {
                action: 'update_primary_category',
                post_id: postId,
                category_id: categoryId
            },
            success: function(response) {
                if (response == 'success') {
                    var selectedCategory = $this.find('option:selected').text();
                    
                    // $this.hide();
                    
                    // Change status icon to 'saved' icon
                    changeStatusIcon($statusImg, 'dashicons dashicons-saved');
                    $loadingImg.hide();
                    
                    $this.siblings('.primary-category-name').text(selectedCategory).show();
                    $primaryCategoryNameYoast.text(selectedCategory).show();
                    
                    console.log($this.siblings('.primary-category-name'));
                } else {
                    // Change status icon to 'error' icon
                    changeStatusIcon($statusImg, 'dashicons dashicons-dismiss');
                    $loadingImg.hide();
                    
                    alert('Error updating primary category');
                }
            },
            error: function() {
                // Change status icon to 'error' icon on AJAX failure
                changeStatusIcon($statusImg, 'dashicons dashicons-dismiss');
                $loadingImg.hide();
                
                alert('Error updating primary category');
            }
        });
    });

    // $(document).click(function(e) {
    //     if (!$(e.target).closest('.primary-category-name, .primary-category-dropdown').length) {
    //         // $('.primary-category-dropdown').hide();
    //         // alert("done!");
    //         $('.primary-category-name').show();
    //     }
    // });
    
    function changeStatusIcon($imgElement, statusClass) {
        // Remove existing dashicons class
        var dashiconClass = $imgElement.attr('class').split(' ').find(function(className) {
            return className.startsWith('dashicons-');
        });
        
        if (dashiconClass) {
            $imgElement.removeClass(dashiconClass);
        }
        
        // Add new status class
        $imgElement.addClass(statusClass);
    }
});
