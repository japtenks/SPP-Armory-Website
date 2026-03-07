<?php

function qualify_tables($sql,$schema,array $tables){
    $pat='/((FROM|JOIN)\s+)(`?('.implode('|',array_map('preg_quote',$tables)).')`?)/i';
    return preg_replace($pat,"\\1`$schema`.`\\4`",$sql);
}

function armory_query($sql,$mode=0){
    global $ARDB,$ARMORY_SCHEMA;

    $sql = qualify_tables($sql,$ARMORY_SCHEMA,[
        'dbc_spell','dbc_spellicon','dbc_spellduration','dbc_spellradius',
        'dbc_itemset','dbc_talent','dbc_talenttab','dbc_itemdisplayinfo',
        'dbc_itemsubclass','dbc_itemrandomproperties','dbc_itemrandomsuffix',
        'dbc_randproppoints'
    ]);

    return q($ARDB,$sql,$mode);
}

function world_query($sql,$mode=0){
    global $WSDB,$WORLD_SCHEMA;

    $sql = qualify_tables($sql,$WORLD_SCHEMA,['item_template']);

    return q($WSDB,$sql,$mode);
}
