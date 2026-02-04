<script>
 $(document).ready(function() {
    $('.delete-image').click(function() {
        let imageId = $(this).data('id');
        let token = "{{ csrf_token() }}";

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("stock/image") }}/' + imageId,
                    type: 'DELETE',
                    data: {
                        _token: token
                    },
                    success: function(response) {
                        $('#image-' + imageId).remove();
                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        );
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong while deleting the image.',
                            'error'
                        );
                    }
                });
            }
        });
    });

    $('#update_abaya').on('submit', function(e) {
        e.preventDefault();

        let $form = $(this);
        let $submitBtn = $form.find('button[type="submit"]');
        
        if ($submitBtn.prop('disabled')) {
            return false;
        }

        let abaya_code = $('#abaya_code').val().trim();
        let barcode = $('#barcode').val().trim();
        let design_name = $('#design_name').val();

        if (!abaya_code) {
            show_notification('error', '<?= trans("messages.enter_abaya_code", [], session("locale")) ?>');
            return;
        }
        if (!barcode) {
            show_notification('error', '<?= trans("messages.enter_barcode", [], session("locale")) ?>');
            return;
        }
        if (!design_name) {
            show_notification('error', '<?= trans("messages.enter_design_name", [], session("locale")) ?>');
            return;
        }

        let originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

        let formData = new FormData(this);

        $.ajax({
            url: "{{ route('update_stock') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message || 'Stock updated successfully!');
                    setTimeout(function() {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.href = '{{ url("view_stock") }}';
                        }
                    }, 1500);
                } else {
                    $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); 
                    });
                } else {
                    show_notification('error', 'Something went wrong!');
                }
            }
        });
    });
});

function imageUploader() {
    return {
        images: [],
        handleFiles(event) {
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.images.push({
                        file: file,
                        url: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            }
        },
        removeImage(index) {
            this.images.splice(index, 1);
        }
    };
}
</script>
