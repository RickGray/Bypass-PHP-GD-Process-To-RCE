<h2>This is a quick demo to show the process of bypass PHP-GD.</h2>
<h3>Choose image to upload, then "include $upload_path;" to show some data.</h3>
<h3>or, use parameter "file" to include file, e.g. http://xxxxx/index.php?file=&lt;something&gt;</h3>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="file" name="upfile" value="">
    <input type="submit" value="upload">
</form>


<?php
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


if (isset($_FILES["upfile"])) {
    $temp_file = $_FILES['upfile']['tmp_name'];

    $img_info = getimagesize($temp_file);
    if ($img_info[2] == '1') {
        $upload_file = "test.gif";
        if (!gd_process($temp_file, $upload_file)) {
            printf("Image upload process error, please check out.\n");
            exit;
        }
        printf("Path: %s, image upload successful!\n", $upload_file);
        include $upload_file;
    } else {
        printf("Image type not support in this demo, GIF please...\n");
        exit;
    }
}

if (isset($_REQUEST["file"])) {
    include $_REQUEST["file"];
}
?>