<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('tailorApp', () => ({
    showModal: false,
    shipping_fee: 0,
    availableAreas: [],
    loading: false,
    customer: { 
      source: '', 
      name: '', 
      phone: '', 
      governorate: '', 
      area: '', 
      is_gift: 'no', 
      gift_message: '' 
    },
    orders: [{ 
      id: 1, 
      stock_id: null,
      abaya_code: '',
      design_name: '',
      quantity: 1, 
      price: 0, 
      length: '', 
      bust: '', 
      sleeves: '', 
      buttons: 'yes', 
      notes: '' 
    }],

    updateAreas() {
      if (this.customer.governorate === 'مسقط') {
        this.availableAreas = ['السيب', 'بوشر', 'مطرح'];
      } else if (this.customer.governorate === 'الداخلية') {
        this.availableAreas = ['نزوى', 'بهلاء', 'الحمراء'];
      } else if (this.customer.governorate === 'الشرقية') {
        this.availableAreas = ['إبراء', 'صور', 'بدية'];
      } else {
        this.availableAreas = [];
      }
      this.customer.area = ''; // Reset area when governorate changes
    },
    
    updateShipping() {
      const area = this.customer.area;
      const fees = { 
        'السيب': 2, 
        'بوشر': 1.5, 
        'مطرح': 1.5, 
        'نزوى': 3, 
        'بهلاء': 3, 
        'الحمراء': 3, 
        'إبراء': 2.5, 
        'صور': 2.5, 
        'بدية': 2.5 
      };
      this.shipping_fee = fees[area] ?? 0;
    },
    
    addOrder() {
      const newId = this.orders.length + 1;
      this.orders.push({ 
        id: newId, 
        stock_id: null,
        abaya_code: '',
        design_name: '',
        quantity: 1, 
        price: 0, 
        length: '', 
        bust: '', 
        sleeves: '', 
        buttons: 'yes', 
        notes: '' 
      });
      this.$nextTick(() => {
        const element = document.getElementById('order-' + newId);
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' });
        }
      });
    },
    
    removeOrder(index) {
      if (confirm('{{ trans('messages.confirm_delete_order', [], session('locale')) }}')) {
        this.orders.splice(index, 1);
      }
    },
    
    openPaymentModal() {
      // Validate before opening modal
      if (!this.customer.name || !this.customer.source) {
        alert('{{ trans('messages.customer_name', [], session('locale')) }} و {{ trans('messages.order_source', [], session('locale')) }} مطلوبان');
        return;
      }
      
      if (this.orders.length === 0 || !this.orders.some(o => o.price > 0)) {
        alert('{{ trans('messages.add_new_abaya', [], session('locale')) }}');
        return;
      }
      
      this.showModal = true;
    },
    
    async submitOrders() {
      if (this.loading) return;
      
      this.loading = true;
      
      try {
        const formData = {
          customer: {
            name: this.customer.name,
            phone: this.customer.phone,
            source: this.customer.source,
            governorate: this.customer.governorate,
            area: this.customer.area,
            is_gift: this.customer.is_gift,
            gift_message: this.customer.gift_message
          },
          orders: this.orders.map(order => ({
            stock_id: order.stock_id,
            abaya_code: order.abaya_code,
            design_name: order.design_name,
            quantity: parseInt(order.quantity) || 1,
            price: parseFloat(order.price) || 0,
            length: order.length || null,
            bust: order.bust || null,
            sleeves: order.sleeves || null,
            buttons: order.buttons || 'yes',
            notes: order.notes || null
          })),
          shipping_fee: this.shipping_fee,
          notes: ''
        };

        console.log('Submitting order:', formData);

        const response = await fetch('{{ url('add_spcialorder') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);

        if (data.success) {
          this.showModal = false;
          
          // Show success message
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: '{{ trans('messages.order_saved_successfully', [], session('locale')) }}',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              // Reset form
              this.customer = { 
                source: '', 
                name: '', 
                phone: '', 
                governorate: '', 
                area: '', 
                is_gift: 'no', 
                gift_message: '' 
              };
              this.orders = [{ 
                id: 1, 
                stock_id: null,
                abaya_code: '',
                design_name: '',
                quantity: 1, 
                price: 0, 
                length: '', 
                bust: '', 
                sleeves: '', 
                buttons: 'yes', 
                notes: '' 
              }];
              this.shipping_fee = 0;
              this.availableAreas = [];
            });
          } else {
            alert('{{ trans('messages.order_saved_successfully', [], session('locale')) }}');
            // Reset form
            location.reload();
          }
        } else {
          throw new Error(data.message || 'Error saving order');
        }
      } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ الطلب: ' + error.message);
      } finally {
        this.loading = false;
      }
    }
  }));

  Alpine.data('abayaSelector', (order) => ({
    search: '', 
    selectedAbaya: null,
    abayas: [],
    loading: false,
    
    async searchAbayas() {
      if (this.search.length < 2) {
        this.abayas = [];
        return;
      }
      
      this.loading = true;
      
      try {
        const response = await fetch(`{{ url('search_abayas') }}?search=${encodeURIComponent(this.search)}`);
        const data = await response.json();
        this.abayas = data || [];
      } catch (error) {
        console.error('Error searching abayas:', error);
        this.abayas = [];
      } finally {
        this.loading = false;
      }
    },
    
    selectAbaya(item) {
      this.selectedAbaya = item;
      this.search = item.name || item.code;
      order.stock_id = item.id;
      order.abaya_code = item.code;
      order.design_name = item.name;
      order.price = parseFloat(item.price) || 0;
      this.abayas = []; // Clear results after selection
    },
  }));
});
</script>