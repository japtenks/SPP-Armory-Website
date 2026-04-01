<?php
if (INCLUDED !== true) exit;

if (empty($sub) && !empty($_GET['amp;sub'])) {
    $sub = (string)$_GET['amp;sub'];
}

switch ($sub) {
    case 'realmstatus':
        include('realmstatus.php');
        break;

    case 'commands':
        include('commands.php');
        break;

    case 'botcommands':
        include('botcommands.php');
        break;

    case 'wbuffbuilder':
        include('server.wbuffbuilder.php');
        break;

    case 'playermap':
        include('playermap.php');
        break;

    case 'statistic':
        include('statistic.php');
        break;

    case 'ah':
        include('ah.php');
        break;

    case 'chars':
        include('chars.php');
        break;

    case 'honor':
        include('server.honor.php');
        break;

    case 'talents':
        include('server.talents.php');
        break;

    case 'items':
        include('server.items.php');
        break;

    case 'sets':
        include('server.sets.php');
        break;

    case 'downloads':
        include('server.downloads.php');
        break;

    case 'guilds':
        include('server.guilds.php');
        break;
    case 'guild':
        include('server.guild.php');
        break;

    default:
        echo "<div style='color:red;text-align:center;padding:10px;'>Unknown or missing server page.</div>";
        break;
}
?>





