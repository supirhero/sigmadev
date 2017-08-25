<?php


$listBU=explode(",","dyas,yaskur");
foreach ($listBU as &$value) {
    $list[] = "'".$value."'";
}
print_r(implode(",",$list));