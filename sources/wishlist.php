<?php
if(!isset($_SESSION))
{
    session_start();
}
if(!isset($_SESSION['id_login'])){
    $d->location(URLPATH.$d->getCate(21,'alias').".html");
    exit();
}

if(isset($search['del']) and $search['token'] == $_SESSION['token']){
    $id_luu = (int)$search['del'];
    $d->o_que("delete from #_luu_sp where id = ".$id_luu." ");
    $d->redirect(URLPATH.$com.'.html');
}

?>
<main>
    <!-- page-banner-area-start -->
     <div class="page-banner-area page-banner-height-2" data-background="img_data/images/<?=$row['banner']?>">
        <div class="container">
            <div class="row">
                <div class="col-xl-12">
                    <div class="page-banner-content text-center">
                        <h4 class="breadcrumb-title"><?=$row['ten']?></h4>
                        <div class="breadcrumb-two">
                            <nav>
                                <nav class="breadcrumb-trail breadcrumbs">
                                    <ul class="breadcrumb-menu">
                                        <li class="breadcrumb-trail">
                                            <a href="<?=URLPATH?>"><span><?=$d->getTxt(11)?></span></a>
                                        </li>
                                        <li class="trail-item">
                                            <span><?=$row['ten']?></span>
                                        </li>
                                    </ul>
                                </nav>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- page-banner-area-end -->
    <!-- cart-area-start -->
    <section class="cart-area pb-120 pt-120">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <form action="#">
                        <div class="table-content table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="product-thumbnail">Hình ảnh</th>
                                        <th class="cart-product-name">Sản phẩm</th>
                                        <th class="product-price">Giá</th>
                                        <th class="product-quantity"></th>
                                        <th class="product-remove">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanpham_luu as $key => $value) {?>
                                    <tr>
                                        <td class="product-thumbnail">
                                            <a href="<?=URLPATH.$value['alias']?>.html">
                                                <img src="img_data/images/<?=$value['hinh_anh']?>" alt="<?=$value['ten']?>">
                                            </a>
                                        </td>
                                        <td class="product-name">
                                            <a href="<?=URLPATH.$value['alias']?>.html"><?=$value['ten']?></a>
                                        </td>
                                        <td class="product-price"><span class="amount"><?=  numberformat($value['gia0'])?><sup>đ</sup></span></td>
                                        <td class="product-quantity">
                                            <a class="tp-btn-h1" href="<?=URLPATH.$value['alias']?>.html">Mua ngay</a>
                                        </td>
                                        <td class="product-remove"><a onClick="if(!confirm('Xác nhận xóa?')) return false;"  href="<?=URLPATH.$com?>.html?del=<?=$value['id_luu']?>&token=<?=$_SESSION['token']?>"><i class="fa fa-times"></i></a></td>
                                    </tr>   
                                    <?php } ?>
                                    
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- cart-area-end -->
</main>