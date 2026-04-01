<?php
if (INCLUDED !== true) exit;

switch ($sub) {
    case 'realmstatus':
        include('realmstatus.php');
        break;

    case 'commands':
        $_GET['tab'] = $_GET['tab'] ?? 'commands';
        include('botcommands.php');
        break;

    case 'botcommands':
        $_GET['tab'] = $_GET['tab'] ?? 'bot';
        include('botcommands.php');
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





