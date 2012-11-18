/**********************************************************
Austin grouped Javascript files together for speed purposes
**********************************************************/

/* BEGAN - Google Analytics tracking code */
var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-3745218-1']);
	_gaq.push(['_setDomainName', 'astrosafari.com']);
	_gaq.push(['_trackPageview']);

(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
/* ENDED - Google Analytics tracking code */

$(document).ready(function(){

	/* BEGAN - Create New Topic action in nav.html */
		$(".newTopicLink").click(function(){
			$(".newTopicListItem").toggle("slow");
			return false;
		});
	/* ENDED - Create New Topic action in nav.html */
	
	/* BEGAN - Create New Topic button in overall_side2.html */
		$(".newTopicButton").click(function(){
			$(".newTopicToggle").toggle("slow");
			return false;
		});
	/* ENDED - Create New Topic button in overall_side2.html */

});
