// awb512-119-u
	var linkClass="subMenuLink";//defines initial css class of the links
	var menuNumber=9;
	var agt=navigator.userAgent.toLowerCase(), appVer = navigator.appVersion.toLowerCase(), iePos  = appVer.indexOf('msie'), is_opera = (agt.indexOf("opera") != -1), is_ie   = ((iePos!=-1) && (!is_opera));
	if(is_ie){
		var menuBg="";
		var menuBgIndent="";
		var underLine="<img src=new-hp/images/menu/mainmenu/bullet-trans-line-blue.gif />";
		var bulletImg="<img src=new-hp/images/menu/mainmenu/bullet-trans-dot-blue.gif align=left />";
		var bulletImgIndent="<img src=new-hp/images/menu/mainmenu/bullet-trans-dot-indent.gif align=left />";
	}else{
		var menuBg="new-hp/images/menu/mainmenu/bullet-trans-bg-blue.gif";
		var menuBgIndent="new-hp/images/menu/mainmenu/bullet-trans-indent-bg.gif";
		var bulletImgIndent="<img src = new-hp/images/pixel.gif width=16 height=1 />";
		var underLine="";
		var bulletImg="";
	}
	var NoOffFirstLineMenus=1;			// Number of main menu  items
						// Colorvariables:
						// Color variables take HTML predefined color names or "#rrggbb" strings
						//For transparency make colors and border color ""
	var LowBgColor="#051B38";			// Background color when mouse is not over
	var HighBgColor="#013A88";			// Background color when mouse is over
	var FontLowColor="white";			// Font color when mouse is not over
	var FontHighColor="white";			// Font color when mouse is over
	var BorderColor="#116EED";			// Border color
	var BorderWidthMain=0;			// Border width main items
	var BorderWidthSub=1;			// Border width sub items
 	var BorderBtwnMain=0;			// Border width between elements main items
	var BorderBtwnSub=0;			// Border width between elements sub items
	var FontFamily="arial,comic sans ms,technical";	// Font family menu items
	var FontSize=11;				// Font size menu items
	var FontBold=0;				// Bold menu items 1 or 0
	var FontItalic=0;				// Italic menu items 1 or 0
	var MenuTextCentered="left";		// Item text position left, center or right
	var MenuCentered="left";			// Menu horizontal position can be: left, center, right
	var MenuVerticalCentered="top";		// Menu vertical position top, middle,bottom or static
	var ChildOverlap=.2;			// horizontal overlap child/ parent
	var ChildVerticalOverlap=.2;			// vertical overlap child/ parent
	var StartTop=-9;				// Menu offset x coordinate
	var StartLeft=0;				// Menu offset y coordinate
	var VerCorrect=0;				// Multiple frames y correction
	var HorCorrect=0;				// Multiple frames x correction
	var DistFrmFrameBrdr=0;			// Distance between main menu and frame border
	if(is_ie)
		var LeftPaddng=9;				// Left padding
	else
		var LeftPaddng=9;				// Left padding
	var TopPaddng=-1;				// Top padding. If set to -1 text is vertically centered
	var FirstLineHorizontal=1;			// Number defines to which level the menu must unfold horizontal; 0 is all vertical
	var MenuFramesVertical=1;			// Frames in cols or rows 1 or 0
	var DissapearDelay=500;			// delay before menu folds in
	var UnfoldDelay=0;			// delay before sub unfolds
	var UnfoldDelay2=200;			// delay before sub builds
	var TakeOverBgColor=1;			// Menu frame takes over background color subitem frame
	var FirstLineFrame="space";			// Frame where first level appears
	var SecLineFrame="space";			// Frame where sub levels appear
	var DocTargetFrame="space";		// Frame where target documents appear
	var TargetLoc="filterMenu";				// span id for relative positioning
	var MenuWrap=1;				// enables/ disables menu wrap 1 or 0
	var RightToLeft=0;				// enables/ disables right to left unfold 1 or 0
	var BottomUp=0;				// enables/ disables Bottom up unfold 1 or 0
	var UnfoldsOnClick=0;			// Level 1 unfolds onclick/ onmouseover
	var BaseHref="";				// BaseHref lets you specify the root directory for relative links. 
						// The script precedes your relative links with BaseHref
						// For instance: 
						// when your BaseHref= "http://www.MyDomain/" and a link in the menu is "subdir/MyFile.htm",
						// the script renders to: "http://www.MyDomain/subdir/MyFile.htm"
						// Can also be used when you use images in the textfields of the menu
						// "MenuX=new Array("<img src=\""+BaseHref+"MyImage\">"
						// For testing on your harddisk use syntax like: BaseHref="file:///C|/MyFiles/Homepage/"


	var Arrws=['shared/wow-com/images/subnav/tri.gif',14,15,'shared/wow-com/images/subnav/arrow_right2.gif',18,12,'shared/wow-com/images/subnav/arrow_right2.gif',5,10];	// Arrow source, width and height


						// Arrow source, width and height.
						// If arrow images are not needed keep source ""

	var MenuUsesFrames=0;			// MenuUsesFrames is only 0 when Main menu, submenus,
						// document targets and script are in the same frame.
						// In all other cases it must be 1

	var RememberStatus=0;			// RememberStatus: When set to 1, menu unfolds to the presetted menu item. 
						// When set to 2 only the relevant main item stays highligthed
						// The preset is done by setting a variable in the head section of the target document.
						// <head>
						//	<script type="text/javascript">var SetMenu="2_2_1";</script>
						// </head>
						// 2_2_1 represents the menu item Menu2_2_1=new Array(.......

	var OverFormElements=0;			// Set this to 0 when the menu does not need to cover form elements.
	var BuildOnDemand=1;			// 1/0 When set to 1 the sub menus are build when the parent is moused over
	var BgImgLeftOffset=5;			// Only relevant when bg image is used as rollover
	var ScaleMenu=0;				// 1/0 When set to 0 Menu scales with browser text size setting

	var HooverBold=0;				// 1 or 0
	var HooverItalic=0;				// 1 or 0
	var HooverUnderLine=0;			// 1 or 0
	var HooverTextSize=0;			// 0=off, number is font size difference on hoover
	var HooverVariant=0;			// 1 or 0

						// Below some pretty useless effects, since only IE6+ supports them
						// I provided 3 effects: MenuSlide, MenuShadow and MenuOpacity
						// If you don't need MenuSlide just leave in the line var MenuSlide="";
						// delete the other MenuSlide statements
						// In general leave the MenuSlide you need in and delete the others.
						// Above is also valid for MenuShadow and MenuOpacity
						// You can also use other effects by specifying another filter for MenuShadow and MenuOpacity.
						// You can add more filters by concanating the strings
	var MenuSlide="";

	var MenuShadow="";
	var MenuShadow="progid:DXImageTransform.Microsoft.DropShadow(color=#000000, offX=2, offY=2, positive=1)";
	var MenuShadow="progid:DXImageTransform.Microsoft.Shadow(color=#000000, direction=135, strength=3)";

	var MenuOpacity="";
	var MenuOpacity="progid:DXImageTransform.Microsoft.Alpha(opacity=90)";

	function BeforeStart(){return}
	function AfterBuild(){return}
	function BeforeFirstOpen(){return}
	function AfterCloseAll(){return}

Menu1=new Array("Site Map","/","shared/wow-com/images/subnav/button_bg.gif",8,15,110,"","","","","","",-1,-1,-1,"","");

	Menu1_1=new Array(bulletImg+"News"+underLine,"#","",3,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_1_1=new Array(bulletImg+"Front Page"+underLine,"?","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_1_2=new Array(bulletImg+"Forum Archive"+underLine,"index.php?n=forum&sub=viewforum&fid=1","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_1_3=new Array(bulletImg+"RSS Feeds"+underLine,"inc/news.rss.xml","",0,15,110,"","","","","","",-1,-1,-1,"","");
	
	Menu1_2=new Array(bulletImg+"Account"+underLine,"index.php?n=account&sub=login","",4,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_2_1=new Array(bulletImg+"Login"+underLine,"index.php?n=account&sub=login","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_2_2=new Array(bulletImg+"Register"+underLine,"index.php?n=account&sub=register","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_2_3=new Array(bulletImg+"Manage Account"+underLine,"index.php?n=account&sub=manage","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_2_4=new Array(bulletImg+"Realm Status"+underLine,"index.php?n=server&sub=realmstatus","",0,15,110,"","","","","","",-1,-1,-1,"","");

	Menu1_3=new Array(bulletImg+"Game Guide"+underLine,"index.php?n=gameguide&sub=connect","",2,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_3_1=new Array(bulletImg+"How To Play"+underLine,"index.php?n=gameguide&sub=connect","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_3_2=new Array(bulletImg+"Bot Guide"+underLine,"index.php?n=server&sub=botcommands","",0,15,110,"","","","","","",-1,-1,-1,"","");
				
	Menu1_4=new Array(bulletImg+"Workshop"+underLine,"index.php?n=server&sub=realmstatus","",6,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_1=new Array(bulletImg+"Realm Status"+underLine,"index.php?n=server&sub=realmstatus","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_2=new Array(bulletImg+"Player Map"+underLine,"index.php?n=server&sub=playermap","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_3=new Array(bulletImg+"Statistics"+underLine,"index.php?n=server&sub=statistic","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_4=new Array(bulletImg+"Auction House"+underLine,"index.php?n=server&sub=ah","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_5=new Array(bulletImg+"Armor Sets"+underLine,"index.php?n=server&sub=sets","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_4_6=new Array(bulletImg+"Downloads"+underLine,"index.php?n=server&sub=downloads","",0,15,110,"","","","","","",-1,-1,-1,"","");
		
	Menu1_5=new Array(bulletImg+"Downloads"+underLine,"index.php?n=server&sub=downloads","",0,15,110,"","","","","","",-1,-1,-1,"",""); 
			
	Menu1_6=new Array(bulletImg+"Forums"+underLine,"index.php?n=forum","",2,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_6_1=new Array(bulletImg+"Forum Home"+underLine,"index.php?n=forum","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_6_2=new Array(bulletImg+"News Archive"+underLine,"index.php?n=forum&sub=viewforum&fid=1","",0,15,110,"","","","","","",-1,-1,-1,"","");

	Menu1_7=new Array(bulletImg+"Armory"+underLine,"index.php?n=server&sub=chars","",6,15,110,"","","","","","",-1,-1,-1,"",""); 
		Menu1_7_1=new Array(bulletImg+"Characters"+underLine,"index.php?n=server&sub=chars","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_7_2=new Array(bulletImg+"Guilds"+underLine,"index.php?n=server&sub=guilds","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_7_3=new Array(bulletImg+"Honor"+underLine,"index.php?n=server&sub=honor","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_7_4=new Array(bulletImg+"Talents"+underLine,"index.php?n=server&sub=talents","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_7_5=new Array(bulletImg+"Items"+underLine,"index.php?n=server&sub=items","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_7_6=new Array(bulletImg+"Marketplace"+underLine,"index.php?n=server&sub=marketplace","",0,15,110,"","","","","","",-1,-1,-1,"","");
		
	Menu1_8=new Array(bulletImg+"Support"+underLine,"#","",6,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_1=new Array(bulletImg+"Bug Tracker"+underLine,"index.php?n=forum&sub=viewforum&fid=2","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_2=new Array(bulletImg+"SPP Discord"+underLine,"https://discord.gg/TpxqWWT","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_3=new Array(bulletImg+"Bots Discord"+underLine,"https://discord.gg/s4JGKG2BUW","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_4=new Array(bulletImg+"SPP Proxmox"+underLine,"https://github.com/japtenks/spp-cmangos-prox/issues","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_5=new Array(bulletImg+"Mangos Bots"+underLine,"https://github.com/celguar/mangos-classic/tree/ike3-bots","",0,15,110,"","","","","","",-1,-1,-1,"","");
		Menu1_8_6=new Array(bulletImg+"Website Issues"+underLine,"https://github.com/japtenks/SPP-Armory-Website/issues","",0,15,110,"","","","","","",-1,-1,-1,"","");

