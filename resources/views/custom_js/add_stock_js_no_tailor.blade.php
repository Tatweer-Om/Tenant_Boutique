<script>
$(document).ready(function() {
    $('#cost_price, #sales_price, #tailor_charges, #notification_limit').on('input blur', function() {
        const value = parseFloat($(this).val());
        if (value < 0 || isNaN(value)) $(this).val(0);
    });

    $(document).on('input', 'input[data-validate="quantity"]', function() {
        let value = $(this).val().replace(/[^\d]/g, '');
        const intValue = Math.max(0, parseInt(value) || 0);
        $(this).val(intValue);
        if (this._x_model && this._x_model.set) this._x_model.set(intValue);
    });

    $('#abaya_form').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $submitBtn = $form.find('button[type="submit"]');
        if ($submitBtn.prop('disabled')) return false;

        let abaya_code = $('#abaya_code').val().trim();
        let barcode = $('#barcode').val().trim();
        let design_name = $('#design_name').val().trim();
        let category_id = $('#category_id').val();

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
        if (!category_id) {
            show_notification('error', '<?= trans("messages.enter_category", [], session("locale")) ?>');
            return;
        }

        let hasColorSize = false;
        $('input[name^="color_sizes["]').each(function() {
            let name = $(this).attr('name');
            if (name && name.includes('[qty]')) {
                let qty = parseFloat($(this).val()) || 0;
                if (qty > 0) { hasColorSize = true; return false; }
            }
        });
        if (!hasColorSize) {
            $('input[name^="colors["][name$="[qty]"]').each(function() {
                if (parseFloat($(this).val()) > 0) { hasColorSize = true; return false; }
            });
        }
        if (!hasColorSize) {
            $('input[name^="sizes["][name$="[qty]"]').each(function() {
                if (parseFloat($(this).val()) > 0) { hasColorSize = true; return false; }
            });
        }
        if (!hasColorSize) {
            show_notification('error', '<?= trans("messages.enter_color_size", [], session("locale")) ?>');
            return;
        }

        let originalBtnText = $submitBtn.html();
        $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

        let formData = new FormData(this);
        formData.delete('images[]');
        if (window.imageUploaderComponent && window.imageUploaderComponent.images && window.imageUploaderComponent.images.length > 0) {
            window.imageUploaderComponent.images.forEach(function(image) {
                if (image.file) formData.append('images[]', image.file);
            });
        } else {
            let imageInput = document.querySelector('#images');
            if (imageInput && imageInput.files) {
                for (let i = 0; i < imageInput.files.length; i++) {
                    formData.append('images[]', imageInput.files[i]);
                }
            }
        }

        $.ajax({
            url: "{{ route('add_stock.post') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status === 'success') {
                    show_notification('success', response.message || '<?= trans("messages.stock_added_successfully", [], session("locale")) ?>');
                    $('#abaya_form')[0].reset();
                    if (window.imageUploaderComponent) window.imageUploaderComponent.images = [];
                    let imageInput = document.querySelector('#images');
                    if (imageInput) imageInput.value = '';
                    if (response.redirect_url) {
                        setTimeout(function() { window.location.href = response.redirect_url; }, 1000);
                    }
                } else {
                    $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) { show_notification('error', value[0]); });
                } else {
                    show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
                }
            }
        });
    });
});

function imageUploader() {
    return {
        images: [],
        init() { window.imageUploaderComponent = this; },
        handleFiles(event) {
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = (e) => { this.images.push({ file: file, url: e.target.result }); };
                reader.readAsDataURL(file);
            }
        },
        removeImage(index) { this.images.splice(index, 1); }
    };
}

window.validateQuantityInput = function(event) {
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].indexOf(event.keyCode) !== -1 ||
        (event.keyCode === 65 && event.ctrlKey === true) || (event.keyCode === 67 && event.ctrlKey === true) ||
        (event.keyCode === 86 && event.ctrlKey === true) || (event.keyCode === 88 && event.ctrlKey === true)) return;
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105) && event.keyCode !== 46) event.preventDefault();
    if (event.keyCode === 190 || event.keyCode === 110 || event.keyCode === 188) event.preventDefault();
};

window.cleanQuantityOnPaste = function(event) {
    event.preventDefault();
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    const cleaned = pastedText.replace(/[^\d]/g, '');
    const input = event.target;
    const start = input.selectionStart, end = input.selectionEnd;
    const currentValue = input.value || '';
    const newValue = currentValue.substring(0, start) + cleaned + currentValue.substring(end);
    input.value = Math.max(0, Math.floor(parseInt(newValue) || 0));
    input.dispatchEvent(new Event('input', { bubbles: true }));
};
</script>
