<?php
$link = explode("?", $_SERVER['REQUEST_URI']);
if ($link[1] != '') {
    $vari = explode("&", $link[1]);
    $search = array();
    foreach ($vari as $item) {
        $str = explode("=", $item);
        $search["$str[0]"] = $str[1];
    }
}
$hinh_anh_sp    =   $d->o_fet("select * from #_sanpham_hinhanh where id_sp = " . $row['id_code'] . " ");
$ma_sp          =   $row['ma_sp'];
$soluong_con    =   $row['so_luong'];
$id_loai = $category['id_code'] . $d->getIdsub($category['id_code']);
$sanphamcl = $d->o_fet("select * from #_sanpham where id_loai in ($id_loai) and hien_thi =1 " . _where_lang . " and id <> " . $row['id'] . " order by so_thu_tu ASC, id DESC limit 0,4 ");

if (isset($_POST['addcart'])) {
    $id_pro = $_POST['id_sp'];
    $soluong = $_POST['soluong'];
    // var_dump($soluong."+++".$id_pro);
    // die();
    $d->addtocart($soluong, $id_pro);
    header("Location:" . URLLANG . "gio-hang.html");
}
// dd($url_page);
?>

<link href="<?= URLPATH ?>templates/module/magiczoomplus/magiczoomplus.css" rel="stylesheet" />
<script src="<?= URLPATH ?>templates/module/magiczoomplus/magiczoomplus.js"></script>


<div class="pnvn-sanpham-chitiet">
    <div class="container">
        <div class="sanpham-chitiet-left">
            <div class="sanpham-chitiet-left-top">
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    <div class="accordion-item">
                        <h3>Tất cả sản phẩm</h3>
                        <h2 class="accordion-header" id="flush-headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                                <a href="#"> Accordion Item #1</a>
                            </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">
                                <ul>
                                    <li><a href="#">Danh mục con 1</a></li>
                                    <li><a href="#">Danh mục con 2</a></li>
                                    <li><a href="#">Danh mục con 3</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header pnvn-accordion-header" id="flush-headingTwo">
                            <a href="#"> Accordion Item #2</a>
                        </h2>

                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false" aria-controls="flush-collapseThree">
                                <a href="#"> Accordion Item #3</a>
                            </button>
                        </h2>
                        <div id="flush-collapseThree" class="accordion-collapse collapse" aria-labelledby="flush-headingThree" data-bs-parent="#accordionFlushExample">
                            <div class="accordion-body">Placeholder content for this accordion, which is intended to demonstrate the <code>.accordion-flush</code> class. This is the third item's accordion body. Nothing more exciting happening here in terms of content, but just filling up the space to make it look, at least at first glance, a bit more representative of how this would look in a real-world application.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sanpham-chitiet-left-bottom"></div>
        </div>
        <div class="sanpham-chitiet-right">
            <!--  -->
            <div class=" pnvn-sanpham-content">
                <div class="container">
                    <!-- slider sp -->
                    <div class="pnvn-slider-sanpham">
                        <div class="swiper pnvnSwiper">
                            <div class="swiper-wrapper">
                                <div class="swiper-slide">
                                    <div class="magiczoom"></div>
                                    <a class="MagicZoom magiczoom-fancybox" data-zoom-image="<?= URLPATH ?>img_data/images/cergy/sanpham/1.webp" data-fancybox="gallery" href="<?= URLPATH ?>img_data/images/cergy/sanpham/1.webp">
                                        <img src="<?= URLPATH ?>img_data/images/cergy/sanpham/1.webp"></a>
                                </div>
                                <div class="swiper-slide"><img src="<?= URLPATH ?>img_data/images/cergy/sanpham/2.webp"></div>
                                <div class="swiper-slide"><img src="<?= URLPATH ?>img_data/images/cergy/sanpham/3.webp"></div>
                                <div class="swiper-slide"><img src="<?= URLPATH ?>img_data/images/cergy/sanpham/4.webp"></div>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                    <!-- slider sp -->
                    <div class="pnvn-thongtin-sanpham ">
                        <h3>Mã sản phẩm: CABIN-NH343T</h3>
                        <h1>Đây là tên sản phẩm special, sản phẩm được sản xuất bởi phương nam vina</h1>
                        <h4>Nếu bạn muốn giá tốt hãy liên hệ với chúng tôi</h4>
                        <div class="thongso-sanpham">
                            <table>
                                <tr>
                                    <td>Minimum Order :</td>
                                    <td><span class="roboc-b">1 piece</span></td>
                                </tr>
                                <tr>
                                    <td>Supply Ability :</td>
                                    <td><span class="roboc-b">3000piece / Month</span></td>
                                </tr>
                                <tr>
                                    <td>Stock Time :</td>
                                    <td><span class="roboc-b">15Day</span></td>
                                </tr>
                                <tr>
                                    <td>Country of Origin :</td>
                                    <td><span class="roboc-b">China</span></td>
                                </tr>

                            </table>
                        </div>
                        <div class="sanpham-soluong">
                            <form method="POST" action="" id="form-cart">
                                <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                                <input type="hidden" value="<?= $row['id_code'] ?>" name="id_sp" />

                                <div class="cart-option mb-15" id="html_dathang">
                                    <div class="product-quantity mr-20 btn-soluong" style="display: flex;">
                                        <span class="soluong-title">Số lượng: </span>

                                        <div class="cart-plus-minus p-relative">
                                            <div class="dec qtybutton">-</div>
                                            <input type="text" value="1" name="soluong" id="soluong" class="soluong">
                                            <div class="inc qtybutton">+</div>
                                        </div>
                                    </div>
                                    <div class="btn-muangay-group">
                                        <button class="cart-btn addcartnow btn-themvaogio" type="button" data-id=<?= $row['id_code'] ?>><?= $d->getTxt(78) ?></button>
                                        <button class="cart-btn addcart" type="submit" name="addcart" data-id=<?= $row['id_code'] ?>><?= $d->getTxt(77) ?></button>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
                <!--  -->
                <div class="product-details-comment">
                    <nav>
                        <div class="nav nav-tabs" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Thông tin sản phẩm</button>
                            <button class="nav-link" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">Bình luận</button>
                        </div>
                    </nav>
                    <div class="tab-content" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">...</div>
                        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            <div class=" py-3">
                                <div class="fb-comments" data-href="http://127.0.0.1/thang8_2023/cergy/tom-hum-bong-734.html" data-width="" data-numposts="5"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--  -->
                <div class="sanphamdownload">
                    <h2>Software & Document</h2>
                    <div class="sanphamdownload-content">
                        <div class="zip-download ">
                            <a href="#"><img class="hvr-buzz" src="<?= URLPATH ?>img_data/images/cergy/zip.png" title="Tải xuống với file Zip">
                                <h6>Monitor software</h6>
                            </a>
                        </div>
                        <div class="zip-download">
                            <a href="#"> <img class="hvr-buzz" src="<?= URLPATH ?>img_data/images/cergy/pdf.png" title="Tải xuống với file PDF">
                                <h6>Specification</h6>
                            </a>
                        </div>
                        <div class="zip-download">
                            <a href="#"> <img class="hvr-buzz" src="<?= URLPATH ?>img_data/images/cergy/android.png" title="Tải xuống với file APK">
                                <h6>Bluetooth</h6>
                            </a>
                        </div>
                    </div>
                </div>
                <!--  -->
                <section class="sanphamcungloai">
                    <div class="title-main">
                        <h3><?= $d->getTxt(131) ?></h3>
                    </div>

                    <div class="owl-carousel owl-theme owl-spcl">
                        <?php foreach ($sanphamcl as $v) { ?>
                            <?php include "module/sanpham-cungloai-item.php"; ?>

                        <?php } ?>
                    </div>
                </section>
            </div>
            <!--  -->
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(".magiczoom-fancybox").on("click", function(e) {
            e.preventDefault();

            var imageUrl = $(this).attr("href");

            // Apply MagicZoom functionality
            $(this).addClass("MagicZoom").attr("data-options", "zoom-position: inner").attr("data-zoom-image", imageUrl);

            // Open Fancybox
            $.fancybox.open({
                type: "image",
                src: imageUrl
            });
        });
    });
</script>