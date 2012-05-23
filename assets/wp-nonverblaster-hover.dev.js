(function (document, swfobject, options, assets_url) {
	
	window.embednonverblasterhover = function () {
		if (! swfobject.hasFlashPlayerVersion("9")) 
			return;
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
				playerBackColor : options[(is_audio ? "control" : "player")+"_back_color"].replace("#", "0x"),
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
				"id" : this.id,
				"class" : this.className
			};
		
		if (props.hdsrc) {
			flashvars.hdURL = props.hdsrc;
			flashvars.defaultHD = !! options.video_default_hd;
		}
		if (props.poster)
			flashvars.teaserURL = props.poster;
		
		return swfobject.embedSWF(
			assets_url + "NonverBlaster.swf",
			this.id, 
			is_audio ? options.audio_width : props.width, 
			is_audio ? "17" : props.height, 
			"9", 
			assets_url + "expressinstall.swf", 
			flashvars, 
			params, 
			attributes
		);
	};
	
	swfobject.addDomLoadEvent(function () {
		if (! swfobject.hasFlashPlayerVersion("9")) 
			return;
		var audios = document.getElementsByTagName("audio"),
			videos = document.getElementsByTagName("video"),
			all = [];
		for (var i = 0; i < audios.length; i++) 
			all.push(audios[i]);
		for (var i = 0; i < videos.length; i++) 
			all.push(videos[i]);
		for (var i = all.length - 1; i > -1; i--)
			if (all[i].className.match(/\bnonverblaster\b/))
				embednonverblasterhover.apply(all[i]);
	});
			
} (document, swfobject, wpnbh.options, wpnbh.assets_url));