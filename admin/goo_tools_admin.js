jQuery(".goo_tools_product").hover(function(){
		jQuery( this ).find( ".goo_tools_icon" ).css("display", "none");
		jQuery( this ).find( ".goo_tools_desription" ).css("display", "block");
		jQuery( this ).find( ".goo_tools_button" ).css("display", "block");
	}, function(){
		jQuery( this ).find( ".goo_tools_icon" ).css("display", "block");
		jQuery( this ).find( ".goo_tools_desription" ).css("display", "none");
		jQuery( this ).find( ".goo_tools_button" ).css("display", "none");
		});