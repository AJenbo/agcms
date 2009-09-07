<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/admin/inc/logon.php';
?><div id="mainmenu"> <?php
	
	if(!empty($_GET['side'])) {
		if($_GET['side'] == 'redigerside') {
			$activityButtons[] = '<li><a onclick="updateSide('.$_GET['id'].');"><img src="images/disk.png" width="16" height="16" alt="" title="Gem siden" /> Gem siden</a></li>';
		} elseif($_GET['side'] == 'redigerkat') {
			$activityButtons[] = '<li><a onclick="updateKat('.$_GET['id'].');"><img src="images/disk.png" width="16" height="16" alt="" title="Gem siden" /> Gem kategori</a></li>';
		} elseif($_GET['side'] == 'redigerFrontpage') {
			$activityButtons[] = '<li><a onclick="updateForside();"><img src="images/disk.png" width="16" height="16" alt="" title="Gem siden" /> Gem siden</a></li>';
		} elseif($_GET['side'] == 'redigerSpecial') {
			$activityButtons[] = '<li><a onclick="updateSpecial('.$_GET['id'].');"><img src="images/disk.png" width="16" height="16" alt="" title="Gem siden" /> Gem siden</a></li>';
		} elseif($_GET['side'] == 'editContact') {
			$activityButtons[] = '<li><a onclick="updateContact('.$_GET['id'].');"><img src="images/disk.png" width="16" height="16" alt="" title="Gem kontakt" /> Gem kontakt</a></li>';
		} elseif($_GET['side'] == 'nyside') {
			$activityButtons[] = '<li><a onclick="opretSide();"><img src="images/disk.png" width="16" height="16" alt="" title="Gem siden" /> Gem siden</a></li>';
		} elseif($_GET['side'] == 'nykat') {
			$activityButtons[] = '<li><a onclick="save_ny_kat();"><img src="images/disk.png" width="16" height="16" alt="" title="Gem kategori" /> Gem kategori</a></li>';
		} elseif($_GET['side'] == 'nykrav' || $_GET['side'] == 'editkrav') {
			$activityButtons[] = '<li><a onclick="save_krav();"><img src="images/disk.png" width="16" height="16" alt="" title="Gem krav" /> Gem krav</a></li>';
		} elseif($_GET['side'] == 'listsort' && $_GET['id']) {
			$activityButtons[] = '<li><a onclick="saveListOrder('.$_GET['id'].');"><img src="images/disk.png" width="16" height="16" alt="" title="Gem liste" /> Gem liste</a></li>';
		} elseif($_GET['side'] == 'newemail' || $_GET['side'] == 'editemail') {
			$activityButtons[] = '<li><a onclick="saveEmail();"><img src="images/disk.png" width="16" height="16" alt="" title="Gem E-mail" /> Gem E-mail</a></li>';
			$activityButtons[] = '<li><a onclick="sendEmail();"><img src="images/email_go.png" width="16" height="16" alt="" title="Send E-mail" /> Send E-mail</a></li>';
		}
	}
	
	$activityButtons[] = '<li id="loading" style="cursor:default;"><img src="images/loading.gif" width="16" height="16" alt="Arbejder" title="Arbejder" /> Arbejder</li>';
	
	if($activityButtons) {
		?><a class="menuboxheader" href="" onclick="showhide('Activity');">Handlinger</a>
		<ul id="Activity"<?php
		if(!empty($_COOKIE['hideActivity']))
			echo(' style="display:none"');
		?>><?php
		
		foreach($activityButtons as $value)
			echo($value);
		
		?></ul><?php
	}
	
  ?><a class="menuboxheader" href="" onclick="showhide('Indhold');">Indhold</a>
  <ul id="Indhold"<?php
  if(!empty($_COOKIE['hideIndhold']))
	  echo(' style="display:none"');
  ?>>
    <li><a href="./?side=nyside"><img src="images/page_add.png" width="16" height="16" alt="" /> Opret side</a></li>
    <li><a href="./?side=nykat"><img src="images/folder_add.png" width="16" height="16" alt="" /> Opret kategori</a></li>
    <li><a href="./?side=getSiteTree"><img src="images/book_open.png" width="16" height="16" alt="" /> Oversigt </a></li>
    <li><a href="#" onclick="return jumpto()"><img src="images/book_go.png" width="16" height="16" alt="" /> Spring til side</a>
      <form onsubmit="jumpto(); return false;" action="" method="get"><input style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" type="submit" accesskey="g" />
        <p style="display:inline"><input style="width:30px;" id="jumptoid" name="id" /></p>
      </form>
    </li>
    <li><a href="#" onclick="return sogsearch()"><img src="images/find.png" width="16" height="16" alt="" /> Søg</a>
      <form onsubmit="sogsearch(); return false;" action="" method="get"><input style="width:1px; height:1px; position:absolute; top: -20px; left:-20px;" type="submit" accesskey="f" />
        <p style="display:inline"><input style="width:91px;" id="sogtext" name="text" /><input type="hidden" name="side" value="search" /></p>
      </form>
    </li>
  </ul>
  <a class="menuboxheader" href="" onclick="showhide('Suplemanger');">Lister</a>
  <ul id="Suplemanger"<?php
  if(@$_COOKIE['hideSuplemanger'])
	  echo(' style="display:none"');
  ?>>
    <li><a href="./?side=krav"><img src="images/page_white_key.png" width="16" height="16" alt="" /> Krav</a></li>
    <li><a href="./?side=maerker"><img src="images/page_white_medal.png" width="16" height="16" alt="" /> Mærker</a></li>
    <li><a href="./?side=listsort"><img src="images/shape_align_left.png" width="16" height="16" alt="" /> Liste sortering</a></li>
  </ul>
  <a class="menuboxheader" href="" onclick="showhide('Tools');">Værktøjer</a>
  <ul id="Tools"<?php
  if(@$_COOKIE['hideTools'])
	  echo(' style="display:none"');
  ?>>
    <li><a onclick="explorer('','');"><img src="images/folder_page_white.png" width="16" height="16" alt="" /> Åben fil håndtering</a></li>
    <li><a href="./?side=emaillist"><img src="images/email.png" width="16" height="16" alt="" /> Nyhedsbreve</a></li>
    <li><a href="./?side=addressbook"><img src="images/book_addresses.png" width="16" height="16" alt="" /> Adressebog</a></li>
    <li><a href="./?side=sogogerstat"><img src="images/page_white_find.png" width="16" height="16" alt="" /> Søg og erstat</a></li>
    <li><a href="./?side=get_db_error"><img src="images/database_error.png" width="16" height="16" alt="" /> Database scanning</a></li>
    <li><a target="_blank" href="/php/"><img src="images/database_gear.png" width="16" height="16" alt="" /> MyPHPAdmin</a></li>
    <li><a href="list_krav.php" onclick="alert('todo'); return false;"><img src="images/group_edit.png" width="16" height="16" alt="" /> Rediger Grupper</a></li>
    <li><a href="katalog-lables.php"><img src="images/printer.png" width="16" height="16" alt="" /> Katalog labels</a></li>
	<li><a href="fakturas.php"><img src="images/table_multiple.png" width="16" height="16" alt="" /> Fakturas</a></li>
  </ul>
</div>