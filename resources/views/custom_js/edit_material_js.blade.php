<script>
     $(document).ready(function() {
    // Prevent negative values in purchase price input
    $('#purchase_price').on('input blur', function() {
        const value = parseFloat($(this).val());
        if (value < 0 || isNaN(value)) {
            $(this).val(0);
        }
    });

   $('#update_material').on('submit', function(e) {
        e.preventDefault();

        let $form = $(this);
        let $submitBtn = $form.find('button[type="submit"]');
        
        // Check if already submitting
        if ($submitBtn.prop('disabled')) {
            return false;
        }

        // --- Manual validations ---
        let material_name     = $('#material_name').val().trim();
        let material_unit     = $('#material_unit').val();
        let material_category = $('#material_category').val();

        if (!material_name) {
            show_notification('error', '<?= trans("messages.enter_material_name", [], session("locale")) ?>');
            return;
        }

        if (!material_unit) {
            show_notification('error', '<?= trans("messages.enter_material_unit", [], session("locale")) ?>');
            return;
        }

        if (!material_category) {
            show_notification('error', '<?= trans("messages.enter_material_category", [], session("locale")) ?>');
            return;
        }

        // --- FormData (includes file) ---
        let formData = new FormData(this); // make sure <form> has enctype="multipart/form-data"
        let originalBtnText = $submitBtn.html();

        // Disable button
        $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

        $.ajax({
            url: "{{ route('update_material') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            beforeSend: function() {
                // optional loader
            },
            success: function(response) {
                if(response.status === 'success') {
                    show_notification('success', response.message);
                    // Redirect to view_material after a short delay
                    setTimeout(function() {
                        window.location.href = '{{ url("view_material") }}';
                    }, 1000);
                } else {
                    // Re-enable button on unexpected response
                    $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                }
            },
            error: function(xhr) {
                // Re-enable button on error
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); // show first validation error
                    });
                } else {
                    show_notification('error', 'Something went wrong!');
                }
            }
        });
    });

});


 const fileInput = document.getElementById('material_image');
    const imagePreview = document.getElementById('imagePreview');
    const uploadIcon = document.getElementById('uploadIcon');

    fileInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result; // Set new image
            imagePreview.classList.remove('hidden'); // Show image
            uploadIcon.style.display = 'none'; // Hide upload icon
        }
        reader.readAsDataURL(file);
    });

    // Optional: clicking the label opens file dialog
    document.getElementById('imageBoxLabel').addEventListener('click', () => {
        fileInput.click();
    });
</script>