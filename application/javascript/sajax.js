var sajax={debugMode:!1,failureRedirect:"",remoteUri:"",requestType:"",targetId:"",requests:[]};sajax.debug=function(a){return sajax.debugMode&&alert(a),!0},sajax.failure=function(a){return""===sajax.failureRedirect||sajax.debugMode||(window.location.href=sajax.failureRedirect),sajax.debug(a),!1},sajax.cancel=function(a){if(0===arguments.length)for(var e=0;e<sajax.requests.length;e++)sajax.requests[e]&&(sajax.requests[e].abort(),sajax.requests.splice(e,1,null));else sajax.requests[a]&&(sajax.requests[a].abort(),sajax.requests.splice(a,1,null))},sajax.doCall=function(funcName,args,method,asynchronous,uri){var i,x,data,targetId=sajax.targetId,argsarray=[];if(asynchronous)var id=sajax.requests.length;for(""!==sajax.requestType&&(method=sajax.requestType),"POST"!==method&&(method="GET"),""!==sajax.remoteUri&&(uri=sajax.remoteUri),""===uri&&(uri=window.location.href.replace(/#.*$/,"")),sajax.debug("in sajax.doCall().."+method+"/"+sajax.targetId),i=0;i<args.length-1;i++)argsarray[i]=args[i];if(data="rs="+encodeURIComponent(funcName),argsarray.length>0)try{data+="&rsargs="+encodeURIComponent(JSON.stringify(argsarray))+"&"}catch(a){return sajax.failure("JSON.stringify() failed for user agent:\n"+navigator.userAgent)}try{x=new window.XMLHttpRequest}catch(a){}if(null===x||"number"!=typeof x.readyState)return sajax.failure("NULL sajax object for user agent:\n"+navigator.userAgent);if("GET"===method&&(uri+data).length>512&&(method="POST",sajax.debug("Data to long for GET switching to POST")),"POST"===method&&void 0===x.setRequestHeader){if(!((uri+data).length<512))return sajax.failure("Request failed for user agent:\n"+navigator.userAgent);sajax.debug("Browser did not support POST, switching to GET"),method="GET"}"GET"===method&&(uri+=(-1===uri.indexOf("?")?"?":"&")+data,data=null),x.open(method,uri,asynchronous),"POST"===method&&(x.setRequestHeader("Method","POST "+uri+" HTTP/1.1"),x.setRequestHeader("Content-Type","application/x-www-form-urlencoded"));var alreadydone=!1,responcefunc=function(){if(!0===alreadydone||4!==x.readyState)return!1;var data=x.responseText.replace(/^\s*|\s*$/g,""),status=data.charAt(0);if("-"!==status&&"+"!==status||(data=data.substring(2)),""===status&&(200===x.status||0===x.status||""===x.status||"12019"===x.status))return!1;if("+"!==status||200!==x.status)return alert("Error "+x.status+": "+data),!1;alreadydone=!0;var extraData=!1,callback=args[args.length-1];"object"==typeof callback&&(extraData=callback.extraData,callback=callback.callback);try{if("undefined"!=typeof JSON&&void 0!==JSON.parse)try{var res=JSON.parse(data)}catch(a){return sajax.failure("JSON.parse failed for user agent:\n"+navigator.userAgent)}else sajax.debug("Warning: JSON is being directly executed via eval()!"),eval("var res = ("+data+"); res;");targetId?document.getElementById(targetId).innerHTML=res:callback(res,extraData),asynchronous&&sajax.requests.splice(id,1,null)}catch(a){return sajax.debug("Caught error "+a+": Could not parse "+data),!1}return!0};asynchronous&&(x.onreadystatechange=responcefunc),sajax.debug(funcName+" uri = "+uri+"/post = "+data);try{x.send(data)}catch(a){return"POST"===method&&""===uri?(sajax.debug("Browser did not support POST, tyring GET instead"),sajax.requestType="",sajax.doCall(funcName,args,"GET",asynchronous)):sajax.failure("Request failed for user agent:\n"+navigator.userAgent)}return sajax.debug(funcName+" waiting.."),asynchronous?(sajax.requests[id]=x,id):responcefunc()},void 0===window.XMLHttpRequest&&(window.XMLHttpRequest=function(){for(var a=["Msxml2.XMLHTTP.6.0","Msxml2.XMLHTTP.5.0","Msxml2.XMLHTTP.4.0","Msxml2.XMLHTTP.3.0","Msxml2.XMLHTTP","Microsoft.XMLHTTP"],e=0;e<a.length;e++)try{return new window.ActiveXObject(a[e])}catch(a){}return null}),"undefined"==typeof encodeURIComponent&&(encodeURIComponent=function(a){this.encodeChar=function(a){var e="";(a=a.charCodeAt(0))<128?e+=String.fromCharCode(a):a>127&&a<2048?(e+=String.fromCharCode(a>>6|192),e+=String.fromCharCode(63&a|128)):(e+=String.fromCharCode(a>>12|224),e+=String.fromCharCode(a>>6&63|128),e+=String.fromCharCode(63&a|128));for(var r="",t=0;t<e.length;t++)r+="%"+e.charCodeAt(t).toString(16).toUpperCase();return r},a=a.replace(/\r\n/g,"\n");for(var e="",r=0;r<a.length;r++)null===a.charAt(r).match(/[~!*()'a-z0-9]/i)?e+=encodeChar(a.charAt(r)):e+=a.charAt(r);return e});
