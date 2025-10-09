\# SPP-Armory-Website

Website with working Armory for the [SPP Classics Repack](https://github.com/celguar/spp-classics-cmangos).

\# Features

- Auto-builds **Talent Trees** for different classes from the armory database  
  *(uses `dbc_talent` and `dbc_talenttab`)*  

- Tooltips update dynamically from the DBC  
  *(pulls values like `spells`, `duration`, `icon`, `radius`)* 

-Talent Calculator for all Classes
	-Allows for importing a hash from wowhead. armory/index.php#2-02-02
	will take you to the Paladin Talent calculator, with 2 points in each tree
-New links to navigate the website

-Armor set page - Complete

-Start to total revamp 




---



\## Installation



1\. Copy over the currently installed website located at `.\Server\website`

2\. Run `Armory_Tooltip_Updates.sql` and `Armory_bot_command_SQL.sql` on your `classicarmory` or `tbcmarmory` database . (Files are located in `website\DB updates`.

3\. Refresh the website.





---



\### Talent Trees

**Shaman**
![Shaman Talent Trees](img/talents01.jpg)

**Hunter**
![Hunter Talent Trees](img/talents02.jpg)

**Paladin**
![Paladin Talent Trees](img/talents03.jpg)

**Updated Banner - Talents**
![Paladin Talent Trees](img/website02.png)

**Talent Calculator - Paladin**
![Paladin Talent Trees](img/website01.png)
Can import the same hash from WowHead's talent calculator.
The share build, will pull the hash code for the current build.
There is a /w botname spot, with the command to have a bot use this talent build. 

**Start page with new Links under Workshop and Gameguide**
![Paladin Talent Trees](img/website04.png)
Talents - Takes you to your selected account character. (If logged in)
Talent Calculator - Take you to new character talent calculator.

**New Link under the Armory dropdown**
![Paladin Talent Trees](img/website03.png)

**New registration page - no more fuss with questions**
![New registration page](img/website05.png)

**New Bot Command Interactive Search **
![Bot Commands](img/website06.png)

**New Login**
![New Login](img/website07.png)

**New Front Page**
![Front Page](img/website08.png)

**New Character List with bot filter**
![Character list](img/website09.png)

**New**
![Server Stats](img/website10.png)
---



\## Notes

\- The `img/` folder is \*\*not included\*\* in release zips (set via `.gitattributes`).





