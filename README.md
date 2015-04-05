Bypass-PHP-GD-Process-To-RCE
===


## Description

Use Similar-Block-Attack to bypass PHP-GD process to RCE.


## Usage

Usage: ``php codeinj.php <src_img> <inj_code>``

    php codeinj.php demo.gif "<?php phpinfo();?>"

then new image "gd_demo.gif" saved in current path.

You can use a quick demo with ``demo/index.php``, copy that to your test folder and upload ``gd_demo.gif`` or your image to test.


## Others

If this script doesn't work well, take it easy please! :P


## Reference

[http://www.secgeek.net/bookfresh-vulnerability/](http://www.secgeek.net/bookfresh-vulnerability/)
