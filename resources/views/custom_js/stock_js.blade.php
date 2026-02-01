<script>
$(document).ready(function() {
    // Prevent negative values in price inputs
    $('#cost_price, #sales_price, #tailor_charges, #notification_limit').on('input blur', function() {
        const value = parseFloat($(this).val());
        if (value < 0 || isNaN(value)) {
            $(this).val(0);
        }
    });

    // Prevent decimal values in quantity inputs (enforce integers only)
    $(document).on('input', 'input[data-validate="quantity"]', function() {
        let value = $(this).val();
        // Remove any decimal points or non-numeric characters
        value = value.replace(/[^\d]/g, '');
        // Ensure it's a positive integer
        const intValue = Math.max(0, parseInt(value) || 0);
        $(this).val(intValue);
        // Update Alpine.js model if it exists
        if (this._x_model && this._x_model.set) {
            this._x_model.set(intValue);
        }
    });

    $('#add_material').on('submit', function(e) {
        e.preventDefault();

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

        $.ajax({
            url: "{{ route('add_material') }}",
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
                    // Redirect to material list
                    if (response.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    }
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); // show first validation error
                    });
                } else {
                    show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
                }
            }
        });
    });

      $('#abaya_form').on('submit', function(e) {
    e.preventDefault();

    let $form = $(this);
    let $submitBtn = $form.find('button[type="submit"]');
    
    // Check if already submitting
    if ($submitBtn.prop('disabled')) {
        return false;
    }

    let abaya_code = $('#abaya_code').val().trim();
    let barcode = $('#barcode').val().trim();
    let design_name = $('#design_name').val().trim();
    let category_id = $('#category_id').val();
    let tailor_id = $('#tailor_id').val();
    let cost_price = $('#cost_price').val();
    let sales_price = $('#sales_price').val();
    let tailor_charges = $('#tailor_charges').val();

    // Validate all required fields
    if (!abaya_code) {
        show_notification('error', '<?= trans("messages.enter_abaya_code", [], session("locale")) ?: "Please enter abaya code" ?>');
        return;
    }
    if (!barcode) {
        show_notification('error', '<?= trans("messages.enter_barcode", [], session("locale")) ?: "Please enter barcode" ?>');
        return;
    }
    if (!design_name) {
        show_notification('error', '<?= trans("messages.enter_design_name", [], session("locale")) ?: "Please enter design name" ?>');
        return;
    }
    if (!category_id) {
        show_notification('error', '<?= trans("messages.enter_category", [], session("locale")) ?: "Please select a category" ?>');
        return;
    }
    if (!tailor_id) {
        show_notification('error', '<?= trans("messages.enter_tailor", [], session("locale")) ?: "Please select a tailor" ?>');
        return;
    }
    if (!cost_price || parseFloat(cost_price) < 0) {
        show_notification('error', '<?= trans("messages.enter_cost_price", [], session("locale")) ?: "Please enter cost price" ?>');
        return;
    }
    if (!sales_price || parseFloat(sales_price) < 0) {
        show_notification('error', '<?= trans("messages.enter_sales_price", [], session("locale")) ?: "Please enter sales price" ?>');
        return;
    }
    if (!tailor_charges || parseFloat(tailor_charges) < 0) {
        show_notification('error', '<?= trans("messages.enter_tailor_charges", [], session("locale")) ?: "Please enter tailor charges" ?>');
        return;
    }

    // Validate at least one color and size combination is selected
    let hasColorSize = false;
    $('input[name^="color_sizes["]').each(function() {
        let name = $(this).attr('name');
        // Check if this is a qty field (format: color_sizes[color_id][size_id][qty])
        if (name && name.includes('[qty]')) {
            let qty = parseFloat($(this).val()) || 0;
            if (qty > 0) {
                hasColorSize = true;
                return false; // break loop
            }
        }
    });
    
    if (!hasColorSize) {
        show_notification('error', '<?= trans("messages.enter_color_size", [], session("locale")) ?: "Please add at least one color and size combination" ?>');
        return;
    }

    // Validate at least one material is assigned
    let hasMaterial = false;
    $('input[name^="abaya_materials"][name$="[quantity]"]').each(function() {
        let qty = parseFloat($(this).val()) || 0;
        if (qty > 0) {
            hasMaterial = true;
            return false; // break loop
        }
    });
    
    if (!hasMaterial) {
        show_notification('error', '<?= trans("messages.at_least_one_material_required", [], session("locale")) ?: "At least one material must be assigned" ?>');
        return;
    }

    // Store original button text and disable button
    let originalBtnText = $submitBtn.html();
    $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

    let formData = new FormData(this);
    
    // Remove the images[] entries that FormData automatically added
    formData.delete('images[]');

    // Only append files from Alpine.js images array (files that haven't been removed)
    if (window.imageUploaderComponent && window.imageUploaderComponent.images && window.imageUploaderComponent.images.length > 0) {
        window.imageUploaderComponent.images.forEach(function(image) {
            if (image.file) {
                formData.append('images[]', image.file);
            }
        });
    } else {
        // Fallback: if Alpine component not found, use all files from input
        let imageInput = document.querySelector('#images');
        if (imageInput && imageInput.files) {
            for (let i = 0; i < imageInput.files.length; i++) {
                formData.append('images[]', imageInput.files[i]);
            }
        }
    }

    $.ajax({
        url: "{{ route('add_stock') }}",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        beforeSend: function() {
        },
        success: function(response) {
            if (response.status === 'success') {
                show_notification('success', '<?= trans("messages.stock_added_successfully", [], session("locale")) ?: "Stock added successfully!" ?>');
                $('#abaya_form')[0].reset();
                // Clear Alpine images array and file input
                if (window.imageUploaderComponent) {
                    window.imageUploaderComponent.images = [];
                }
                let imageInput = document.querySelector('#images');
                if (imageInput) {
                    imageInput.value = '';
                }
                // Redirect to stock list
                if (response.redirect_url) {
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 1000);
                }
            } else {
                // Re-enable button on unexpected response
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
            }
        },
        error: function(xhr) {
            // Re-enable button on error
            $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
            
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    show_notification('error', value[0]); 
                });
            } else {
                show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
            }
        }
    });
});

});


document.getElementById('material_image').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('imagePreview');
        img.src = e.target.result;
        img.classList.remove('hidden');

        // Hide icon and text
        document.getElementById('uploadIcon').style.display = 'none';
        document.getElementById('uploadText').style.display = 'none';
    };
    reader.readAsDataURL(file);
});
function imageUploader() {
    return {
        images: [],
        init() {
            // Store reference to this component globally for form submission
            window.imageUploaderComponent = this;
        },
        handleFiles(event) {
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.images.push({ file: file, url: e.target.result });
                };
                reader.readAsDataURL(file);
            }
        },
        removeImage(index) {
            // Remove the image from the array
            this.images.splice(index, 1);
        }
    };
}

// Quantity validation functions - prevent decimal/point values
window.validateQuantityInput = function(event) {
    // Allow: backspace, delete, tab, escape, enter, home, end, arrow keys
    if ([8, 9, 27, 13, 46, 35, 36, 37, 38, 39, 40].indexOf(event.keyCode) !== -1 ||
        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.keyCode === 65 && event.ctrlKey === true) ||
        (event.keyCode === 67 && event.ctrlKey === true) ||
        (event.keyCode === 86 && event.ctrlKey === true) ||
        (event.keyCode === 88 && event.ctrlKey === true)) {
        return;
    }
    // Ensure that it is a number and stop the keypress if it's a decimal point
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105) && event.keyCode !== 46) {
        event.preventDefault();
    }
    // Prevent decimal point
    if (event.keyCode === 190 || event.keyCode === 110 || event.keyCode === 188) {
        event.preventDefault();
    }
};

window.cleanQuantityOnPaste = function(event) {
    event.preventDefault();
    const pastedText = (event.clipboardData || window.clipboardData).getData('text');
    // Remove all non-numeric characters except digits
    const cleaned = pastedText.replace(/[^\d]/g, '');
    const input = event.target;
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const currentValue = input.value || '';
    const newValue = currentValue.substring(0, start) + cleaned + currentValue.substring(end);
    // Set value and ensure it's a positive integer
    input.value = Math.max(0, Math.floor(parseInt(newValue) || 0));
    // Trigger input event to update Alpine.js model
    input.dispatchEvent(new Event('input', { bubbles: true }));
};






</script>
