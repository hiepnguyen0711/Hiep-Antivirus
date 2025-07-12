<?php 
    $login_mobile_button = $d->getContents(968);
?>
<div class="pnvn-menu-mobile">
    <div class="container">
        <div class="row row-cols-2">
            <!-- Logo -->
            <div class="col pnvn-menumobile-left">
                <div class="pnvn-header-logo-box">
                    <a href="<?= URLPATH ?>"><img src="<?= _logo ?>" alt="Logo <?= _ten_cong_ty ?>"></a>
                </div>
            </div>
            <!-- Buuton menu -->
            <div class="col pnvn-menumobile-right">
                <div class="pnvn-button-menu-mobile">
                    <!-- đăng nhập trên mobile start -->
                <div class="sc-login-mobile-button me-1">
                    <a <?= vanhiep_link($login_mobile_button[1]) ?>>
                        <img class="flag-icon-img " src="<?= Img($login_mobile_button[1]['hinh_anh']) ?>" alt="icon">
                    </a>
                </div>
                    <!-- đăng nhập trên mobile end -->
                    <div class="hq-header-content-top-item-language">
                        <?php
                        if ($lang == "us") {
                        ?>
                            <a href="<?= $d->get_link_lang($com, 'vi') ?>" class="hvr-float-shadow">
                                <img class="flag-icon-img " src="<?= URLPATH ?>templates/images/vietnam-flag-icon.png" alt="Flag Icon">
                            </a>
                        <?php } else { ?>

                            <a href="<?= $d->get_link_lang($com, 'us') ?>" class="hvr-float-shadow">
                                <img class="flag-icon-img" src="<?= URLPATH ?>templates/images/united_states_of_america_round_icon_256.png" alt="Flag Icon">
                            </a>
                        <?php } ?>
                    </div>
                    <button type="button" class="menu-mobi navbar-toggle hc-nav-trigger hc-nav-1 d-md-block" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <?php include 'mmenu.php'; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    $('.menu-right-icon').click(function() {
        $('.menu-bar-right').addClass('menu-active');
    });
    $('.menu-right-close').click(function() {
        $('.menu-bar-right').removeClass('menu-active');
    })
    $('.menu-right-search-icon').click(function() {
        $('.search').toggleClass('search-active');
    })
    $('.header-button-language-container').click(function() {
        $('.language-second').toggleClass('header-button-language-active');
    })
</script>