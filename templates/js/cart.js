// $("body").on("click", ".addcart", function () {
//   var id = $(this).data("id");
//   var quantity = $(".soluong").val() ? $(".soluong").val() : 1;
//   if (id) {
//     $.ajax({
//       url: "sources/ajax/ajax_cart.php",
//       type: "POST",
//       dataType: "html",
//       async: false,
//       data: { do: "addcart", id: id, quantity: quantity },
//       success: function (result) {
//         window.location = result;
//       },
//     });
//   }
// });

// Update so luong
$(document).ready(function () {
  // Hàm cập nhật tổng tiền
  function updateTotalPrice() {
    // Lấy tổng tiền sản phẩm ban đầu
    let totalProductPrice =
      parseFloat($(".total-price-text").data("total")) || 0;

    // Lấy phí vận chuyển hiện tại
    let shippingCost = parseFloat($(".cost-shipping").data("shipping")) || 0;

    // Cập nhật tổng tiền
    let totalPrice = totalProductPrice + shippingCost;
    $(".total-price-text").text(
      new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
      }).format(totalPrice)
    );
  }

  // Hàm kiểm tra input vận chuyển nào được check và cập nhật phí vận chuyển
  function initializeShippingCost() {
    let selectedShipping = $(
      'input[name="vanhiep_phuong_thuc_van_chuyen"]:checked'
    );
    let shippingCost = parseFloat(selectedShipping.data('value')) || 0;

    // Cập nhật phí vận chuyển hiển thị
    $(".cost-shipping").text(
      new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
      }).format(shippingCost)
    );
    $(".cost-shipping").data("shipping", shippingCost);

    // Cập nhật lại tổng tiền
    updateTotalPrice();
  }

  // Xử lý sự kiện khi chọn phương thức vận chuyển
  $('input[name="vanhiep_phuong_thuc_van_chuyen"]').on("change", function () {
    let shippingCost = parseFloat($(this).data('value')) || 0;

    // Cập nhật phí vận chuyển hiển thị
    $(".cost-shipping").text(
      new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
      }).format(shippingCost)
    );
    $(".cost-shipping").data("shipping", shippingCost);

    // Cập nhật lại tổng tiền
    updateTotalPrice();
  });

  // Khởi tạo phí vận chuyển và tổng tiền khi load trang
  initializeShippingCost();

  // Hàm cập nhật số lượng sản phẩm và tổng tiền
  function updateCartQuantityAndTotal($button, qty) {
    const parent = $button.parents(".ha-cart-product-item");
    const id = parent.data("pid");
    const code = parent.data("code");

    // Gọi AJAX để cập nhật giỏ hàng
    $.ajax({
      type: "POST",
      url: "sources/ajax/ajax_cart.php",
      dataType: "json",
      data: { do: "update-cart", id: id, code: code, quantity: qty },
      success: function (result) {
        if (result) {
          $(".load-price-" + code + " .text-price").html(result.gia);
          $(".tongtien").html(result.tongtien);
          $(".soluong_cart").html(result.soluongsp);
          $(".right-head_cart span").html(result.soluongsp);
          $(".vh-gio-hang-item-price-key-" + code).html(result.gia);
          $(".total-price-text").html(result.tongtien);
          $("#vh-tam-tinh-price").html(result.tongtien + "<sup> đ</sup>");

          // Lấy tổng tiền không bao gồm phí vận chuyển để tính toán lại
          $(".total-price-text").data("total", result.totalProductPrice || 0);

          // Cập nhật lại tổng tiền với phí vận chuyển
          updateTotalPrice();
        }
      },
    });
  }
  // Xử lý sự kiện tăng giảm số lượng sản phẩm
  $("body").on("click", ".qty-button", function () {
    var $button = $(this);
    var parent = $button.parents(".ha-cart-product-item");
    var quantity_child = parent.find(".ha-cart-product-item-quantity");
    var oldValue = parent.find(".num_sl").val();
    var qty;

    if ($button.hasClass("qty-down")) {
      if (parseFloat(oldValue) - 1 == 0) {
        return false;
      }
      qty = parseFloat(oldValue) - 1;
    } else {
      qty = parseFloat(oldValue) + 1;
    }

    // Cập nhật số lượng hiển thị
    quantity_child.text(qty);
    $button.parent().find(".num_sl").val(qty);

    // Gọi hàm cập nhật giỏ hàng và tính lại tổng tiền
    updateCartQuantityAndTotal($button, qty);
  });
});
// Update cart
function update_cart(id = 0, code = "", quantity = 1) {
  if (id) {
    $.ajax({
      type: "POST",
      url: "sources/ajax/ajax_cart.php",
      dataType: "json",
      data: { do: "update-cart", id: id, code: code, quantity: quantity },
      success: function (result) {
        if (result) {
          $(".load-price-" + code + " .text-price").html(result.gia);
          $(".tongtien").html(result.tongtien);
          $(".soluong_cart").html(result.soluongsp);
          $(".right-head_cart span").html(result.soluongsp);

          // $(".ha-cart-product-item-quantity-key-" + code).html(
          //   result.soluongsp
          // );
          $(".num-cart-quantity").html(result.soluongsp);

          $(".vh-gio-hang-item-price-key-" + code).html(result.gia);

          $(".total-price-text").html(result.tongtien);
          $("#vh-tam-tinh-price").html(result.tongtien + "<sup> vnđ</sup>");
          $(".bag-cart").html(result.soluongsp);
        }
      },
    });
  }
}

$("body").on("click", ".addcartnow", function () {
  // console.log("123");
  var id = $(this).data("id");
  var color = 0;
  var size = 0;
  var quantity = $(".soluong").val() ? $(".soluong").val() : 1;
  $(".p-color").each(function () {
    if (this.checked) {
      console.log($(this).val());
      color = $(this).val();
    }
  });

  $(".p-size").each(function () {
    if (this.checked) {
      console.log($(this).val());
      size = $(this).val();
    }
  });

  $.ajax({
    type: "post",
    url: "sources/ajax/ajax_cart.php",
    data: {
      id: id,
      quantity: quantity,
      color: color,
      size: size,
      do: "addcartnow",
    },
    dataType: "json",
    success: function (res) {
      Swal.fire({
        icon: "success",
        title: "Add to cart successfully",
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 1200,
        timerProgressBar: true,
      });
      $(".bag-cart").html(res);
      $(".bag-cart-number").html(res);
    },
  });
});

function get_huyen(code_tinh, code_huyen) {
  var id_quocgia = $("#" + code_tinh).val();
  $.ajax({
    url: "sources/ajax/ajax.php",
    type: "post",
    dataType: "text",
    data: {
      do: "get_huyen",
      code_tinh: id_quocgia,
    },
    success: function (result) {
      $("#" + code_huyen).html(result);
      $("select").niceSelect("update");
    },
  });
}

function get_xa(code_huyen, code_xa) {
  var id_huyen = $("#" + code_huyen).val();
  $.ajax({
    url: "sources/ajax/ajax.php",
    type: "post",
    dataType: "text",
    data: {
      do: "get_xa",
      code_huyen: id_huyen,
    },
    success: function (result) {
      $("#" + code_xa).html(result);
      $("select").niceSelect("update");
    },
  });
}
