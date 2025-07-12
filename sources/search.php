<?php
$keyword = validate_content(urldecode($_POST['textsearch']));
$id_danhmuc = isset($_GET['danhmuc_search']) ? $_GET['danhmuc_search'] : '';
$total_records = $d->num_rows("select * from #_sanpham where ten like '%" . $keyword . "%' and hien_thi =1 " . _where_lang . " order by so_thu_tu ASC, id DESC");
$limit = 24; //get_json('product', 'paging');
$total_page = ceil($total_records / $limit);
$where = '';
$where .= "ten like '%" . $keyword . "%'";
if ($id_danhmuc) {
    $where .= " and id_loai = " . $id_danhmuc . " ";
}
$sanpham = $d->o_fet("select * from #_sanpham where {$where} and hien_thi =1 " . _where_lang . " order by so_thu_tu ASC, id DESC limit 0,$limit ");
?>

<section class="">
    <div class="container">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 gx-5 gy-4" id="result">
            <?php foreach ($sanpham as $key => $v) { ?>
                <div class="col">
                    <?php include "module/nb-product-item.php"; ?>
                </div>
            <?php } ?>
        </div>
        <?php if ($total_page > 0) { ?>
            <div class="pagination-page text-center">
                <ul id="pagination-ajax" class="pagination-sm"></ul>
            </div>
        <?php } ?>
    </div>

</section>


<?php if ($total_page > 1) { ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var keyword = '<?= $keyword ?>';
            var id_danhmuc = '<?= $id_danhmuc ?>';
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
                            key: keyword,
                            iddm: id_danhmuc,
                            limit: '<?= $limit ?>',
                            do: 'pagination_sanpham2'
                        },
                        success: function(data) {
                            console.log(data);
                            $('#result').html(data);
                        }
                    })
                }
            });
        });
    </script>
<?php } ?>