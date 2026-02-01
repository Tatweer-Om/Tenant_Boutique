<script>
function tailorProfile() {
    return {
        tab: 'special_orders',
        selectedMaterial: false,
        selectedMaterialName: '',
        materialUnit: '',
        materialCategory: '',
        quantityLabel: '',
        materials: [],
        filteredMaterials: [],
        showMaterialDropdown: false,
        materialSearch: '',
        init() {
            this.loadMaterials();
        },
        loadMaterials() {
            $.get("{{ route('materials.all') }}", (data) => {
                this.materials = data || [];
                this.filteredMaterials = this.materials;
                // No need to populate hidden select anymore - we work directly with materials array
            }).fail(() => {
                console.error('Error loading materials');
                this.materials = [];
                this.filteredMaterials = [];
            });
        },
        filterMaterials() {
            const search = (this.materialSearch || '').toLowerCase().trim();
            if (!search) {
                this.filteredMaterials = this.materials;
            } else {
                this.filteredMaterials = this.materials.filter(m => {
                    const nameMatch = (m.material_name || '').toLowerCase().includes(search);
                    const categoryMatch = (this.getCategoryLabel(m.category) || '').toLowerCase().includes(search);
                    const unitMatch = (m.unit || '').toLowerCase().includes(search);
                    return nameMatch || categoryMatch || unitMatch;
                });
            }
            // Show dropdown if there are filtered results
            if (this.filteredMaterials.length > 0) {
                this.showMaterialDropdown = true;
            }
        },
        selectedMaterialData: null,
        availableQuantity: 0,
        selectMaterial(material) {
            // Close dropdown first
            this.showMaterialDropdown = false;
            
            // Set the material directly
            $('#material_id').val(material.id);
            this.materialSearch = material.material_name;
            
            // Store material data for validation
            this.selectedMaterialData = material;
            this.availableQuantity = parseFloat(material.available_quantity || material.meters_per_roll || 0);
            
            // Blur the input to remove focus
            setTimeout(() => {
                $('#material_search').blur();
            }, 100);
            
            // Update Alpine.js state directly from the material object
            this.selectedMaterial = true;
            this.selectedMaterialName = material.material_name || '';
            this.materialUnit = material.unit || '-';
            this.materialCategory = material.category || '-';
            
            // Set quantity label based on unit
            if (material.unit === 'roll') {
                this.quantityLabel = '{{ trans("messages.how_many_rolls", [], session("locale")) }}';
            } else if (material.unit === 'meter') {
                this.quantityLabel = '{{ trans("messages.how_many_meters", [], session("locale")) }}';
            } else if (material.unit === 'piece') {
                this.quantityLabel = '{{ trans("messages.how_many_pieces", [], session("locale")) }}';
            } else {
                this.quantityLabel = '{{ trans("messages.quantity", [], session("locale")) }}';
            }
            
            // Set max attribute on quantity input
            $('#quantity').attr('max', this.availableQuantity);
        },
        onMaterialSelect() {
            // This method is kept for backward compatibility but may not be needed
            const materialId = $('#material_id').val();
            if (materialId) {
                $.get("{{ url('materials') }}/" + materialId, (response) => {
                    if (response.status === 'success') {
                        const material = response.material;
                        $('#material_id').val(material.id);
                        this.selectedMaterial = true;
                        this.selectedMaterialName = material.material_name || '';
                        this.materialUnit = material.unit || '-';
                        this.materialCategory = material.category || '-';
                        
                        // Set quantity label based on unit
                        if (material.unit === 'roll') {
                            this.quantityLabel = '{{ trans("messages.how_many_rolls", [], session("locale")) }}';
                        } else if (material.unit === 'meter') {
                            this.quantityLabel = '{{ trans("messages.how_many_meters", [], session("locale")) }}';
                        } else if (material.unit === 'piece') {
                            this.quantityLabel = '{{ trans("messages.how_many_pieces", [], session("locale")) }}';
                        } else {
                            this.quantityLabel = '{{ trans("messages.quantity", [], session("locale")) }}';
                        }
                    }
                }).fail(() => {
                    show_notification('error', '{{ trans("messages.error_loading_material", [], session("locale")) }}');
                });
            } else {
                this.selectedMaterial = false;
                this.selectedMaterialName = '';
                this.materialSearch = '';
                $('#material_id').val('');
            }
        },
        getCategoryLabel(category) {
            if (!category) return '-';
            const categories = {
                'fabric': '{{ trans("messages.fabric", [], session("locale")) }}',
                'embroidery': '{{ trans("messages.embroidery", [], session("locale")) }}',
                'accessories': '{{ trans("messages.accessories", [], session("locale")) }}'
            };
            return categories[category] || category;
        }
    }
}

// Form submission
$(document).ready(function() {
    $('#send_material_form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Form submitted');
        
        // Get values
        let material_id = $('#material_id').val();
        let quantity = $('#quantity').val();
        let tailor_id = $('input[name="tailor_id"]').val();

        console.log('Form values:', {
            material_id: material_id,
            quantity: quantity,
            tailor_id: tailor_id
        });

        // Validation
        if (!material_id) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ trans("messages.warning", [], session("locale")) }}',
                    text: '{{ trans("messages.please_select_material", [], session("locale")) }}',
                    confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                });
            } else {
                show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}');
            }
            return false;
        }

        if (!quantity || quantity <= 0 || isNaN(quantity) || quantity < 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ trans("messages.warning", [], session("locale")) }}',
                    text: '{{ trans("messages.please_enter_quantity", [], session("locale")) }}',
                    confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                });
            } else {
                show_notification('error', '{{ trans("messages.please_enter_quantity", [], session("locale")) }}');
            }
            return false;
        }

        // Validate quantity doesn't exceed available
        const component = Alpine.$data(document.querySelector('[x-data="tailorProfile()"]'));
        if (component && component.selectedMaterialData) {
            const availableQty = parseFloat(component.availableQuantity || 0);
            const requestedQty = parseFloat(quantity);
            
            if (requestedQty > availableQty) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ trans("messages.error", [], session("locale")) }}',
                        text: '{{ trans("messages.insufficient_material_quantity", [], session("locale")) ?: "Insufficient material quantity" }}. ' +
                              '{{ trans("messages.available", [], session("locale")) ?: "Available" }}: ' + availableQty.toFixed(2) + 
                              ', {{ trans("messages.requested", [], session("locale")) ?: "Requested" }}: ' + requestedQty.toFixed(2),
                        confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                    });
                } else {
                    show_notification('error', '{{ trans("messages.insufficient_material_quantity", [], session("locale")) ?: "Insufficient material quantity" }}');
                }
                return false;
            }
        }

        // Prepare form data
        let formData = {
            tailor_id: tailor_id,
            material_id: material_id,
            quantity: parseFloat(quantity),
            _token: '{{ csrf_token() }}'
        };

        console.log('Sending AJAX request with data:', formData);

        // Disable submit button to prevent double submission
        const submitBtn = $('#send_material_form button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>{{ trans("messages.saving", [], session("locale")) }}...');

        // Make AJAX request
        $.ajax({
            url: "{{ route('send_material_to_tailor') }}",
            type: "POST",
            data: formData,
            dataType: 'json',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            success: function(response) {
                console.log('Success response:', response);
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                if(response && response.status === 'success') {
                    // Show SweetAlert success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ trans("messages.success", [], session("locale")) }}',
                            text: response.message || '{{ trans("messages.material_sent_successfully", [], session("locale")) }}',
                            confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: true
                        }).then((result) => {
                            // Reset form
                            $('#send_material_form')[0].reset();
                            $('#material_id').val('');
                            $('#abaya_id').val('');
                            
                            // Reset Alpine.js state
                            const component = Alpine.$data(document.querySelector('[x-data="tailorProfile()"]'));
                            if (component) {
                                component.selectedMaterial = false;
                                component.selectedMaterialName = '';
                                component.materialUnit = '';
                                component.materialCategory = '';
                                component.quantityLabel = '';
                                component.showMaterialDropdown = false;
                                component.materialSearch = '';
                            }
                            
                            // Reload page to show updated materials sent history
                            window.location.reload();
                        });
                    } else {
                        show_notification('success', response.message || '{{ trans("messages.material_sent_successfully", [], session("locale")) }}');
                        $('#send_material_form')[0].reset();
                        $('#material_id').val('');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    // Handle unexpected response
                    console.error('Unexpected response:', response);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ trans("messages.warning", [], session("locale")) }}',
                            text: response.message || '{{ trans("messages.generic_error", [], session("locale")) }}',
                            confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                        });
                    } else {
                        show_notification('error', response.message || '{{ trans("messages.generic_error", [], session("locale")) }}');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseJSON,
                    statusCode: xhr.status
                });
                
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                let errorMessage = '{{ trans("messages.generic_error", [], session("locale")) }}';
                
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    errorMessage = xhr.responseJSON?.message || '';
                    
                    // If validation errors, show first error or all errors
                    if (Object.keys(errors).length > 0) {
                        const firstError = Object.values(errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 500) {
                    errorMessage = '{{ trans("messages.server_error", [], session("locale")) }}';
                } else if (xhr.status === 0) {
                    errorMessage = '{{ trans("messages.network_error", [], session("locale")) }}';
                }
                
                // Show SweetAlert error
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ trans("messages.error", [], session("locale")) }}',
                        text: errorMessage,
                        confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                    });
                } else {
                    show_notification('error', errorMessage);
                }
            }
        });
        
        return false;
    });
});
</script>