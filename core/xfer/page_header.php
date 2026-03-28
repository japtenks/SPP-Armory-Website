<?php

$expansionName =
    $expansion==2 ? 'WotLK' :
    ($expansion==1 ? 'TBC' : 'Classic');

echo "<div style='padding:8px;background:#111;color:#eee;font-family:Arial'>
<strong>Realm:</strong> {$realmName} &nbsp;&nbsp;
<strong>Expansion:</strong> {$expansionName}
</div>";
