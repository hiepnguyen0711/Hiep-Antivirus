<link rel="stylesheet" href="templates/css/limit.css">
<section class="hy-pin-section">

    <div class="mdv-pin-container">
        <div class="mdv-pin-form">
            <div class="mdv-pin-header">
                <div class="mdv-pin-logo">
                    <a href="http://phuongnamvina.vn" rel="nofollow" target="_blank">
                        <img src="<?= URLPATH . "templates/images/logo-pnvn.png" ?>" alt="Logo PNVN" class="img-fluid" style="width: 100px;">
                    </a>
                </div>
                <h1>Xác Thực Bảo Mật</h1>
                <p>Vui lòng nhập mã PIN 6 số để tiếp tục</p>
            </div>

            <div class="mdv-pin-input-group">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]*">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]*">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]*">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]*">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]*">
                <input type="password" class="mdv-pin-input" maxlength="1" data-index="6" inputmode="numeric" pattern="[0-9]*">
            </div>

            <div class="mdv-pin-actions">
                <button class="mdv-pin-toggle">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="mdv-pin-clear">
                    <i class="fas fa-backspace"></i>
                </button>
            </div>

            <div class="mdv-pin-message"></div>

            <button class="mdv-pin-submit" disabled>
                <span class="default-text">
                    <i class="fas fa-lock me-2"></i>
                    Mở Khóa
                </span>
                <span class="loading-text">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Đang Xác Thực...
                </span>
                <span class="success-text">
                    <i class="fas fa-check-circle me-2"></i>
                    Xác Thực Thành Công
                </span>
            </button>

            <div class="mdv-pin-footer">
                <a href="tel:0912817117" target="_blank" class="mdv-pin-forgot">Quên mã PIN?</a>
            </div>
        </div>
    </div>
</section>


<script>
    $(document).ready(function() {
        const PIN_LENGTH = 6;
        let pinVisible = false;

        $('.mdv-pin-input:first').focus();

        $('.mdv-pin-input').on('input', function() {
            let $input = $(this);
            let value = $input.val();
            let index = parseInt($input.data('index'));

            if (!/^\d*$/.test(value)) {
                $input.val('');
                return;
            }

            if (value && index < PIN_LENGTH) {
                $(`.mdv-pin-input[data-index="${index + 1}"]`).focus();
            }

            updateSubmitButton();
        });

        $('.mdv-pin-input').on('keydown', function(e) {
            let $input = $(this);
            let index = parseInt($input.data('index'));

            if (e.key === 'Backspace' && !$input.val() && index > 1) {
                e.preventDefault();
                $(`.mdv-pin-input[data-index="${index - 1}"]`).val('').focus();
                updateSubmitButton();
            }

            if (e.key === 'Enter' && $('.mdv-pin-submit').prop('disabled') === false) {
                $('.mdv-pin-submit').click();
            }
        });

        $(document).on('paste', '.mdv-pin-input', function(e) {
            e.preventDefault();
            let pastedData = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            let numbers = pastedData.replace(/\D/g, '').split('').slice(0, PIN_LENGTH);

            numbers.forEach((num, index) => {
                $(`.mdv-pin-input[data-index="${index + 1}"]`).val(num);
            });

            if (numbers.length === PIN_LENGTH) {
                $('.mdv-pin-input:last').focus();
            } else {
                $(`.mdv-pin-input[data-index="${numbers.length + 1}"]`).focus();
            }

            updateSubmitButton();
        });

        $('.mdv-pin-toggle').click(function() {
            pinVisible = !pinVisible;
            let type = pinVisible ? 'text' : 'password';
            let icon = pinVisible ? 'fa-eye-slash' : 'fa-eye';

            $('.mdv-pin-input').attr('type', type);
            $(this).find('i').attr('class', `fas ${icon}`);
        });

        $('.mdv-pin-clear').click(resetPIN);

        function updateSubmitButton() {
            let filledInputs = $('.mdv-pin-input').filter((_, input) => $(input).val().length === 1).length;
            $('.mdv-pin-submit').prop('disabled', filledInputs !== PIN_LENGTH);
        }

        function resetPIN() {
            $('.mdv-pin-input').val('').removeClass('error success');
            $('.mdv-pin-input:first').focus();
            $('.mdv-pin-message').text('');
            $('.mdv-pin-submit').removeClass('loading success').prop('disabled', true);
        }

        $('.mdv-pin-submit').click(function() {
            let $btn = $(this);
            let enteredPin = $('.mdv-pin-input').map((_, input) => $(input).val()).get().join('');

            $btn.addClass('loading').prop('disabled', true);

            // Simulating AJAX request
            setTimeout(function() {

                $.ajax({
                    url: "sources/code_secure.php",
                    type: "POST",
                    data: {
                        xac_nhan: enteredPin
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status === "success") {
                            $('.mdv-pin-message').text('').removeClass('text-danger').addClass('text-success');
                            $btn.removeClass('loading').addClass('success');
                            FuiToast.success('Xác thực thành công');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            $('.mdv-pin-message').text("Mã PIN không chính xác").addClass('text-danger');
                            $btn.removeClass('loading').prop('disabled', false);

                            Swal.fire({
                                icon: "error",
                                title: "Sai mã PIN!",
                                text: "Vui lòng thử lại.",
                            });

                            setTimeout(resetPIN, 1000);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: "error",
                            title: "Lỗi hệ thống!",
                            text: "Vui lòng thử lại sau.",
                        });
                        $btn.removeClass('loading').prop('disabled', false);
                    }
                });

            }, 1500);
        });
    });
</script>