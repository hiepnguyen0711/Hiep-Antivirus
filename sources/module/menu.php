<?php
$menu = "";
$nav  = $d->o_fet("select * from #_category where menu = 1 and hien_thi= 1 " . _where_lang . " order by so_thu_tu asc, id desc");
$idcate = $d->simple_fetch("select * from #_category where menu=1 and hien_thi=1 and alias = '" . $com . "' order by so_thu_tu asc, id desc");

$delay = 0.1;
foreach ($nav as $item) {
    $sub = $d->o_fet("select * from #_category where id_loai={$item['id_code']} and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
    $active = '';
    if ($idcate['id'] == $item['id']) {
        $active = 'active';
    }
    if (count($sub) > 0) {
        $menusub = "";
        foreach ($sub as $key => $item1) {
            $sub1 = $d->o_fet("select * from #_category where id_loai={$item1['id_code']} and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
            if (count($sub1) > 0) {
                $menusub2 = "";
                foreach ($sub1 as $key1 => $item2) {
                    // Menu cấp 3 (4)
                    $sub2 = $d->o_fet("select * from #_category where id_loai={$item2['id_code']} and hien_thi=1 " . _where_lang . " order by so_thu_tu asc, id desc");
                    // Kiểm tra có cấp 3(4) không?
                    if (count($sub2) > 0) {
                        // Khởi tạo menu 3 (4)
                        $menusub3 = "";
                        foreach ($sub2 as $key2 => $item3) {
                            $menusub3 .= '<li class="sub-nav-2 menu_cap_4"><a href="' . URLLANG . $item3['alias'] . '.html">' . $item3['ten'] . '</a></li>';
                        }
                        $menusub2 .= '<li class="sub-nav-2 menu_li_level_3">
                        <a class="menu_a_level_3" href="' . URLLANG . $item2['alias'] . '.html">' . $item2['ten'] . '</a>
                        <ul class="ul_nav_cap_4" >' . $menusub3 . '</ul>
                        </li>';
                    } else {
                        $menusub2 .= '<li class="sub-nav-2 menu_li_level_3"><a class="menu_a_level_3" href="' . URLLANG . $item2['alias'] . '.html"><span class="hvr-shrink">' . $item2['ten'] . '</span></a></li>';
                    }
                }
                $menusub .= '
                        <li  class="dropdown_cap_2 menu_li_level_2">
                            <a class="menu_a_level_2" href="' . URLLANG . $item1['alias'] . '.html" title="' . $item1['ten'] . '"><span class="hvr-shrink">' . $item1['ten'] . '</span></a>
                            <ul class="menu_ul_level_3" >' . $menusub2 . '</ul>
                        </li>';
            } else {
                $menusub .= '<li class="menu_li_level_2 sub-no-nav"><a class="menu_a_level_2 " href="' . URLLANG . $item1['alias'] . '.html" title="' . $item1['ten'] . '"><span class="hvr-shrink">' . $item1['ten'] . '</span></a></li>';
            }
        }
        $menu .= '<li class="dropdown menu_li_level_1 ' . $active . '">
                        <a class="p-3 menu_a_level_1 hvr-icon-hang' . $active . '" href="' . URLLANG . $item['alias'] . ".html" . '"  title="' . $item['ten'] . '">' . $item['ten'] . ' <i class="ms-2 hvr-icon fa-solid fa-angle-down"></i></a>
                        <ul class="sub-menu fadeInUp">
                            ' . $menusub . '
                        </ul>
                    </li>';
    } else {

        $menu .= '<li class="menu_li_level_1 ' . $active . '" ><a href="' . URLLANG . $item['alias'] . '.html" class="p-3 menu_a_level_1 ">' . $item['ten'] . '</a></li>';
    }
    $delay = $delay + 0.1;
}
echo $menu;
