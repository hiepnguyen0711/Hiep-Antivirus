<?php

?>

<!-- id: 77777 tin tức detail start -->
<?php
// lấy danh sách tin tức ngẫu nhiên
$news_related_list = $d->o_fet("select ten,alias from #_tintuc where hien_thi = 1 and id_code != {$row['id_code']} " . _where_lang . " order by RAND() limit 0,5");
?>
<section class="vh-news-detail">
    <div class="container">
        <div class="vh-news-detail-content">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <div class="vh-news-detail-content-item vh-news-detail-content-item-left-content h-100 d-flex flex-column">
                        <!-- banner -->
                        <div class="vh-news-detail-content-item-banner-image ratio ratio-16x9 mb-3">
                            <a href="<?= Img($row['hinh_anh']) ?>" data-fancybox data-caption="<?= $row['ten'] ?>">
                                <img src="<?= Img($row['hinh_anh']) ?>" alt="<?= $row['ten'] ?>">
                            </a>
                        </div>
                        <!-- category -->
                        <?php
                        $category_row = $d->getCate($row['id_loai']);
                        ?>
                        <div class="vh-news-detail-content-item-category mb-2">
                            <a href="<?= URLLANG . $category_row['alias'] . ".html" ?>">
                                <?= $category_row['ten'] ?>
                            </a>
                        </div>
                        <!-- name -->
                        <div class="vh-news-detail-content-item-name mb-2">
                            <?= $row['ten'] ?>
                        </div>
                        <!-- author and date -->
                        <div class="vh-news-detail-author-date d-flex flex-nowrap mb-3 justify-content-lg-start align-items-center">
                            <div class="vh-news-detail-author">
                                <?php
                                // lấy tên tác giả
                                $author = $d->simple_fetch("select ho_ten from #_user where id = {$row['id_user']}");
                                echo "By " . $author['ho_ten'];
                                ?>
                            </div>
                            <i class="fa-solid fa-circle mx-2"></i>
                            <div class="vh-news-detail-date">
                                <?= date("d/m/Y", $row['ngay_dang']) ?>
                            </div>
                        </div>

                        <!-- content start -->
                        <div class="vh-news-detail-content-detail vanhiep-content mb-3">
                            <?= content_mucluc($row['noi_dung'], $url_page) ?>
                        </div>
                        <!-- content end -->

                        <!-- tag start -->
                        <?php
                        if (!empty($row['tags'])) {
                            $array_tags = explode(",", $row['tags']);
                        ?>
                            <div class="vh-news-detail-tags-group d-flex align-items-center">
                                <?php
                                foreach ($array_tags as $key => $v) {
                                    // lấy alias
                                    $tag_alias = $d->simple_fetch("select * from #_tags where id = {$v} ");
                                ?>
                                    <!-- item start -->
                                    <a href="<?= URLLANG . "tags.html?alias=" . $tag_alias['alias'] ?>">
                                        <div class="vh-news-detail-tags-item d-inline-flex px-3 py-2 me-2">
                                            <?= $tag_alias['ten'] ?>
                                        </div>
                                    </a>
                                    <!-- item end -->
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <!-- tag end -->
                        <!--  -->
                    </div>
                </div>
                <div class="col-12 col-lg-4 d-none d-lg-block ">
                    <div class="vh-news-detail-content-item h-100 ps-lg-4">
                        <!-- form  search start -->
                        <div class="vh-news-detail-content-item-search">
                            <form action="" method="post">
                                <input type="hidden" name="com" value="search">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="textsearch" placeholder="Search..." aria-label="Search..." aria-describedby="button-addon2">
                                    <button class="btn vh-news-detail-btn-search" type="submit" id="button-addon2"><i class="fa-solid fa-magnifying-glass"></i></button>
                                </div>
                            </form>
                        </div>
                        <!-- form search end -->

                        <!-- tin tức ngẫu nhiên start -->
                        <?php
                        if (!empty($news_related_list)) {
                        ?>
                            <div class="vh-news-details-right-random d-flex flex-column">
                                <div class="vh-news-details-right-random-title mb-1">
                                    <?= $d->gettxt(290) ?>
                                </div>
                                <div class="vh-news-details-right-random-content">
                                    <div class="row row-cols-1 g-0">
                                        <?php
                                        foreach ($news_related_list as $key => $v) {
                                        ?>
                                            <!-- item start -->
                                            <div class="col">
                                                <div class="vh-news-details-right-random-item py-3">
                                                    <a href="<?= URLLANG . $v['alias'] . ".html" ?>" class="catchuoi2">
                                                        <?= $v['ten'] ?>
                                                    </a>
                                                </div>
                                            </div>
                                            <!-- item end -->
                                        <?php } ?>
                                    </div>
                                </div>

                            </div>
                        <?php } ?>
                        <!-- tin tức ngẫu nhiên end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- tin tức detail end -->