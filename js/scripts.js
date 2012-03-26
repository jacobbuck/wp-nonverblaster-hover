/*!
  * domready (c) Dustin Diaz 2012 - License MIT
  */
!function (name, definition) {
  if (typeof module != 'undefined') module.exports = definition()
  else if (typeof define == 'function' && typeof define.amd == 'object') define(definition)
  else this[name] = definition()
}('domready', function (ready) {

  var fns = [], fn, f = false
    , doc = document
    , testEl = doc.documentElement
    , hack = testEl.doScroll
    , domContentLoaded = 'DOMContentLoaded'
    , addEventListener = 'addEventListener'
    , onreadystatechange = 'onreadystatechange'
    , readyState = 'readyState'
    , loaded = /^loade|c/.test(doc[readyState])

  function flush(f) {
    loaded = 1
    while (f = fns.shift()) f()
  }

  doc[addEventListener] && doc[addEventListener](domContentLoaded, fn = function () {
    doc.removeEventListener(domContentLoaded, fn, f)
    flush()
  }, f)


  hack && doc.attachEvent(onreadystatechange, fn = function () {
    if (/^c/.test(doc[readyState])) {
      doc.detachEvent(onreadystatechange, fn)
      flush()
    }
  })

  return (ready = hack ?
    function (fn) {
      self != top ?
        loaded ? fn() : fns.push(fn) :
        function () {
          try {
            testEl.doScroll('left')
          } catch (e) {
            return setTimeout(function() { ready(fn) }, 50)
          }
          fn()
        }()
    } :
    function (fn) {
      loaded ? fn() : fns.push(fn)
    })
})

/*!
  * NonvernBlaster:hover loader (c) Jacob Buck 2012 - License MIT
  */

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
						controlBackColor : wpnbh.options.control_back_color.replace("#", "0x"),
						crop : wpnbh.options.video_crop,
						defaultVolume : 100,
						playerBackColor : is_audio ? wpnbh.options.control_back_color.replace("#", "0x") : wpnbh.options.player_back_color.replace("#", "0x"),
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
				
				return swfobject.embedSWF(
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
			
			for (var i = audios.length - 1; i > -1; i--)
				if ((" "+audios[i].className+" ").indexOf("nonverblaster") > -1)
					embedflash.apply(audios[i]);
			for (var i = videos.length - 1; i > -1; i--) 
				if ((" "+videos[i].className+" ").indexOf("nonverblaster") > -1)
					embedflash.apply(videos[i]);
			
		});
		
	}
	
} (swfobject, window, document);