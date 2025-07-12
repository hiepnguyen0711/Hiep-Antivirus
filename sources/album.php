<?php
$list_album = $d->o_fet("select * from #_album where hien_thi = 1 " . _where_lang . " order by so_thu_tu asc");
$all_images = $d->o_fet("select * from #_album_hinhanh");
?>

<!-- Breadcrumb -->
<?php include 'module/bee-breadcrumb.php'; ?>

<!-- Banner start -->
<?php
$banner = "";
if (!empty($row['hinh_anh'])) {
    $banner = $row['hinh_anh'];
} else {
    $banner_id = $d->getContent(798);
    $banner = $banner_id['hinh_anh'];
}
?>
<section class="bee-about-us-banner" style="background-image: url('<?= Img($banner) ?>');">
    <h1><?= $row['ten']; ?></h1>
</section>
<!-- Banner end -->


<div class="bee-feedback-page">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 gx-5 gy-4">
            <?php
            foreach ($list_album as $key => $v) {
            ?>
                <div class="col bee-feedback-item">
                    <!-- card start -->
                    <div class="bee-card ">
                        <a href="img_data/images/<?= $v['hinh_anh'] ?>" data-fancybox="images">
                            <img class="bee-album-img" data-src="img_data/images/<?= $v['hinh_anh'] ?>" alt="image">
                        </a>
                        <p class="bee-album-logo">BEEMANOR
                        </p>
                    </div>
                    <!-- bee-card end -->

                </div>
            <?php } ?>
        </div>
    </div>
</div>