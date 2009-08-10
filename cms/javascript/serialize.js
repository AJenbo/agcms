/* 
 * More info at: http://phpjs.org
 * 
 * This is version: 2.8
 * php.js is copyright 2009 Kevin van Zonneveld.
 * 
 * Portions copyright Brett Zamir (http://brett-zamir.me), Kevin van Zonneveld
 * (http://kevin.vanzonneveld.net), Onno Marsman, Michael White
 * (http://getsprink.com), Waldo Malqui Silva, Paulo Ricardo F. Santos, Jack,
 * Philip Peterson, Jonas Raoni Soares Silva (http://www.jsfromhell.com),
 * Legaev Andrey, Ates Goral (http://magnetiq.com), Ratheous, Martijn
 * Wieringa, Nate, Enrique Gonzalez, Philippe Baumann, Webtoolkit.info
 * (http://www.webtoolkit.info/), Theriault, Ash Searle
 * (http://hexmen.com/blog/), Carlos R. L. Rodrigues
 * (http://www.jsfromhell.com), Ole Vrijenhoek, Jani Hartikainen, travc,
 * Johnny Mast (http://www.phpvrouwen.nl), T.Wild, marrtins, d3x, Alex,
 * Michael Grier, GeekFG (http://geekfg.blogspot.com), Andrea Giammarchi
 * (http://webreflection.blogspot.com), stag019, Erkekjetter, Marc Palau, Oleg
 * Eremeev, Steve Hilder, gettimeofday, gorthaur, T.J. Leahy, Public Domain
 * (http://www.json.org/json2.js), Arpad Ray (mailto:arpad@php.net), David,
 * Steven Levithan (http://blog.stevenlevithan.com), Breaking Par Consulting
 * Inc
 * (http://www.breakingpar.com/bkp/home.nsf/0/87256B280015193F87256CFB006C45F7),
 * AJ, Caio Ariede (http://caioariede.com), Tyler Akins (http://rumkin.com),
 * Alfonso Jimenez (http://www.alfonsojimenez.com), KELAN, Kankrelune
 * (http://www.webfaktory.info/), Lars Fischer, Thunder.m, Sakimori, Aman
 * Gupta, Josh Fraser
 * (http://onlineaspect.com/2007/06/08/auto-detect-a-time-zone-with-javascript/),
 * Karol Kowalski, Mirek Slugen, mdsjack (http://www.mdsjack.bo.it),
 * Pellentesque Malesuada, Raphael (Ao RUDLER), Steve Clay, kenneth, Ole
 * Vrijenhoek (http://www.nervous.nl/), nobbler, T. Wild, ger, David James,
 * madipta, Douglas Crockford (http://javascript.crockford.com), Hyam Singer
 * (http://www.impact-computing.com/), Frank Forte, Marco, mktime, marc
 * andreu, class_exists, noname, john (http://www.jd-tech.net), David Randall,
 * Paul, djmix, Lincoln Ramsay, Linuxworld, Thiago Mata
 * (http://thiagomata.blog.com), Soren Hansen, Pyerre, Jon Hohle, Bayron
 * Guevara, duncan, sankai, Denny Wardhana, Sanjoy Roy, 0m3r, Gilbert,
 * Subhasis Deb, Felix Geisendoerfer (http://www.debuggable.com/felix), T0bsn,
 * Peter-Paul Koch (http://www.quirksmode.org/js/beat.html), Eugene Bulkin
 * (http://doubleaw.com/), Der Simon (http://innerdom.sourceforge.net/), JB,
 * LH, J A R, Marc Jansen, Francesco, echo is bad, XoraX
 * (http://www.xorax.info), Tim Wiel, Brad Touesnard, MeEtc
 * (http://yass.meetcweb.com), Bryan Elliott, Nathan, Ozh, pilus,
 * http://stackoverflow.com/questions/57803/how-to-convert-decimal-to-hex-in-javascript,
 * vlado houba, Arno, Rick Waldron, Mick@el, rezna, Eric Nagel, Kirk Strobeck,
 * Martin Pool, Daniel Esteban, Saulo Vallory, Kristof Coomans (SCK-CEN
 * Belgian Nucleair Research Centre), Pierre-Luc Paour, Bobby Drake, Pul,
 * Christian Doebler, setcookie, YUI Library:
 * http://developer.yahoo.com/yui/docs/YAHOO.util.DateLocale.html, Blues at
 * http://hacks.bluesmoon.info/strftime/strftime.js, penutbutterjelly, Gabriel
 * Paderni, Luke Godfrey, Blues (http://tech.bluesmoon.info/), Anton Ongson,
 * Simon Willison (http://simonwillison.net), Jason Wong (http://carrot.org/),
 * Valentina De Rosa, Yves Sucaet, sowberry, hitwork, Norman "zEh" Fuchs,
 * johnrembo, Brian Tafoya (http://www.premasolutions.com/), Nick Callen,
 * ejsanders, Aidan Lister (http://aidanlister.com/), Philippe Jausions
 * (http://pear.php.net/user/jausions), dptr1988, James, strcasecmp, strcmp,
 * Alan C, uestla, Wagner B. Soares, metjay, ChaosNo1, Chris, Pedro Tainha
 * (http://www.pedrotainha.com), DxGx, Alexander Ermolaev
 * (http://snippets.dzone.com/user/AlexanderErmolaev), Andreas, DtTvB
 * (http://dt.in.th/2008-09-16.string-length-in-bytes.html), Luis Salazar
 * (http://www.freaky-media.com/), Tim de Koning, taith, Robin, FremyCompany,
 * Alexander M Beedie, FGFEmperor, baris ozdil, Greg Frazier, Tod Gentille,
 * Matt Bradley, Manish, Scott Cariss, Slawomir Kaniecki, ReverseSyntax,
 * Mateusz "loonquawl" Zalega, Francois, date, Cord, Victor, stensi, Jalal
 * Berrami, gabriel paderni, Yannoo, Leslie Hoare, Ben Bryan, booeyOH, Cagri
 * Ekin, mk.keck, Greenseed, Russell Walker (http://www.nbill.co.uk/),
 * Garagoth, Andrej Pavlovic, Dino, Amir Habibi
 * (http://www.residence-mixte.com/), Jay Klehr, Benjamin Lupton, davook, Atli
 * Þór, jakes, Allan Jensen (http://www.winternet.no), Howard Yeend, Kheang
 * Hok Chin (http://www.distantia.ca/), Luke Smith (http://lucassmith.name),
 * Rival, Diogo Resende
 * 
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL KEVIN VAN ZONNEVELD BE LIABLE FOR ANY CLAIM, DAMAGES
 * OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */ 


// Compression: minified


function serialize(mixed_value){var _getType=function(inp){var type=typeof inp,match;var key;if(type=='object'&&!inp){return'null';}
if(type=="object"){if(!inp.constructor){return'object';}
var cons=inp.constructor.toString();match=cons.match(/(\w+)\(/);if(match){cons=match[1].toLowerCase();}
var types=["boolean","number","string","array"];for(key in types){if(cons==types[key]){type=types[key];break;}}}
return type;};var type=_getType(mixed_value);var val,ktype='';switch(type){case"function":val="";break;case"boolean":val="b:"+(mixed_value?"1":"0");break;case"number":val=(Math.round(mixed_value)==mixed_value?"i":"d")+":"+mixed_value;break;case"string":val="s:"+encodeURIComponent(mixed_value).replace(/%../g,'x').length+":\""+mixed_value+"\"";break;case"array":case"object":val="a";var count=0;var vals="";var okey;var key;for(key in mixed_value){ktype=_getType(mixed_value[key]);if(ktype=="function"){continue;}
okey=(key.match(/^[0-9]+$/)?parseInt(key,10):key);vals+=serialize(okey)+
serialize(mixed_value[key]);count++;}
val+=":"+count+":{"+vals+"}";break;case"undefined":default:val="N";break;}
if(type!="object"&&type!="array"){val+=";";}
return val;}