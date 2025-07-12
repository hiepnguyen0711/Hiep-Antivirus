$(document).ready(function () {
  $('ul#menu>li>a[href="#"]').click(function () {
    $(this).next("ul").toggle();
    return false;
  });
  $("#search-input").change(function () {
    var p = $(this).attr("data-p");
    var col = $(this).val();
    var key = $("#key-search").val();
    window.location =
      "index.php?p=" + p + "&a=man&search=" + col + "&key=" + key;
  });
  $("#search-cate").change(function () {
    var p = $(this).attr("data-p");
    var col = $(this).val();
    window.location = "index.php?p=" + p + "&a=man&search=loai&key=" + col;
  });
  $("#check_all").change(function () {
    $(".table input:checkbox").prop("checked", this.checked);
  });
  $(".a_stt").on("blur", function () {
    var table = $(this).attr("data-table");
    var col = $(this).attr("data-col");
    var id = $(this).attr("data-id");
    var val = $(this).val();
    $.ajax({
      url: "./sources/ajax.php",
      type: "POST",
      data: { table: table, col: col, val: val, id: id, do: "update_stt" },
      dataType: "json",
      success: function (data) {
        $.notify(data.text, data.status);
      },
    });
  });
  $(".a_stt2").on("change", function () {
    var table = $(this).attr("data-table");
    var col = $(this).attr("data-col");
    var id = $(this).attr("data-id");
    var val = $(this).val();
    if (val > 0) {
      $.ajax({
        url: "./sources/ajax.php",
        type: "POST",
        data: { table: table, col: col, val: val, id: id, do: "update_data" },
        dataType: "json",
        success: function (data) {
          $.notify(data.text, data.status);
        },
      });
    }
  });
  $(".ajax-input").change("click", function () {
    var col = $(this).attr("data-col");
    var table = $(this).attr("data-table");
    var id = $(this).attr("data-id");
    var val = $(this).val();
    //alert(val);
    $.ajax({
      url: "sources/ajax.php",
      type: "post",
      data: { table: table, col: col, val: val, id: id, do: "update_stt" },
      dataType: "json",
      success: function (data) {
        $.notify(data.text, data.status);
      },
    });
  });
  $(".insert-ck").change("click", function () {
    var data_tv = $(this).attr("data-tv");
    var data_id = $(this).attr("data-id");
    var data_dv = $(this).attr("data-dv");
    var val = $(this).val();
    //alert(val);
    $.ajax({
      url: "sources/ajax.php",
      type: "post",
      dataType: "text",
      data: {
        data_tv: data_tv,
        data_id: data_id,
        val: val,
        data_dv: data_dv,
        do: "update_chietkhau",
      },
      success: function (result) {
        if (result === "1") {
          $.notify("Cập nhật thành công", "success");
        } else {
          $.notify("Đã sảy ra lỗi! ", "error");
        }
      },
    });
  });
});
function copyToClipboard(element) { 
  var $temp = $("<input>");
  $("body").append($temp);
  $temp.val(element.val()).select();
  document.execCommand("copy");
  $temp.remove();
  $.notify("Đã sao chép", "success");
}
function addText(text, target, title) {
  var str = $(text).val();
  var link = locdau(str);
  $(target).val(link);
  $(title).val(str);
}
function seach(obj, tenp) {
  var loai = $(obj).val();
  var key = $("#search").val();
  if (loai == 1) {
    window.location = "index.php?p=" + tenp + "&a=man&seach=id&key=" + key;
  } else if (loai == 2) {
    window.location = "index.php?p=" + tenp + "&a=man&seach=name&key=" + key;
  } else if (loai == 3) {
    window.location = "index.php?p=" + tenp + "&a=man&seach=ma&key=" + key;
  }
}

function show(obj, tenp) {
  var show = $(obj).val();
  window.location = "index.php?p=" + tenp + "&a=man&hienthi=" + show;
}

function form_submit(obj) {
  if ($(obj).val() == "delete") {
    if (confirm("Bạn có chắc muốn xóa?")) {
      $("#form").submit();
    }
  }
}

function on_check(obj, bang, cot, id) {
  var trangthai = 0;
  if ($(obj).is(":checked")) {
    trangthai = 1;
  }
  $.ajax({
    url: "./sources/aj_checkbox.php",
    type: "POST",
    dataType: "json",
    data:
      "do=checkbox&trangthai=" +
      trangthai +
      "&bang=" +
      bang +
      "&cot=" +
      cot +
      "&id=" +
      id,
    success: function (data) {
      $.notify(data.text, data.status);
    },
  });
}
function xoa_img(bang, cot, id_code, folder) {
  if (confirm("Xác nhận xóa") == true) {
    $.ajax({
      url: "./sources/aj_checkbox.php",
      type: "POST",
      data:
        "do=xoaimg&bang=" +
        bang +
        "&cot=" +
        cot +
        "&id=" +
        id_code +
        "&folder=" +
        folder,
      success: function (data) {
        if (data === "1") {
          $.notify("Xóa thành công", "success");
          // xóa video start
          if (cot == "video") {
            var video = $(".video_img");
            var children = $(video).find(".video_img_src");
            $(children).val("");
            $("#review_video_box").attr('src', '');
          }
          // xóa video end
          $("." + cot + folder)
            .find(".box-img2 img")
            .attr("src", "img/no-image.png");
          $("." + cot + folder)
            .find("#hinh_anh")
            .val("");
          $("." + cot + folder)
            .find("button")
            .hide();
        } else {
          $.notify("Đã xảy ra lỗi! ", "error");
        }
      },
    });
  }
}
// function locdau(str) {
//   str = str.toLowerCase();
//   str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
//   str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
//   str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
//   str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
//   str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
//   str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
//   str = str.replace(/đ/g, "d");
//   str = str.replace(
//     /!|@|\$|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\'| |\"|\&|\#|\[|\]|\;|\||\{|\}|~/g,
//     "-"
//   );
//   str = str.replace(/^\-+|\-+$/g, "-");
//   str = str.replace(/\–/g, "-");
//   str = str.replace(/\\/g, "-");
//   str = str.replace(/-+-/g, "-");
//   return str;
// }
function locdau(str) {
  str = str.toLowerCase();
  str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
  str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
  str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
  str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
  str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
  str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
  str = str.replace(/đ/g, "d");
  str = str.replace(/[!@#$%^*()+=<>?/,.:' "&#[\];|{}~\\]/g, "-");
  str = str.replace(/^-+|-+$/g, "-");
  str = str.replace(/–/g, "-");
  str = str.replace(/\\/g, "-");
  str = str.replace(/-+/g, "-");
  str = str.replace(/-+/g, "-");
  str = str.replace(/^\-+|\-+$/g, "");

  str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, "a");
  str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, "e");
  str = str.replace(/ì|í|ị|ỉ|ĩ/g, "i");
  str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, "o");
  str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, "u");
  str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, "y");
  str = str.replace(/đ/g, "d");
  str = str.replace(/À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ/g, "A");
  str = str.replace(/È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ/g, "E");
  str = str.replace(/Ì|Í|Ị|Ỉ|Ĩ/g, "I");
  str = str.replace(/Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ/g, "O");
  str = str.replace(/Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ/g, "U");
  str = str.replace(/Ỳ|Ý|Ỵ|Ỷ|Ỹ/g, "Y");
  str = str.replace(/Đ/g, "D");
  // Some system encode vietnamese combining accent as individual utf-8 characters
  // Một vài bộ encode coi các dấu mũ, dấu chữ như một kí tự riêng biệt nên thêm hai dòng này
  str = str.replace(/\u0300|\u0301|\u0303|\u0309|\u0323/g, ""); // ̀ ́ ̃ ̉ ̣  huyền, sắc, ngã, hỏi, nặng
  str = str.replace(/\u02C6|\u0306|\u031B/g, ""); // ˆ ̆ ̛  Â, Ê, Ă, Ơ, Ư
  // Remove extra spaces
  // Bỏ các khoảng trắng liền nhau
  str = str.replace(/ + /g, "-");
  str = str.trim();
  // Remove punctuations
  // Bỏ dấu câu, kí tự đặc biệt
  str = str.replace(
    /!|@|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\;|\'|\"|\&|\#|\[|\]|~|\$|_|`|-|{|}|\||\\/g,
    "-"
  );
  return str;
}
function view_notif_all() {
  $.ajax({
    url: "sources/ajax.php",
    type: "post",
    dataType: "text",
    data: {
      do: "view_notif_all",
    },
    success: function () {
      $("#notifications").html("0");
      $(".view_notif").removeClass("chuaxem");
      $.notify("Update successful", "success");
    },
  });
}
$(".view_notif").click(function () {
  var url = $(this).attr("data-url");
  var id = $(this).attr("data-id");
  $.ajax({
    url: "sources/ajax.php",
    type: "post",
    dataType: "text",
    data: {
      do: "view_notif",
      id: id,
    },
    success: function () {
      window.location = url;
    },
  });
});
$(".delete-notif").click(function () {
  var id = $(this).attr("data-id");
  $.ajax({
    url: "sources/ajax.php",
    type: "post",
    dataType: "text",
    data: {
      do: "delete_notif",
      id: id,
    },
    success: function () {
      $("#notif_" + id).remove();
    },
  });
});
