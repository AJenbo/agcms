var JSON=JSON||{};JSON.stringify=JSON.stringify||function(obj){var t=typeof(obj);if(t=="undefined"){return}else if(typeof obj.toJSON!="undefined"){obj=obj.toJSON();if(typeof(obj)=="string")obj='"'+obj.replace(/"/g,'\\"')+'"';return String(obj)}else if(t!="object"||obj===null){if(t=="string")obj='"'+obj.replace(/"/g,'\\"')+'"';return String(obj)}else{var n,v,json=[],arr=(obj&&obj.constructor==Array);for(n in obj){v=JSON.stringify(obj[n]);json[json.length]=(arr?"":'"'+n+'":')+String(v)}return(arr?"[":"{")+String(json)+(arr?"]":"}")}};if(typeof Date.prototype.toJSON=='undefined'){Date.prototype.toJSON=function(key){return isFinite(this.valueOf())?this.getUTCFullYear()+'-'+f(this.getUTCMonth()+1)+'-'+f(this.getUTCDate())+'T'+f(this.getUTCHours())+':'+f(this.getUTCMinutes())+':'+f(this.getUTCSeconds())+'Z':null};String.prototype.toJSON=Number.prototype.toJSON=Boolean.prototype.toJSON=function(key){return this.valueOf()}}