(function(i,f,e,j){window.embednonverblasterhover=function(){if(f.hasFlashPlayerVersion("9")){var b=function(h){for(var k={},g=0;g<h.length;g++)k[h[g].name.replace("data-","")]=h[g].value;return k}(this.attributes),d=this.tagName.toLowerCase()=="audio",c={mediaURL:b.src,allowSmoothing:true,autoPlay:!!b.autoplay,buffer:6,showTimecode:!d,loop:!!b.loop,controlColor:e.control_color.replace("#","0x"),controlBackColor:e.control_back_color.replace("#","0x"),crop:e.video_crop,defaultVolume:100,playerBackColor:e[(d?"control":"player")+"_back_color"].replace("#","0x"),treatAsAudio:d,controlsEnabled:!!b.controls},a={menu:false,allowFullScreen:!d,allowScriptAccess:"always",wmode:"transparent"},l={id:this.id,"class":this.className};if(b.hdsrc){c.hdURL=b.hdsrc;c.defaultHD=!!e.video_default_hd}if(b.poster)c.teaserURL=b.poster;return f.embedSWF(j+"NonverBlaster.swf",this.id,d?e.audio_width:b.width,d?"17":b.height,"9",j+"expressinstall.swf",c,a,l)}};f.addDomLoadEvent(function(){if(f.hasFlashPlayerVersion("9")){for(var b=i.getElementsByTagName("audio"),d=i.getElementsByTagName("video"),c=[],a=0;a<b.length;a++)c.push(b[a]);for(a=0;a<d.length;a++)c.push(d[a]);for(a=c.length-1;a>-1;a--)c[a].className.match(/\bnonverblaster\b/)&&embednonverblasterhover.apply(c[a])}})})(document,swfobject,wpnbh.options,wpnbh.assets_url);