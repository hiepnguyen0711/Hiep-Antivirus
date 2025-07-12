<?php
$id_loai = $row['id_code'] . $d->getIdsub($row['id_code']);
$sale = 0;
if ($row['id_code'] == 194) {
    $id_loai = '150' . $d->getIdSub(150);
    $sale = 1;
}
$total_records = $d->num_rows("select * from #_sanpham where id_loai in ($id_loai) and hien_thi =1 " . _where_lang . " order by so_thu_tu ASC, id DESC");
$limit = 10; //get_json('product', 'paging');
$total_page = ceil($total_records / $limit);
$sanpham = $d->o_fet("select * from #_sanpham where id_loai in ($id_loai) and hien_thi =1 " . _where_lang . " order by so_thu_tu ASC, id DESC limit 0,$limit ");
$id_product_cate = '113' . $d->getIdSub(113);
$category_product = "";
// dd($id_product_cate);

// dd($row);

// lấy danh sách menu sản phẩm
// $product_cate_id = 
$menu_product_navigation = $d->o_fet("select ten,alias from #_category where module = 3 and id_code != {$row['id_code']} and hien_thi = 1 " . _where_lang . " order by so_thu_tu asc, id desc");
$background_product_page = $d->getContent(921);
?>

<?php include 'module/nb-breadcrumb.php'; ?>

<section class="hq-product-page-section" >
    <div class="container">
        <!-- menu sản phẩm start -->
        <div class="hq-menu-product-page mb-3 py-3">
            <div class="hq-menu-product-page-content ">
                <!-- title start -->
                <div class="hq-review-section-content-top-item-name text-center mb-3 aos-init aos-animate" data-aos-once="true" data-aos="fade-up" data-aos-duration="500" data-aos-delay="400">
                    <?= $row['ten'] ?> </div>
                <!-- title end -->

                <!-- content start -->
                <div class="hq-menu-product-page-content-box">
                    <ul class="hq-menu-product-page-content-ul d-flex w-100 align-items-center justify-content-center flex-wrap">
                        <?php
                        foreach ($menu_product_navigation as $key => $v) {
                        ?>
                            <!-- item start -->
                            <li class="hq-menu-product-page-content-li mx-3">
                                <a href="<?= URLLANG . $v['alias'] . ".html" ?>" class="hq-menu-product-page-content-a hvr-underline-from-left text-center">
                                    <?= $v['ten'] ?>
                                </a>
                            </li>
                            <!-- item end -->
                        <?php } ?>
                    </ul>
                </div>
                <!-- content end -->
            </div>
        </div>
        <!-- menu sản phẩm end -->

        <!-- Content -->
        <div class="zc-product-page-content">
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 gx-5 gy-4" id="result">

            </div>
        </div>
        <!-- Pagination -->
        <div class="pagination-page text-center">
            <ul id="pagination-ajax" class="pagination-sm"></ul>
        </div>
        <!-- Pagination End -->
    </div>
</section>






<script type="text/javascript" src="templates/js/jquery.twbsPagination.js"></script>
<?php if ($total_page > -1) { ?>
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
                            sale: <?= $sale ?>,
                            do: 'pagination_sanpham'
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
<?php } ?>