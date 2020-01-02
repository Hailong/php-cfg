<?php
try {
    $a = 1;
    if (1>0) {
        $d = 4;
    }
    try {
        $e = 5;
    }
    catch (RangeException $e) {
        $f = 6;
    }
    finally {
        $g = 7;
    }
}
catch (RangeException $e) {
    $h = 8;
}
catch (Exception $e) {
    $b = 2;
}
finally {
    $c = 3;
}