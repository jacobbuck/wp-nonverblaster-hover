/*!
  * domready (c) Dustin Diaz 2012 - License MIT
  */
!function(a,b){typeof module!="undefined"?module.exports=b():typeof define=="function"&&typeof define.amd=="object"?define(b):this[a]=b()}("domready",function(a){function m(a){l=1;while(a=b.shift())a()}var b=[],c,d=!1,e=document,f=e.documentElement,g=f.doScroll,h="DOMContentLoaded",i="addEventListener",j="onreadystatechange",k="readyState",l=/^loade|c/.test(e[k]);return e[i]&&e[i](h,c=function(){e.removeEventListener(h,c,d),m()},d),g&&e.attachEvent(j,c=function(){/^c/.test(e[k])&&(e.detachEvent(j,c),m())}),a=g?function(c){self!=top?l?c():b.push(c):function(){try{f.doScroll("left")}catch(b){return setTimeout(function(){a(c)},50)}c()}()}:function(a){l?a():b.push(a)}})


! function (swfobject, window, document) {
	
	if (swfobject.hasFlashPlayerVersion("9")) {
		
		var nonverblaster_swf = wpnbh.plugins_url +  "/swf/NonverBlaster.swf",
			expressinstall_swf = wpnbh.plugins_url +  "/swf/expressinstall.swf",
			
			embedflash = function () {
				var props = function (x) {
						var y = {};
						for (var i = 0; i < x.length; i++) 
							y[x[i].name.replace("data-", "")] = x[i].value;
						return y;
					}(this.attributes),
					is_audio = this.tagName.toLowerCase() == "audio",
					flashvars = {
						mediaURL : props.src,
						allowSmoothing : "true",
						autoPlay : !! props.autoplay,
						buffer : 6,
						showTimecode : ! is_audio,
						loop : !! props.loop,
						controlColor : wpnbh.options.control_color.replace("#", "0x"),
						controlBackColor : wpnbh.options.player_back_color.replace("#", "0x"),
						crop : wpnbh.options.video_crop,
						defaultVolume : 100,
						playerBackColor : wpnbh.options.player_back_color.replace("#", "0x"),
						treatAsAudio : is_audio,
						controlsEnabled : !! props.controls
					},
					params = {
						menu : "false",
						allowFullScreen : is_audio ? "false" : "true",
						allowScriptAccess : "always",
						wmode : "transparent"
					},
					attributes = {
						id : this.id,
						class : this.className
					};
				
				if (!! props.hdsrc) {
					flashvars.hdURL = props.hdsrc;
					flashvars.defaultHD = wpnbh.options.video_default_hd;
				}
				if (!! props.poster) {
					flashvars.teaserURL = props.poster;
				}
				
				swfobject.embedSWF(
					nonverblaster_swf,
					this.id, 
					is_audio ? wpnbh.options.audio_width : props.width, 
					is_audio ? "17" : props.height, 
					"9", 
					expressinstall_swf, 
					flashvars, 
					params, 
					attributes
				);
								
			};
		
		domready(function () {
			var audios = document.getElementsByTagName("audio"),
				videos = document.getElementsByTagName("video");
			
			for (var i = 0; i < audios.length; i++)
				if (audios[i].className.indexOf("nonverblaster") > -1) 
					embedflash.apply(audios[i]);
			
			for (var i = 0; i < videos.length; i++) 
				if (videos[i].className.indexOf("nonverblaster") > -1) 
					embedflash.apply(videos[i]);
		});
		
	}
	
} (swfobject, window, document);