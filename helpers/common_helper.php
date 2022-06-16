<?php

function my_asset($path)
{
    return asset($path . '?v=' . env('ASSET_VERSION', md5(date('YmdHis'))));
}