<?php
// về chúng tôi
$about_us_introduce = $d->getContent(1016);
// tầm nhìn và giá trị cốt lõi
$core_values = $d->getContent(1017);
$core_values_list = $d->getContents(1017);
// giới thiệu về sca
$introduce_sca = $d->getContent(1022);
// lịch sử SCA
$history = $d->getContent(1023);
$history_sca = $d->getContents(1023);
// mạng lưới phủ sóng
$network = $d->getContent(1030);
// Hình quảng cáo
$picture_ad = $d->getContent(1031);
?>
<!-- breadcrumb start -->
<?php include 'module/sca-breadcrumb.php'; ?>
<!-- breadcrumb end -->
<!-- banner start -->
<?php include 'module/sca-banner.php'; ?>
<!-- banner end -->

<!-- về chúng tôi start -->
<section class="sca-about-us-page-about-section p-0 mb-4">
    <div class="container">
        <div class="sca-about-us-page-about-section-content p-3 p-lg-5 d-flex flex-column align-items-center">
            <!-- title -->
            <div class="sca-about-us-page-about-section-title text-center mb-3" data-aos="fade-up" data-aos-duration="500" data-aos-delay="0" data-aos-once="true">
                <?= $about_us_introduce['ten'] ?>
            </div>
            <!-- des -->
            <div class="sca-about-us-page-about-section-des text-center" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200" data-aos-once="true">
                <?= $about_us_introduce['noi_dung'] ?>
            </div>
            <!--  -->
        </div>
    </div>
</section>
<!-- về chúng tôi end -->

<!-- tầm nhìn và giá trị cốt lõi -->
<section class="sca-core-values">
    <div class="container">
        <div class="sca-core-values-content">
            <div class="row row-cols-1 row-cols-lg-2 align-items-center gx-5 gy-4">
                <div class="col">
                    <!-- hình -->
                    <div class="sca-core-values-content-item">
                        <!-- hình chính start -->
                        <div class="pb-5">
                            <div class="sca-core-values-content-item-image-main ratio ratio-4x3">
                                <a href="<?= Img($core_values_list[0]['hinh_anh']) ?>" data-fancybox="core-value-album">
                                    <img class="hvr-grow" data-src="<?= Img($core_values_list[0]['hinh_anh']) ?>" alt="hình giá trị cốt lõi">
                                </a>
                            </div>
                        </div>

                        <!-- hình chính end -->

                        <!-- hình phụ start -->
                        <div class="sca-core-values-content-item-image-extra d-flex flex-column">
                            <div class="sca-core-values-content-item-image-extra-logo p-3">
                                <img data-src="<?= Img($core_values['hinh_anh']) ?>" alt="ảnh logo">
                            </div>
                            <div class="sca-core-values-content-item-image-extra-bottom">
                                <img class="hvr-grow" data-src="<?= Img($core_values_list[1]['hinh_anh']) ?>" alt="hình giá trị cốt lõi">
                            </div>
                        </div>
                        <!-- hình phụ end -->
                    </div>
                    <!-- hình end -->
                </div>
                <div class="col">
                    <div class="sca-core-values-content-item ps-lg-5">
                        <div class="row row-cols-1 gy-4">
                            <?php
                            foreach ($core_values_list as $key => $v) {
                                if ($key > 1) {
                            ?>
                                    <!-- item start -->
                                    <div class="col sca-core-values-content-item-col">
                                        <div class="sca-core-values-content-subitem">
                                            <div class="sca-core-values-content-subitem-title mb-2" data-aos="fade-up" data-aos-duration="500" data-aos-delay="0" data-aos-once="true">
                                                <?= $v['ten'] ?>
                                            </div>
                                            <div class="sca-core-values-content-subitem-des mb-2" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200" data-aos-once="true">
                                                <?= $v['noi_dung'] ?>
                                            </div>
                                            <div class="sca-core-values-content-subitem-line" data-aos="fade-right" data-aos-duration="500" data-aos-delay="300" data-aos-once="true">
                                                <svg width="92" height="10" viewBox="0 0 92 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M1.94946 8.83326H91.5951L73.9824 1.59057" stroke="#C8541A" stroke-width="2" stroke-linecap="round" />
                                                </svg>

                                            </div>
                                        </div>
                                    </div>
                                    <!-- item end -->
                            <?php }
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- tầm nhìn và giá trị cốt lõi -->

<!-- giới thiệu về sca start -->
<section class="sca-introduce-about-sca">
    <div class="container">
        <div class="sca-introduce-about-sca-content">
            <div class="row row-cols-1 row-cols-lg-2 gx-5 gy-4 flex-column-reverse flex-lg-row align-items-center">
                <div class="col">
                    <div class="sca-introduce-about-sca-content-item d-flex flex-column">
                        <div class="sca-introduce-about-sca-content-item-title mb-3 sca-core-values-content-subitem-title" data-aos="fade-up" data-aos-duration="500" data-aos-delay="0" data-aos-once="true">
                            <?= $introduce_sca['ten'] ?>
                        </div>
                        <div class="sca-introduce-about-sca-content-item-des sca-core-values-content-subitem-des" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200" data-aos-once="true">
                            <?= $introduce_sca['noi_dung'] ?>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="sca-introduce-about-sca-content-item">
                        <div class="sca-introduce-about-sca-content-item-image ratio ratio-4x3">
                            <img data-src="<?= Img($introduce_sca['hinh_anh']) ?>" alt="Ảnh giới thiệu">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- giới thiệu về sca end -->


<!-- lịch sử start -->
<section class="page-cau-chuyen-nnv" style="background-image: url('<?= Img($history['hinh_anh']) ?>');">
    <div class="container">
        <div class="page-cau-chuyen-nnv-content">
            <div class="row align-items-center gx-5 gy-4">
                <div class="col-12 col-lg-4">
                    <div class="page-cau-chuyen-nnv-content-item">
                        <!-- Swiper -->
                        <div class="swiper cau-chuyen-nnv-swiper">
                            <div class="swiper-wrapper py-3">
                                <?php
                                foreach ($history_sca as $key => $v) {
                                ?>
                                    <!-- item start -->
                                    <div class="swiper-slide">
                                        <div class="cau-chuyen-nnv-swiper-item d-flex w-100 justify-content-between align-items-center">
                                            <div class="cau-chuyen-nnv-swiper-item-number">

                                                <div class="cau-chuyen-nnv-swiper-item-number-title">
                                                    <?= $v['ten'] ?>
                                                </div>
                                                <div class="cau-chuyen-nnv-swiper-item-number-des">
                                                    <?= $v['chucvu'] ?>
                                                </div>
                                            </div>
                                            <div class="cau-chuyen-nnv-swiper-item-box-line ">
                                                <div class="cau-chuyen-nnv-swiper-item-box-line-inside active"></div>
                                            </div>
                                        </div>


                                    </div>
                                    <!-- item end -->
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-8">
                    <div class="page-cau-chuyen-nnv-content-item">
                        <!-- Swiper -->
                        <div class="swiper cau-chuyen-nnv-swiper-main">
                            <div class="swiper-wrapper">
                                <?php
                                foreach ($history_sca as $key => $v) {
                                ?>
                                    <!-- item start -->
                                    <div class="swiper-slide">
                                        <div class="cau-chuyen-nnv-swiper-main-item">
                                            <div class="row align-items-center gy-4">
                                                <div class="col-12 col-lg-4 cau-chuyen-nnv-swiper-main-item-image-col">
                                                    <div class="cau-chuyen-nnv-swiper-main-item-image-box">
                                                        <div class="cau-chuyen-nnv-swiper-main-item-image ratio ratio-1x1">
                                                            <a href="<?= Img($v['hinh_anh']) ?>" data-fancybox="anh-cau-chuyen">
                                                                <img class="hvr-grow-rotate" src="<?= Img($v['hinh_anh']) ?>" alt="ảnh câu chuyện">
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-8">
                                                    <div class="cau-chuyen-nnv-swiper-main-item-content-box">
                                                        <div class="cau-chuyen-nnv-swiper-main-item-content p-3 p-lg-5">
                                                            <?= $v['noi_dung'] ?>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                    <!-- item end -->
                                <?php } ?>
                            </div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
<!-- lịch sử sca end -->


<!-- mạng lưới phủ sóng start -->
<section class="sca-network-section ">
    <div class="container">
        <div class="sca-network-content">
            <div class="sca-network-content-title text-center mb-3 sca-core-values-content-subitem-title" data-aos="fade-up" data-aos-duration="500" data-aos-delay="0" data-aos-once="true">
                <?= $network['ten'] ?>
            </div>
            <div class="sca-network-content-des sca-core-values-content-subitem-des text-center" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200" data-aos-once="true">
                <?= $network['noi_dung'] ?>
            </div>
        </div>
    </div>
</section>
<!-- mạng lưới phủ sóng end -->

<!-- hình quảng cáo -->
<section class="sca-background-ad pb-0">
    <div class="sca-background-ad-image ratio">
        <a <?= vanhiep_link($picture_ad) ?>>
            <img class="" data-src="<?= Img($picture_ad['hinh_anh']) ?>" alt="Ảnh banner quảng cáo">
        </a>
    </div>
</section>