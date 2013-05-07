// Gumby is ready to go
Gumby.ready(function() {
	console.log('Gumby is ready to go...', Gumby.debug());

	var sunflower = "rgba(241, 196, 15, .25)";
	var emerland = "rgba(46, 204, 113, .25)";
	var wisteria = "rgba(142, 68, 173, .25)";
	var alizarin = "rgba(231, 76, 60, .25)";

	$('body').css("background-color", alizarin);
	$('.tab-content#home').fadeIn(2000);		

	// placeholder polyfil
	if(Gumby.isOldie || Gumby.$dom.find('html').hasClass('ie9')) {
		$('input, textarea').placeholder();
	}

	$('#about_triangle').click(function(){
		$('.tab-content').removeClass("active");
		$('.tab-content').attr("style", "");
		$('body').attr("style", "");
		$('.tab-content#about-me').addClass("active");
		$('body').css("background-color", sunflower);
		$('.tab-content#about-me').fadeIn(2000);		
	});
	$('#projects_triangle').click(function(){
		$('.tab-content').removeClass("active");
		$('.tab-content').attr("style", "");
		$('body').attr("style", "");
		$('.tab-content#projects').addClass("active");
		$('body').css("background-color", emerland);	
		$('.tab-content#projects').fadeIn(2000);
	});
	$('#blog_triangle').click(function(){
		$('.tab-content').removeClass("active");
		$('.tab-content').attr("style", "");
		$('body').attr("style", "");
		$('.tab-content#blog').addClass("active");
		$('body').css("background-color", wisteria);
		$('.tab-content#blog').fadeIn(2000);	
	});
	$('#home_triangle').click(function(){
		$('.tab-content').removeClass("active");
		$('.tab-content').attr("style", "");
		$('body').attr("style", "");
		$('.tab-content#home').addClass("active");
		$('body').css("background-color", alizarin);
		$('.tab-content#home').fadeIn(2000);		
	});
});

// Oldie document loaded
Gumby.oldie(function() {
	console.log("Oldie");
});

// Document ready
$(function() {

});

