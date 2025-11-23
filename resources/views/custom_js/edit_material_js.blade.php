<script>
     $(document).ready(function() {


   $('#update_material').on('submit', function(e) {
    alert(1);
        e.preventDefault();

        // --- Manual validations ---
        let material_name     = $('#material_name').val().trim();
        let material_notes    = $('#material_notes').val().trim();
        let material_unit     = $('#material_unit').val();
        let material_category = $('#material_category').val();

        if (!material_name) {
            show_notification('error', '<?= trans("messages.enter_material_name", [], session("locale")) ?>');
            return;
        }

        if (!material_notes) {
            show_notification('error', '<?= trans("messages.enter_material_notes", [], session("locale")) ?>');
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
                    $('#add_material')[0].reset();
                    $('#imagePreview').attr('src','').hide();
                }
            },
            error: function(xhr) {
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