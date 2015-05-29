<?php 
//PERSON
$person_name = "Rebecca Yarbrough";
$person_title = "Founder and President";

// PHONE
$phone_number = "202.930.5093";
$phone_number_tel = "+12029305093";

// COMPANY DETAILS
$company_name = "The Offline Society";
$url = "offlinesociety.com";

// LOGO
$logo_url = "http://s9.postimg.org/siysec0kv/logo_2x.png";
$logo_width = "176";
$logo_height = "107";

// STYLE
$signature_width = 340;
$text_col_width = $signature_width / 2;
$text_color = "#000000";
$highlight_color = "#fb0000";
$font_stack = "'Brandon Grotesque', 'Helvetica Neue',Helvetica, sans-serif";
$font_style = "normal";
$font_size = "14px";

$font_style_string = "font-family:" . $font_stack . ";font-style:" . $font_style . ";font-size:" . $font_size . ";color:" . $text_color . ";";
?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="format-detection" content="telephone=no">

<table width='<? echo $signature_width; ?>' id="sig" cellspacing='0' cellpadding='0' border-spacing='0' style="<? echo $font_style_string; ?>width:<? echo $signature_width; ?>px;margin:0;padding:0;">
  <tr>
    <td style="width:<? echo $text_col_width; ?>px">
      <table style="color:#000000">
      <tr style="cellpadding">
      <td style="<? echo $font_style_string; ?>padding-bottom: 7px;">
        <b style="font-family:<? echo $font_stack; ?>;font-size:<? echo $font_size; ?>;color:<? echo $text_color; ?>;">Rebecca Yarbrough</b><br>
        Founder and President<br>
        <a style="<? echo $font_style_string; ?>padding-bottom: 7px;text-decoration:none" href='tel:<? echo $phone_number_tel; ?>'><? echo $phone_number; ?></a><br>
        <a href="http://<? echo $url; ?>" style="font-family:<? echo $font_stack; ?>;font-size:<? echo $font_size; ?>;font-style:<? echo $font_style; ?>;color:<? echo $highlight_color; ?>;text-decoration:none"><? echo $url; ?></a>
      </td>
      </tr>
      </table>
    </td>
    <td style="padding:0;padding:0;margin:0"><a href="http://<? echo $url; ?>"><img src="<? echo $logo_url; ?>" width="<? echo $logo_width; ?>" height="<? echo $logo_height; ?>" style="padding:0;margin:0;" /></a></td>
    
  </tr>
</table>