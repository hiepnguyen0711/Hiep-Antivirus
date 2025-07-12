<?php
$contact_info = $d->getContent(1057);
// danh sách tuyển dụng hot
$category_recruitment = $d->o_fet("select id_code from #_category where module = 29 " . _where_lang);
$category_recruitment_id_array = "";
foreach ($category_recruitment as $key => $v) {
    $category_recruitment_id_array .= $v['id_code'] . ",";
}
$category_recruitment_id = rtrim($category_recruitment_id_array, ",");
$recruitment_list_hot = $d->o_fet("select * from #_tintuc where hien_thi = 1 and id_loai in ($category_recruitment_id) and noi_bat = 1 " . _where_lang . " order by so_thu_tu asc, id desc limit 0,3");

// liên hệ form orange
$contact_form_orange = $d->getContent(1072);


?>

<!-- breadcrumb start -->
<?php include 'module/sca-breadcrumb.php'; ?>
<!-- breadcrumb end -->
<!-- banner start -->
<?php include 'module/sca-banner.php'; ?>
<!-- banner end -->

<!-- thông tin liên hệ và bản đồ start -->
<section class="sca-contact-page-section">
    <div class="container">
        <div class="sca-contact-page-section-content">
            <div class="row gx-5 gy-3">
                <div class="col-12 col-lg-5">
                    <div class="sca-contact-page-section-content-item">
                        <!-- title start -->
                        <div class="sca-contact-page-section-content-item-title mb-3">
                            <?= $contact_info['ten'] ?>
                        </div>
                        <!-- title end -->

                        <!-- content start -->
                        <div class="sca-contact-page-section-content-item-des">
                            <?= $contact_info['noi_dung'] ?>
                        </div>
                        <!-- content end -->
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="sca-contact-page-section-content-item ps-lg-5">
                        <div class="sca-contact-page-section-content-item-map ratio ratio-21x9">
                            <?= _bando ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- thông tin liên hệ và bản đồ end -->


<!-- tin tuyển dụng start -->
<section class="sca-contact-new-recruitment-section">
    <div class="container">
        <div class="sca-contact-new-recruitment-section-content">
            <!-- title start -->
            <div class="sca-contact-new-recruitment-section-content-title mb-3">
                <?= $d->gettxt(275) ?>
            </div>
            <!-- title end -->

            <!-- content start -->
            <div class="sca-contact-new-recruitment-section-content-box">
                <!-- thanh tìm kiếm start -->
                <div class="sca-contact-new-recruitment-section-content-box-find-filter mb-3">
                    <div class="row gy-3">
                        <div class="col-12 col-lg-8">
                            <div class="sca-content-box-find-filter-item">
                                <div class="row row-cols-2 row-cols-lg-4 gy-2">
                                    <div class="col">
                                        <div class="sca-content-box-find-filter-subitem">
                                            <select name="find_news" class="form-select sca-lien-he-tin-tuc-tim-kiem" id="find_news">
                                                <option selected><?= $d->gettxt(225) ?></option>
                                                <option value="1"><?= $d->gettxt(210) ?></option>
                                                <option value="2"><?= $d->gettxt(275) ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="sca-content-box-find-filter-subitem">
                                            <select name="find_news_child" class="form-select" id="find_news_child">
                                                <option selected><?= $d->gettxt(276) ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="sca-content-box-find-filter-subitem">
                                            <input type="text" name="find_text_search" class="form-control" id="exampleFormControlInput1" placeholder="<?= $d->gettxt(277) ?>">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="sca-content-box-find-filter-subitem">
                                            <button type="button" class="btn sca-contact-btn-find">
                                                <?= $d->gettxt(225) ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="sca-content-box-find-filter-item">
                                <div class="sca-content-box-find-filter-item-title text-center px-2">
                                    <?= $d->gettxt(278) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- thanh tìm kiếm end -->

                <!-- content box start -->
                <div class="sca-contact-recruitment-box-content" id="result-tim-kiem-tin-tuc">
                    <div class="row gy-3">
                        <div class="col-12 col-lg-8">
                            <div class="sca-contact-recruitment-box-content-item">
                                <!-- hình -->
                                <div class="sca-contact-recruitment-box-content-item-image mb-3 ratio ratio-16x9">
                                    <a href="<?= URLLANG . $recruitment_list_hot[0]['alias'] . ".html" ?>" title="<?= $recruitment_list_hot[0]['ten'] ?>">
                                        <img class="hvr-grow" data-src="<?= Img($recruitment_list_hot[0]['hinh_anh']) ?>" alt="ảnh tin tức">
                                    </a>
                                </div>
                                <!-- tên -->
                                <div class="sca-contact-recruitment-box-content-item-name mb-2">
                                    <a href="<?= URLLANG . $recruitment_list_hot[0]['alias'] . ".html" ?>" class="catchuoi2">
                                        <?= $recruitment_list_hot[0]['ten'] ?>
                                    </a>
                                </div>
                                <!-- ngày đăng -->
                                <div class="sca-contact-recruitment-box-content-item-date mb-2">
                                    <?= date('d/m/Y', $recruitment_list_hot[0]['ngay_dang']) ?>
                                </div>
                                <!-- mô tả -->
                                <div class="sca-contact-recruitment-box-content-item-des catchuoi2">
                                    <?= $recruitment_list_hot[0]['mo_ta'] ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="sca-contact-recruitment-box-content-item">
                                <div class="row row-cols-1 gy-3">
                                    <?php
                                    foreach ($recruitment_list_hot as $key => $v) {
                                        if ($key > 0) {
                                    ?>
                                            <!-- item start -->
                                            <div class="col">
                                                <div class="sca-contact-recruitment-box-content-item">
                                                    <!-- hình -->
                                                    <div class="sca-contact-recruitment-box-content-item-image mb-3 ratio ratio-16x9">
                                                        <a href="<?= URLLANG . $v['alias'] . ".html" ?>" title="<?= $v['ten'] ?>">
                                                            <img class="hvr-grow" data-src="<?= Img($v['hinh_anh']) ?>" alt="ảnh tin tức">
                                                        </a>
                                                    </div>
                                                    <!-- tên -->
                                                    <div class="sca-contact-recruitment-box-content-item-name mb-2">
                                                        <a href="<?= URLLANG . $v['alias'] . ".html" ?>" class="catchuoi2">
                                                            <?= $v['ten'] ?>
                                                        </a>
                                                    </div>
                                                    <!-- ngày đăng -->
                                                    <div class="sca-contact-recruitment-box-content-item-date mb-2">
                                                        <?= date('d/m/Y', $v['ngay_dang']) ?>
                                                    </div>
                                                    <!-- mô tả -->
                                                    <div class="sca-contact-recruitment-box-content-item-des catchuoi2">
                                                        <?= $v['mo_ta'] ?>
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
                <!-- content box end -->


            </div>
            <!-- content end -->
        </div>
    </div>
</section>
<!-- tin tuyển dụng end -->


<!-- liên hệ start -->
<section class="sca-contact-orange p-0">
    <div class="row row-cols-1 row-cols-lg-2 g-0">
        <div class="col">
            <div class="sca-contact-orange-item p-3 p-lg-5">
                <div class="sca-contact-orange-item-form">
                    <!-- title start -->
                    <div class="sca-contact-orange-item-form-title mb-3 text-center">
                        <?= $contact_form_orange['ten'] ?>
                    </div>
                    <!-- title end -->
                    <form action="" method="post">
                        <input type="hidden" name="pnvn_token" value="<?= $_SESSION['token'] ?>">
                        <div class="form-floating mb-3">
                            <input type="text" name="ho_ten" required class="form-control" id="floatingInput" placeholder="name@example.com">
                            <label for="floatingInput"><?= $d->gettxt(5) ?></label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" name="dien_thoai" required class="form-control" id="floatingInput" placeholder="name@example.com">
                            <label for="floatingInput"><?= $d->gettxt(6) ?></label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" name="email" required class="form-control" id="floatingInput" placeholder="name@example.com">
                            <label for="floatingInput"><?= $d->gettxt(268) ?></label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" name="dia_chi" class="form-control" id="floatingInput" placeholder="name@example.com">
                            <label for="floatingInput"><?= $d->gettxt(7) ?></label>
                        </div>
                        <div class="text-center sca-orange-form-button">
                            <button type="submit" name="lienhe_nocaptcha" class="btn sca-orange-form-btn py-2 hvr-float-shadow">
                                <?= $d->gettxt(73) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="sca-contact-orange-item">
                <div class="sca-contact-orange-item-image ratio ratio-4x3">
                    <img data-src="<?= Img($contact_form_orange['hinh_anh']) ?>" alt="ảnh form liên hệ">
                </div>
            </div>
        </div>
    </div>
</section>
<!-- liên hệ end -->



<script>
    $(document).ready(function() {
        $("body").on("change", ".sca-lien-he-tin-tuc-tim-kiem", function(event) {
            var currentElement = event.currentTarget;
            var parent = $(currentElement).parents(".sca-contact-new-recruitment-section-content-box");

            var value = $(this).val();
            // load loại hình start
            $.ajax({
                url: 'sources/ajax/ajax_load_loai_hinh.php',
                type: "post",
                dataType: "text",
                data: {
                    loai_hinh: value
                },
                success: function(response) {
                    console.log(response);
                    $("#find_news_child").html(response);
                },
                error: function(e) {
                    console.log(e);
                }
            })
            // load loại hình end
        });


        // xử lý nút tìm kiếm start
        $("body").on("click", ".sca-contact-btn-find", function(event) {
            var currentElement = event.currentTarget;
            var parent = $(currentElement).parents(".sca-contact-new-recruitment-section-content-box");

            var loai_hinh = parseInt(parent.find(".sca-lien-he-tin-tuc-tim-kiem").val());
            var id_loai = parseInt(parent.find("#find_news_child").val());


            $(".sca-loading").addClass("active");

            setTimeout(function() {
                $(".sca-loading").removeClass("active");
                $.ajax({
                    url: "sources/ajax/ajax_load_tin_tuc_loai_hinh.php",
                    type: "post",
                    dataType: "text",
                    data: {
                        loai_hinh: loai_hinh,
                        id_loai: id_loai
                    },
                    success: function(response) {
                        $("#result-tim-kiem-tin-tuc").html(response);
                    },
                    error: function(e) {
                        console.log(e);
                    }
                })
            }, 1200);

        });

        // xử lý nút tìm kiếm end

    });
</script>