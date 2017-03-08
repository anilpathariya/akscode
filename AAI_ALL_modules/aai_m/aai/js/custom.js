// JavaScript Document

// When the user clicks on the div, toggle between hiding and showing the dropdown content
function myFunction() {
	document.getElementById("userDropdown").classList.toggle("show");
};

// Close the dropdown if the user clicks outside of it

window.onclick = function(event) {
	if (!event.target.matches('.user')) {
		
		var dropdowns = document.getElementsByClassName("user-dropdown-content");
		
		var i;
		for (i = 0; i < dropdowns.length; i++) {
			var openDropdown = dropdowns[i];
			if (openDropdown.classList.contains('show')) {
				
				openDropdown.classList.remove('show');
			}
		}
	}
};