<?php
// Slider Main
$slider = $d->getContents(844);

?>
<?php if ($source == 'index' ) { ?>
    <section class="hq-slider p-0">
        <div class="hq-slider-content nb-slider-content">
            <!-- Swiper -->
            <div class="swiper hq-swiper-slider">
                <div class="swiper-wrapper">
                    <?php
                    foreach ($slider as $key => $v) {
                    ?>
                        <!-- item start -->
                        <div class="swiper-slide">
                            <div class="hq-swiper-slider-item-image ratio ratio-16x9">
                                <a <?= vanhiep_link($v) ?>>
                                    <img  src="<?= Img($v['hinh_anh']) ?>" alt="Ảnh Slider">
                                </a>
                            </div>
                        </div>
                        <!-- item end -->
                    <?php } ?>
                </div>

            </div>
          

        </div>
    </section>
<?php } ?>