<?php
if ($com == 'tags') {
    $tags = addslashes($_REQUEST['alias']);
    $query = $d->simple_fetch("select * from #_tags where alias = '$tags'");
    $tintuc = $d->o_fet("select  * from #_tintuc where hien_thi = 1 and tags_hienthi like '%" . $query['ten'] . "%' order by so_thu_tu asc, id desc");
} else {
    // cate tuyển dụng id start
    $category_recruitment = $d->o_fet("select id_code from #_category where module = 29 " . _where_lang);
    $category_recruitment_id_array = "";
    foreach ($category_recruitment as $key => $v) {
        $category_recruitment_id_array .= $v['id_code'] . ",";
    }
    $category_recruitment_id = rtrim($category_recruitment_id_array, ",");
    // cate tuyển dụng id end


    $id_loai = $row['id_code'] . $d->getIdsub($row['id_code']);
    $total_records = $d->num_rows("select * from #_tintuc where id_loai in ($id_loai) and id_loai not in ($category_recruitment_id)  and noi_bat = 0 and hien_thi = 1 " . _where_lang . " order by so_thu_tu ASC, id DESC");
    $limit = 6;
    $total_page = ceil($total_records / $limit);
    $tintuc = $d->o_fet("select * from #_tintuc where id_loai in ($id_loai) and id_loai not in ($category_recruitment_id) and noi_bat = 0 and hien_thi = 1  " . _where_lang . " order by so_thu_tu ASC, id DESC limit 0,$limit ");
}

// lấy tin tức nổi bật
$hot_new_list = $d->o_fet("select * from #_tintuc where hien_thi = 1 and noi_bat = 1 and id_loai not in ($category_recruitment_id) " . _where_lang . " order by so_thu_tu asc, id desc limit 0,4");
$author_main = $d->simple_fetch("select ho_ten from #_user where id = {$hot_new_list[0]['id_user']} ");
?>
<!-- breadcrumb start -->
<?php include 'module/sca-breadcrumb.php'; ?>
<!-- breadcrumb end -->
<!-- banner start -->
<?php include 'module/sca-banner.php'; ?>
<!-- banner end -->


<!-- tin nổi bật start -->
<section class="sca-hot-news-section">
    <div class="container">
        <div class="sca-hot-news-section-content">
            <div class="sca-hot-news-section-content-title mb-3 mb-lg-4">
                <?= $d->gettxt(4) ?>
            </div>
        </div>
        <!-- content start -->
        <div class="sca-hot-news-section-content-box">
            <div class="row row-cols-1 row-cols-lg-2 gx-4 gy-3">
                <div class="col">
                    <div class="sca-hot-news-section-content-box-item">
                        <div class="sca-hot-news-section-content-box-item-main-image ratio ratio-16x9 mb-3 mb-lg-0">
                            <a href="<?= URLLANG . $hot_new_list[0]['alias'] . ".html" ?>">
                                <img class="hvr-grow" data-src="<?= Img($hot_new_list[0]['hinh_anh']) ?>" alt="ảnh tin tức">
                            </a>
                        </div>
                        <!-- content start -->
                        <div class="sca-hot-news-section-content-box-item-main-content d-block d-lg-none">
                            <div class="sca-hot-news-section-content-box-item-main-content-title mb-1">
                                <a href="<?= URLLANG . $hot_new_list[0]['alias'] . ".html" ?>" class="catchuoi2">
                                    <?= $hot_new_list[0]['ten'] ?>
                                </a>
                            </div>
                            <!-- ngày -->
                            <div class="sca-hot-news-section-content-box-subitem-date mb-1 d-flex justify-content-between align-items-center">
                                <div class="sca-hot-news-section-content-box-subitem-day">
                                    <?= $d->gettxt(216) . " " . date('d/m/Y', $hot_new_list[0]['ngay_dang']); ?>
                                </div>
                                <div class="sca-hot-news-section-content-box-subitem-author">
                                    <?= $d->gettxt(270) ?>: <?= $author_main['ho_ten'] ?>
                                </div>
                            </div>
                            <div class="sca-hot-news-section-content-box-item-main-content-des catchuoi2">
                                <?= $hot_new_list[0]['mo_ta'] ?>
                            </div>
                        </div>
                        <!-- content end -->
                    </div>
                </div>
                <div class="col">
                    <div class="sca-hot-news-section-content-box-item">
                        <div class="row row-cols-1 gy-4 gy-lg-2">
                            <?php
                            foreach ($hot_new_list as $key => $v) {
                                if ($key > 0) {
                                    $author = $d->simple_fetch("select ho_ten from #_user where id = {$v['id_user']} ");
                            ?>
                                    <!-- item start -->
                                    <div class="col">
                                        <div class="sca-hot-news-section-content-box-subitem">
                                            <div class="row gx-4 gy-3">
                                                <div class="col-12 col-lg-4">
                                                    <div class="sca-hot-news-section-content-box-subitem-item">
                                                        <div class="sca-hot-news-section-content-box-subitem-image ratio ratio-16x9">
                                                            <a href="<?= URLLANG . $v['alias'] . ".html" ?>" title="<?= $v['ten'] ?>">
                                                                <img class="hvr-grow" data-src="<?= Img($v['hinh_anh']) ?>" alt="ảnh tin tức nổi bật">
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12 col-lg-8">
                                                    <div class="sca-hot-news-section-content-box-subitem-item">
                                                        <!-- tên -->
                                                        <div class="sca-hot-news-section-content-box-subitem-title mb-1">
                                                            <a href="<?= URLLANG . $v['alias'] . ".html" ?>" title="<?= $v['ten'] ?>" class="">
                                                                <?= pnvn_trim($v['ten'], 30) ?>
                                                            </a>
                                                        </div>
                                                        <!-- ngày -->
                                                        <div class="sca-hot-news-section-content-box-subitem-date mb-1 d-flex justify-content-between align-items-center">
                                                            <div class="sca-hot-news-section-content-box-subitem-day">
                                                                <?= $d->gettxt(216) . " " . date('d/m/Y', $v['ngay_dang']); ?>
                                                            </div>
                                                            <div class="sca-hot-news-section-content-box-subitem-author">
                                                                <?= $d->gettxt(270) ?>: <?= $author['ho_ten'] ?>
                                                            </div>
                                                        </div>
                                                        <!-- mô tả -->
                                                        <div class="sca-hot-news-section-content-box-subitem-des catchuoi2">
                                                            <?= $v['mo_ta'] ?>
                                                        </div>
                                                    </div>
                                                </div>
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
        <!-- content end -->
    </div>
</section>
<!-- tin nổi bật end -->


<!-- Tin tức content -->
<section class="sca-news-section-page">
    <div class="container ">
        <div class="sca-news-section-page-content">
            <!-- title start -->
            <div class="sca-hot-news-section-content-title mb-3 mb-lg-4">
                <?= $row['ten'] ?>
            </div>
            <!-- title end -->
        </div>
        <div class="row gx-5">
            <!-- nội dung content -->
            <div class="col-12 ">
                <div class="row row-cols-1 row-cols-md-2 gx-5 gy-4 sca-news-section-page-content-result" id="result">

                </div>
                <!-- Ajax pagination start -->
                <div class="pagination-page text-center pt-3 pb-3 d-flex justify-content-center">
                    <ul id="pagination-ajax" class="pagination-sm"></ul>
                </div>
                <!-- Ajax pagination end -->
            </div>

        </div>
    </div>

</section>


<script type="text/javascript" src="templates/js/jquery.twbsPagination.js"></script>
<script type="text/javascript">
    jQuery(document).ready(function($) {
        var chuyenmuc = '<?= $id_loai ?>';
        $('#pagination-ajax').twbsPagination({
            totalPages: <?= $total_page ?>,
            visiblePages: 2,
            prev: '<span aria-hidden="true">&laquo;</span>',
            next: '<span aria-hidden="true">&raquo;</span>',
            onPageClick: function(event, page) {
                $.ajax({
                    url: "sources/ajax/ajax-pagination.php",
                    type: 'POST',
                    data: {
                        page: page,
                        totalPages: '<?= $total_page ?>',
                        chuyenmuc: chuyenmuc,
                        limit: '<?= $limit ?>',
                        do: 'pagination_tintuc'
                    },
                    success: function(data) {
                        //console.log(data);
                        $('#result').html(data);
                    }
                })
            }
        });
    });
</script>