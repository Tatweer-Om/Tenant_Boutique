<script>
    function get_date_only(dateString) {
    // Convert the date string to a Date object
    const date = new Date(dateString);

    // Format the date as needed, for example: "YYYY-MM-DD"
    return date.toISOString().split('T')[0]; // Adjust the format as needed
}


function show_notification(type, msg) {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: 'toast-top-right', // Set position to top-right
            preventDuplicates: false,
            onclick: null,
            showDuration: '300',
            hideDuration: '1000',
            timeOut: '5000',
            extendedTimeOut: '1000',
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
        if (type == "success") {
            toastr.success(msg, type);
        } else if (type == "error") {
            toastr.error(msg, type);
        } else if (type == "warning") {
            toastr.warning(msg, type);
        }
    }

    function before_submit() {
        $('.submit_form').attr('disabled', true);
        $('.submit_form').html(
            'Please wait <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>');
    }

    function after_submit() {
        $('.submit_form').attr('disabled', false);
        $('.submit_form').html('Submit');
    }

function show_preloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.classList.remove('hidden');
        preloader.style.opacity = '1';
    }
}

function hide_preloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(() => preloader.classList.add('hidden'), 300);
    }
}

 function generateBarcode() {
    // Generate a random 8-12 digit number
    const randomCode = Math.floor(10000000 + Math.random() * 90000000);
    document.getElementById('barcode').value = randomCode;
  }

</script>