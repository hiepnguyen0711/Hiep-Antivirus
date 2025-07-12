<?php
$num_cart = 0;
$tong = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $value) {
        $price_h = $value['gia'];
        $num_cart = $num_cart + $value['soluong'];
        $tongtien_h = $tongtien_h + ($price_h * $value['soluong']);
    }
}


$giohang_c = $d->getCate(121);


?>

<header class="sca-header">
    <div class="container">

    </div>
</header>


<!-- modal search start -->
<div class="ys-modal-search d-flex align-items-center justify-content-center">
    <i class="fa-solid fa-xmark btn-close-search"></i>
    <div class="container">
        <form action="" method="post">
            <input type="hidden" name="com" value="search">
            <div class="ys-modal-search-box">
                <div class="form-floating mb-3">
                    <input type="text" name="textsearch" class="form-control" id="floatingInput" placeholder="name@example.com">
                    <label for="floatingInput">Tìm kiếm...</label>
                    <button type="submit" class="btn ys-btn-search"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- modal search end -->

<script>
    $(document).ready(function() {
        $('.nb-header-search-btn').on('click', function() {
            $('.ys-modal-search').addClass('active');
        });
        $('.btn-close-search').on('click', function() {
            $('.ys-modal-search').removeClass('active');
        });
    });
</script>



<script>
    window.addEventListener('scroll', function() {
        var headerHeight = 100;
        var offset = 100;
        $('header').toggleClass('active', window.scrollY > headerHeight + offset);
        $('.header-logo img').toggleClass('header-logo-img-active', window.scrollY > headerHeight + offset);
        $('.pnvn-menu-mobile').toggleClass('active', window.scrollY > 100 + offset);
        // $('.sub-menu').toggleClass('header-menu-sub-menu-active', window.scrollY > 0);
        // $('.search').toggleClass('search-active-scroll', window.scrollY > 0);
        // $('.sub-menu').toggleClass('list-menu-sub-menu-scroll-active', window.scrollY > 0);
    });
</script>