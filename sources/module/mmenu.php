<?php
$menu = "<li><a href='" . URLPATH . "' title='Trang chủ'>" . 'Trang chủ' . "</a></li>";
$nav  = $d->o_fet("select * from #_category where (menu = 1 or (module = 3 and id_loai = 0)) and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
// print_r($nav);
foreach ($nav as $item) {

    $sub = $d->o_fet("select * from #_category where id_loai={$item['id_code']} and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
    if (count($sub) > 0) {
        // Menu cấp 1 tạo menu con cấp 2
        $menusub = ""; // đây là khởi tạo menu con cấp 3
        foreach ($sub as $key => $item1) { // nếu menu con cấp 2 có menu con cấp 3
            // Id con cấp 3
            $sub1 = $d->o_fet("select * from #_category where id_loai={$item1['id_code']} and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
            if (count($sub1) > 0) {
                // Nếu id con cấp 3 có id con cấp  4
                $menusub2 = "";
                // echo count($sub1);
                foreach ($sub1 as $key1 => $item2) {
                    // Menu cấp 3
                    $menu_cap_2_content = $d->o_fet("select * from #_category where id_loai={$item2['id_code']} and hien_thi= 1 " . _where_lang . " order by so_thu_tu asc, id desc");
                    // Menu cấp 3 có menu con
                    if (count($menu_cap_2_content) > 0) {
                        $menusub3 = "";
                        // Menu cấp 3 có menu con và tạo ra menu cấp 4
                        foreach ($menu_cap_2_content as $key2 => $item3) {
                            $menusub3 .= '<li class="menu-con-cap-3"><a href="' . URLLANG . $item3['alias'] . '.html" title="' . $item3['ten'] . '">' . $item3['ten'] . '</a></li>';
                        }
                        $menusub2 .= '
                        <li class="li-cap-2-co-cap-3">
                            <a href="' . URLLANG . $item2['alias'] . '.html" title="' . $item2['ten'] . '">' . $item2['ten'] . ' <span class="caret"></span></a>
                            <ul>' . $menusub3 . '</ul>
                        </li>';
                    } else {
                        // Menu cấp 3 không có menu con tạo ra menu cấp 4
                        $menusub2 .= '<li class="menu-con-cap-3"><a href="' . URLLANG . $item2['alias'] . '.html" title="' . $item2['ten'] . '">' . $item2['ten'] . '</a></li>';
                    }
                }
                $menusub .= '
                        <li class="li-cap-2-co-cap-3">
                            <a href="' . URLLANG . $item1['alias'] . '.html" title="' . $item1['ten'] . '">' . $item1['ten'] . ' <span class="caret"></span></a>
                            <ul>' . $menusub2 . '</ul>
                        </li>';
            } else {
                $menusub .= '<li><a href="' . URLLANG . $item1['alias'] . '.html" title="' . $item1['ten'] . '">' . $item1['ten'] . '</a></li>';
            }
        }
        // Tạo ra menu cấp 2
        $menu .= '<li>
                        <a href="' . URLLANG . $item['alias'] . '.html"  title="' . $item['ten'] . '">' . $item['ten'] . '</a>
                        <ul>
                            ' . $menusub . '
                        </ul>
                    </li>';
    } else {
        // In ra menu cấp 1 mà ko có cấp con
        $menu .= '<li><a href="' . URLLANG . $item['alias'] . '.html" title="' . $item['ten'] . '">' . $item['ten'] . '</a></li>';
    }
}
//echo $menu;
?>

<link href="<?= URLPATH ?>templates/module/HC-MobileNav/css/HC-Mobilenav.css" rel="stylesheet" />
<nav id="main-nav">
    <ul class="first-nav">
        <li>
            <form method="get" action="index.php" role="search">
                <input type="hidden" name="com" value="search">
                <input name="textsearch" type="text" class="form-control" placeholder="Nhập từ khóa tìm kiếm">
            </form>
        </li>
    </ul>
    <ul class="second-nav">
        <?php echo  $menu ?>
    </ul>
</nav>
<!-- hc-offcanvas-nav -->
<script src="<?= URLPATH ?>templates/module/HC-MobileNav/js/hc-offcanvas-nav.js"></script>
<script>
    (function($) {
        var $main_nav = $('#main-nav');
        var $toggle = $('.navbar-toggle');
        var defaultData = {
            maxWidth: false,
            customToggle: $toggle,
            navTitle: 'Danh mục',
            levelTitles: true,
            pushContent: '#container',
            insertClose: false,
            // levelOpen: 'expand',
        };
        // call our plugin
        var Nav = $main_nav.hcOffcanvasNav(defaultData);

    })(jQuery);
</script>
<!-- hc-offcanvas-nav -->