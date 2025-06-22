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
    $('#add-artist-product-form').on('submit', function (e) {
        e.preventDefault();

        var form = $(this);
        var formData = new FormData(this);
        var submitButton = form.find('button[type="submit"]');
        
        // Add loading state with spinner
        form.addClass('loading');
        submitButton.prop('disabled', true);
        
        // Add spinner to button (left side)
        var originalText = submitButton.text();
        submitButton.html('<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>' + originalText);

        $.ajax({
            url: artist_vendor_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    // Show success message
                    if (response.data.message) {
                        // You can add a success message display here if needed
                        console.log(response.data.message);
                    }

                    // Keep button disabled with spinner during redirect
                    submitButton.prop('disabled', true);
                    submitButton.html('<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>' + originalText);

                    // Redirect if provided
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    // Show error message and re-enable button
                    form.removeClass('loading');
                    submitButton.prop('disabled', false);
                    submitButton.html(originalText);

                    if (response.data.message) {
                        alert(response.data.message);
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            },
            error: function () {
                // Show error and re-enable button
                form.removeClass('loading');
                submitButton.prop('disabled', false);
                submitButton.html(originalText);
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Edit product form
    $('#edit-artist-product-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Edit form submitted'); // Debug log

        var form = $(this);
        var formData = new FormData(this);
        var submitButton = form.find('button[type="submit"]');
        var messageContainer = form.find('.form-message');
        
        console.log('Submit button found:', submitButton.length); // Debug log
        console.log('Message container found:', messageContainer.length); // Debug log

        // Clear previous messages
        messageContainer.html('').removeClass('error success');
        
        // Add loading state
        form.addClass('loading');
        submitButton.prop('disabled', true);

        // Add spinner to button (left side)
        var originalText = submitButton.text();
        console.log('Original button text:', originalText); // Debug log

        submitButton.html('<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>' + originalText);

        console.log('Spinner added to button'); // Debug log
        
        $.ajax({
            url: artist_vendor_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                console.log('AJAX response:', response); // Debug log
                
                if (response.success) {
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('success');
                    
                    // Keep button disabled with spinner during redirect
                    submitButton.prop('disabled', true);
                    submitButton.html('<span class="spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid #ffffff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite; margin-right: 8px;"></span>' + originalText);

                    // Redirect if provided
                    if (response.data.redirect) {
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    }
                } else {
                    // Show error message and re-enable button
                    form.removeClass('loading');
                    submitButton.prop('disabled', false);
                    submitButton.html(originalText);
                    messageContainer.html('<p>' + response.data.message + '</p>').addClass('error');
                }
            },
            error: function (xhr, status, error) {
                console.log('AJAX error:', error); // Debug log
            // Show error and re-enable button
                form.removeClass('loading');
                submitButton.prop('disabled', false);
                submitButton.html(originalText);
                messageContainer.html('<p>An error occurred. Please try again.</p>').addClass('error');
            }
        });
    });
});