jQuery(document).ready(function ($) {
    $('#artist-profile-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url:         artist_vendor_params.ajax_url,
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('.form-message')
                        .removeClass('error')
                        .addClass('success')
                        .html(response.data.message);

                    if (response.data.reload) {
                        setTimeout(function () { window.location.reload(); }, 1000);
                    }
                } else {
                    $('.form-message')
                        .removeClass('success')
                        .addClass('error')
                        .html(response.data.message);
                }
            }
        });
    });
});
