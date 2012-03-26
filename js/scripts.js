! function (swfobject, options, window, document) {
	
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
						allowSmoothing : true,
						autoPlay : !! props.autoplay,
						buffer : 6,
						showTimecode : ! is_audio,
						loop : !! props.loop,
						controlColor : options.control_color.replace("#", "0x"),
						controlBackColor : options.control_back_color.replace("#", "0x"),
						crop : options.video_crop,
						defaultVolume : 100,
						playerBackColor : options[is_audio ? "control_back_color" : "player_back_color"].replace("#", "0x"),
						treatAsAudio : is_audio,
						controlsEnabled : !! props.controls
					},
					params = {
						menu : false,
						allowFullScreen : ! is_audio,
						allowScriptAccess : "always",
						wmode : "transparent"
					},
					attributes = {
						id : this.id,
						class : this.className
					};
				
				if (!! props.hdsrc) {
					flashvars.hdURL = props.hdsrc;
					flashvars.defaultHD = !! options.video_default_hd;
				}
				if (!! props.poster)
					flashvars.teaserURL = props.poster;
				
				return swfobject.embedSWF(
					nonverblaster_swf,
					this.id, 
					is_audio ? options.audio_width : props.width, 
					is_audio ? "17" : props.height, 
					"9", 
					expressinstall_swf, 
					flashvars, 
					params, 
					attributes
				);
			};
		
		swfobject.addDomLoadEvent(function () {
			var audios = document.getElementsByTagName("audio"),
				videos = document.getElementsByTagName("video");
			
			for (var i = audios.length - 1; i > -1; i--)
				if ((" "+audios[i].className+" ").indexOf("nonverblaster") > -1)
					embedflash.apply(audios[i]);
			for (var i = videos.length - 1; i > -1; i--) 
				if ((" "+videos[i].className+" ").indexOf("nonverblaster") > -1)
					embedflash.apply(videos[i]);
			
		});
		
	}
	
} (swfobject, wpnbh.options, window, document);