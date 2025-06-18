jQuery(document).ready(function($) {
    // Artist registration form
    $('#artist-registration-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var messageContainer = form.siblings('.registration-message');
        
        // Clear previous messages
        messageContainer.html('').removeClass('error success');
        
        // Add loading state
        form.addClass('loading');
        form.find('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: artist_vendor_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                
                if (response.success) {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('success');
                    
                    // Redirect if provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('error');
                }
            },
            error: function() {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                messageContainer.html('<p>An error occurred. Please try again.</p>').addClass('error');
            }
        });
    });
    
    // Add product form
    $('#add-artist-product-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var messageContainer = form.find('.form-message');
        
        // Clear previous messages
        messageContainer.html('').removeClass('error success');
        
        // Add loading state
        form.addClass('loading');
        form.find('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: artist_vendor_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                
                if (response.success) {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('success');
                    
                    // Redirect if provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('error');
                }
            },
            error: function() {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                messageContainer.html('<p>An error occurred. Please try again.</p>').addClass('error');
            }
        });
    });
    
    // Edit product form
    $('#edit-artist-product-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        var messageContainer = form.find('.form-message');
        
        // Clear previous messages
        messageContainer.html('').removeClass('error success');
        
        // Add loading state
        form.addClass('loading');
        form.find('button[type="submit"]').prop('disabled', true);
        
        $.ajax({
            url: artist_vendor_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                
                if (response.success) {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('success');
                    
                    // Redirect if provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('error');
                }
            },
            error: function() {
                form.removeClass('loading');
                form.find('button[type="submit"]').prop('disabled', false);
                messageContainer.html('<p>An error occurred. Please try again.</p>').addClass('error');
            }
        });
    });
});