
function polarToCartesian(centerX, centerY, radius, angleInDegrees) 
{
  var angleInRadians = (angleInDegrees-90) * Math.PI / 180.0;
  
  var xx = centerX + (radius * Math.cos(angleInRadians));
  var yy = centerY + (radius * Math.sin(angleInRadians));
  
  xx = +(parseFloat(xx).toFixed(2));
  yy = +(parseFloat(yy).toFixed(2));
  
  return {
	
	x: xx,
	y: yy
  };
}

function describeArc(x, y, radius, startAngle, endAngle)
{

    var start = polarToCartesian(x, y, radius, endAngle);
    var end = polarToCartesian(x, y, radius, startAngle);

    var arcSweep = endAngle - startAngle <= 180 ? "0" : "1";


    var d = [
        "M", start.x, start.y, 
        "A", radius, radius, 0, arcSweep, 0, end.x, end.y
    ].join(" ");

    return d;    	
}

function table_tr_hover(tr_id, inout)
{
	var tr_class = jQuery( "#"+tr_id ).attr("class");
		
	jQuery( '.'+tr_class ).stop();
	jQuery( "#"+tr_id ).css({ opacity: 1 });
	
	if ( inout == "in")
		{
			jQuery( '.'+tr_class ).not( "#"+tr_id ).animate({opacity: 0.5}, 100);
		}
		
	if ( inout == 'out' )
		{
			jQuery( '.'+tr_class ).not( "#"+tr_id ).animate({opacity: 1}, 100);
		}
	
}

function slice_out(id)  //slice hover OUT effect
{
	var effect_id = jQuery( "#"+id ).parent( "svg" ).attr("id");
		effect_id = effect_id.replace( "-svg-", "-effect-" );
		
	var data_str = jQuery( "#"+id ).data("process");
	var data_arr = data_str.split(", ");
		
		
	var d = describeArc(+(parseFloat(data_arr[0]).toFixed(2)), +(parseFloat(data_arr[1]).toFixed(2)), +(parseFloat(data_arr[2]).toFixed(2)), +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)))+" L"+data_arr[0]+" "+data_arr[1]+" Z";
		
		
	var h_id = id+"-hover";
			
	jQuery( "#"+id ).attr( "opacity", "1" );
			
	jQuery( "#"+id ).attr("d", d);
	var date = new Date();
	timestamp = date.getTime();
	jQuery( "#"+id  ).data("lasttime", timestamp);
	jQuery( "#"+id  ).data("anim", '0');
			
	jQuery( "#"+id ).removeAttr("filter", 'url(#'+effect_id+')');  //REMOVE ATTRIBUTE
}

function resize_svg()
{
	jQuery( ".goo-pie-chart-main" ).each(function(){
		var svg_id = jQuery( this ).children( "svg" ).get( 0 ).id; 
		var container_width = jQuery( this ).width();
		var container_height = jQuery( this ).height();
		var viewportWidth = jQuery(window).width();
		
		var original_str = jQuery( "#"+svg_id ).data("viewbox");
		var original_arr = original_str.split(",");
		
		var original_width = original_arr[2].trim();
		var original_height = original_arr[3].trim();
		
		
		if ( viewportWidth < container_width*1.2 )  //-20%
		{
			var calc_width = container_width*0.8;
			var calc_height = container_height*0.8;
			calc_height = calc_height.toFixed(0);
			calc_width = calc_width.toFixed(0);

			jQuery( "#"+svg_id ).attr( "height", calc_height ).attr( "width", calc_width );
			jQuery( this ).css("width", calc_width+"px").css("height", calc_height+"px");
		}
		
		if ( viewportWidth > container_width*1.5 && original_width > container_width)
		{
			var calc_width = container_width*1.25;
			var calc_height = container_height*1.25;
			
			calc_height = calc_height.toFixed(0);
			calc_width = calc_width.toFixed(0);
			
			if ( calc_width >= original_width )
				{
					calc_width = original_width;
					calc_height = original_height;
				}
			
			jQuery( "#"+svg_id ).attr( "height", calc_height ).attr( "width", calc_width );
			jQuery( this ).css("width", calc_width+"px").css("height", calc_height+"px");
			
		}
		
	});
}

function goo_draw_pie_chart( identifier, container )
{
	jQuery( "#"+container+"-svg-"+identifier+" path" ).each(function(){
		if ( jQuery( this ).attr( "data-process" ) == undefined ) {return;}
		
		var id = jQuery( this ).attr("id");
		var data_str = jQuery( this ).data("process");
		var data_arr = data_str.split(", ");
		
		var effect_id = jQuery( this ).parent( "svg" ).attr("id");
		effect_id = effect_id.replace( "-svg-", "-effect-" );

		var d = describeArc(+(parseFloat(data_arr[0]).toFixed(2)), +(parseFloat(data_arr[1]).toFixed(2)), +(parseFloat(data_arr[2]).toFixed(2)), +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)))+" L"+data_arr[0]+" "+data_arr[1]+" Z";
		jQuery( this ).attr("d", d);
		jQuery ( this ).hover(function() {

			var id = jQuery( this ).attr("id");
			var data_str = jQuery( this ).data("process");
			var data_arr = data_str.split(", ");
			
			var h_id = id+"-hover";
			var new_arc = +(parseFloat(data_arr[2])*1.2).toFixed(2);  
			var h_d = describeArc(+(parseFloat(data_arr[0]).toFixed(2)), +(parseFloat(data_arr[1]).toFixed(2)), new_arc, +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)));
			
			jQuery( this ).attr( "opacity", "0.8" );	
			
			jQuery( this ).attr("filter", 'url(#'+effect_id+')');  //add effect on hover
			
			var R = +(parseFloat(data_arr[2]).toFixed(2));
			var X = +(parseFloat(data_arr[0]).toFixed(2));
			var Y = +(parseFloat(data_arr[1]).toFixed(2));
			var Xi = +(parseFloat(data_arr[5]).toFixed(2))*2.5;
			var Yi = +(parseFloat(data_arr[6]).toFixed(2))*2.5;
			
			var date = new Date();
			var timestamp = date.getTime();
			
			if ( jQuery( this ).data("anim") == '0' && timestamp > 50+jQuery( this ).data("lasttime"))
				{
					jQuery( this ).data("anim", '1');
					
					var timer = setInterval(function(){
						var distX = Math.abs(X-(+(parseFloat(data_arr[0]).toFixed(2))));
						var distY = Math.abs(Y-(+(parseFloat(data_arr[1]).toFixed(2))));
						if ( distX > 10 || distY > 10)
							{
								clearInterval(timer);
								timestamp = date.getTime();
								jQuery( "#"+id  ).data("anim", '0');
								jQuery( "#"+id  ).data("lasttime", timestamp);
								
								if ( !jQuery( "#"+id  ).is(":hover") ) 
									{
										jQuery( "#"+id ).attr("d", d);
									}
							}
						else
							{
								if ( !jQuery( "#"+id  ).is(":hover") ) 
									{
										clearInterval(timer);
										timestamp = date.getTime();
										jQuery( "#"+id  ).data("anim", '0');
										jQuery( "#"+id  ).data("lasttime", timestamp);
										jQuery( "#"+id ).attr("d", d);
									}
									else
									{
										X = X + Xi;
										Y = Y + Yi;
										dd = describeArc(+(parseFloat(X).toFixed(2)), +(parseFloat(Y).toFixed(2)), +(parseFloat(data_arr[2]).toFixed(2)), +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)))+" L"+X+" "+Y+" Z";
										jQuery( "#"+id ).attr("d", dd);
										
										timestamp = date.getTime();
										jQuery( "#"+id  ).data("lasttime", timestamp);
									}
								
								
							}
					}, 10);
				}

			table_tr_hover(id.replace('-arc-', '-tr-'), 'in'); //table hover
			
			
			
		}, function(){
			
			slice_out(id);
			
			table_tr_hover(id.replace('-arc-', '-tr-'), 'out'); //table hover
			
		});
		
	});
	
	//--------------------- TABLE HOOKS -----------------------
	jQuery( "tr."+container+"-tr-"+identifier ).each(function(){
		var tr_id = jQuery( this ).attr("id");
		
		jQuery ( this ).hover( function(){
			var slice_id=tr_id.replace('-tr-', '-arc-');
			
			var effect_id = jQuery( "#"+slice_id ).parent( "svg" ).attr("id");
			effect_id = effect_id.replace( "-svg-", "-effect-" );
			
			var data_str = jQuery( "#"+slice_id ).data("process");
			var data_arr = data_str.split(", ");
			var d = describeArc(+(parseFloat(data_arr[0]).toFixed(2)), +(parseFloat(data_arr[1]).toFixed(2)), +(parseFloat(data_arr[2]).toFixed(2)), +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)))+" L"+data_arr[0]+" "+data_arr[1]+" Z";
		
			
			table_tr_hover(tr_id,'in');
			
			//-----arc effect
			jQuery( "#"+slice_id ).attr( "opacity", "0.8" );
			
			
			jQuery( "#"+slice_id ).attr("filter", 'url(#'+effect_id+')');  //add effect on hover
			
			var R = +(parseFloat(data_arr[2]).toFixed(2));
			var X = +(parseFloat(data_arr[0]).toFixed(2));
			var Y = +(parseFloat(data_arr[1]).toFixed(2));
			var Xi = +(parseFloat(data_arr[5]).toFixed(2))*2.5;
			var Yi = +(parseFloat(data_arr[6]).toFixed(2))*2.5;
			
			var date = new Date();
			var timestamp = date.getTime();
			
			if ( jQuery( "#"+slice_id ).data("anim") == '0' && timestamp > 50+jQuery( "#"+slice_id ).data("lasttime"))
				{
					jQuery( "#"+slice_id ).data("anim", '1');
					
					var timer = setInterval(function(){
						var distX = Math.abs(X-(+(parseFloat(data_arr[0]).toFixed(2))));
						var distY = Math.abs(Y-(+(parseFloat(data_arr[1]).toFixed(2))));
						if ( distX > 10 || distY > 10)
							{
								clearInterval(timer);
								timestamp = date.getTime();
								jQuery( "#"+slice_id  ).data("anim", '0');
								jQuery( "#"+slice_id  ).data("lasttime", timestamp);
								
								if ( !jQuery( '#'+tr_id ).is(":hover") ) 
									{
										jQuery( "#"+slice_id ).attr("d", d);
									}
							}
						else
							{
								if ( !jQuery( '#'+tr_id ).is(":hover") ) 
									{
										clearInterval(timer);
										timestamp = date.getTime();
										jQuery( "#"+slice_id  ).data("anim", '0');
										jQuery( "#"+slice_id  ).data("lasttime", timestamp);
										jQuery( "#"+slice_id ).attr("d", d);
									}
									else
									{
										X = X + Xi;
										Y = Y + Yi;
										dd = describeArc(+(parseFloat(X).toFixed(2)), +(parseFloat(Y).toFixed(2)), +(parseFloat(data_arr[2]).toFixed(2)), +(parseFloat(data_arr[3]).toFixed(2)), +(parseFloat(data_arr[4]).toFixed(2)))+" L"+X+" "+Y+" Z";
										jQuery( "#"+slice_id ).attr("d", dd);
										
										timestamp = date.getTime();
										jQuery( "#"+slice_id  ).data("lasttime", timestamp);
									}
								
								
							}
					}, 10);
				}
			//---------------|
			
		}, function(){
			var slice_id=tr_id.replace('-tr-', '-arc-');
			
			table_tr_hover(tr_id,'out');
			slice_out(slice_id);
			
		} );
	});
	
}

jQuery(function(){
   jQuery( ".goo-pie-chart-main" ).each(function(){
	   goo_draw_pie_chart( jQuery( this ).data("unique"),jQuery( this ).data("container") );
   } );
});

resize_svg();

jQuery(window).resize(function() {
	resize_svg();
	
});







