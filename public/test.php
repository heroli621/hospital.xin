<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/2
 * Time: 11:37
 */
$nt = time();
$d3 = strtotime("+1 days");
$i = 24*3600;
echo $nt;
echo "<hr />";
echo $d3;
echo "<hr />";
echo $i;
echo "<hr />";
echo date("Y-m-d H:i:s",$d3);