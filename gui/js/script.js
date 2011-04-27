$(document).ready(function() {
	$(".popup").fancybox(fancyboxoptions("ajax"));
	$(".popup.iframe").fancybox(fancyboxoptions("iframe"));
	$(".popup-image").fancybox(fancyboxoptions("photo"));

	$(".datatable.clickable tr td").bind("click", function(e) {
		var $clicked = $(e.target);
		if (!$clicked.closest("a").get(0)){
			var $this = $(this).closest("tr");
			console.log($this.find("a.rowaction"));
			$this.find("a.rowaction:first").click();
			console.log($this.find("a.rowaction:first").html())
		}
	})


	$(".deletebtn").click(function(){
		return confirm("Are you sure you want to delete this record?");
	})
});
var transitionSpeed = 300;
function fancyboxoptions(type) {
	if (type) {
		var fancybox_popup = {
			'type'			:	type,
			'transitionIn'	:	'elastic',
			'transitionOut'	:	'elastic',
			//'modal'			: 	true,
			'scrolling'		:	 'no',
			'autoDimensions':	false,
			'autoScale'		:	false,
			'centerOnScroll':	true,
			'titleShow'		:	true,
			'titleFormat'	:	function(titleStr, currentArray, currentIndex, currentOpts) {
//+ $(currentOpts.orig).attr("class")
				var $this = currentOpts.orig;

				var $title = currentOpts.orig.next(".link-title").html();
				if (!$title) {
					$title = currentOpts.orig.attr("title");
				}

				return '<div>' + $title + '</div>';
			},
			'speedIn'		:   transitionSpeed,
			'speedOut'		:	transitionSpeed,
			'overlayShow'	:	true,
			'overlayOpacity':	0.8,
			'overlayColor'	:	"#fff",
			'padding'		:	0,
			'margin'		:	0,
			//'showCloseButton':  false,
			'width'			:   700,
			'height'		:   420,
			'showNavArrows'	:	false,
			'onComplete'	:   function() {

			},
			'onClosed'	    :   function() {

			}
		};
		var fancybox_popup_image = {
			'transitionIn'	:	'elastic',
			'transitionOut'	:	'elastic',
			//'modal'			: 	true,
			'centerOnScroll':	true,
			'titleShow'		:	true,
			titlePosition : 'over',
			'titleFormat'	:	function(titleStr, currentArray, currentIndex, currentOpts) {

				return '<div id="fancybox-title-over">' + $(currentOpts.orig).attr("alt") + '</div>';
			},
			'speedIn'		:   transitionSpeed,
			'speedOut'		:	transitionSpeed,
			'overlayShow'	:	true,
			'overlayOpacity':	0.7,
			'overlayColor'	:	"#000",
			'padding'		:	0,
			'margin'		:	0
		};

		if (type == "photo") {
			return fancybox_popup_image;
		} else {
			return fancybox_popup;
		}
	} else {
		return "";
	}
}