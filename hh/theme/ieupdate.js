var FlashDetect=new function(){var self=this;var activeXDetectRules=[{"name":"ShockwaveFlash.ShockwaveFlash.7","version":function(obj){return getActiveXVersion(obj)}}];var getActiveXVersion=function(activeXObj){var version=-1;try{version=activeXObj.GetVariable("$version")}catch(err){}return version};var getActiveXObject=function(name){var obj=-1;try{obj=new ActiveXObject(name)}catch(err){}return obj};var parseActiveXVersion=function(str){var versionArray=str.split(",");return{"major":parseInt(versionArray[0].split(" ")[1],10),"minor":parseInt(versionArray[1],10),"revision":parseInt(versionArray[2],10),"revisionStr":versionArray[2]}};self.FlashDetect=function(){if(navigator.plugins&&navigator.plugins.length>0){var mimeTypes=navigator.mimeTypes;self.major=parseInt(mimeTypes['application/x-shockwave-flash'].enabledPlugin.description.split(' ')[2].split('.')[0],10);}else if(navigator.appVersion.indexOf("Mac")==-1&&window.execScript){var version=-1;for(var i=0;i<activeXDetectRules.length&&version==-1;i++){var obj=getActiveXObject(activeXDetectRules[i].name);if(typeof obj=="object"){version=activeXDetectRules[i].version(obj);var versionObj=parseActiveXVersion(version);self.major=versionObj.major;}}}}()};if(FlashDetect.major>8){objects=document.getElementsByTagName("object");for(var i=0;i<objects.length;i++){objects[i].outerHTML=objects[i].outerHTML}}else{objects=document.getElementsByTagName("object");for(var i=0;i<objects.length;i++){objects[i].outerHTML='<div style="text-align:center;width:'+objects[i].width+'px;height:'+objects[i].height+'px">Du skal have den seneste version af flash for at se denne video, Flash kan gratis hentes her:<br /><a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash&amp;promoid=BIOW"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Hent Flash" width="88" height="31" title="" /></a>';objects[i].innerHTML='<div style="text-align:center;width:'+objects[i].width+'px;height:'+objects[i].height+'px">Du skal have den seneste version af flash for at se denne video, Flash kan gratis hentes her:<br /><a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash&amp;promoid=BIOW"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Hent Flash" width="88" height="31" title="" /></a>'}}