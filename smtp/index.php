<?php
require('class.phpmailer.php');
function sendmail($tieude, $noidung, $nguoigui, $nguoinhan, $tennguoigui)
{
    $mail             = new PHPMailer();
    $subject =  mb_encode_mimeheader($tieude, 'UTF-8', 'B');
    $body = $noidung;
    $mail->IsSMTP();
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = "ssl";
    $mail->Host       = "smtp.gmail.com";
    $mail->Port       = 465;
    $mail->Username   = "customerphuongnamvina@gmail.com";
    $mail->Password   = "nafqjvrjhrvwceov";
    $mail->SetFrom($nguoigui, $tennguoigui);
    $mail->Subject    = $subject;
    $mail->MsgHTML($body);
    $address = $nguoinhan;
    $mail->AddAddress($address, $subject);
    // Tắt debug trực tiếp, ghi log nếu cần
    // $mail->SMTPDebug   = 0;
    // $mail->Debugoutput = 'error_log';
    if (!$mail->Send()) return false;
    else return true;
}
