function ac_favorite(product_id) {
						
	// check cookie
	if( Cookies.get('favorite_product') ) {
		var list_favorite_product = JSON.parse(Cookies.get('favorite_product'));
		var position = list_favorite_product.indexOf(product_id);
		if( position == -1 ){
			list_favorite_product.push( product_id );
			set_favorite('insert', product_id);
		} else {
			// hapus dari daftar cookie
			list_favorite_product.splice(position, 1);
			set_favorite('delete', product_id);
		}
		var json_str = JSON.stringify(list_favorite_product);
		Cookies.set('favorite_product', json_str);
	} else {
		var arr = [product_id];
		var json_str = JSON.stringify(arr);
		Cookies.set('favorite_product', json_str);
		set_favorite('insert', product_id);
	}

	// update icon
	get_this_user_favorite( product_id );
}

function get_this_user_favorite(product_id) {
	if( Cookies.get('favorite_product') ) {
		var list_favorite_product = JSON.parse(Cookies.get('favorite_product'));
		var position = list_favorite_product.indexOf(product_id);
		var total_favorite = jQuery("#total_favorite_" + product_id).html();
		if( position == -1 ){
			jQuery("#icon-favorite-"+product_id).html( '<i class="fa fa-heart tj-favorite off" aria-hidden="true"></i>' );
			jQuery("#total_favorite_" + product_id).html( ( parseInt( total_favorite ) - 1 ) );
		} else {
			jQuery("#icon-favorite-"+product_id).html( '<i class="fa fa-heart tj-favorite on" aria-hidden="true"></i>' );
			jQuery("#total_favorite_" + product_id).html( ( parseInt( total_favorite ) + 1 ) );
		}
	}
}

function set_favorite(action, product_id) {
	
	jQuery("#icon-spin-"+product_id).css({"visibility":"visible"});
	jQuery("#icon-favorite-"+product_id).hide();

	var dataPost = {
        'action'		: 'update_favorite',
        'fav_action'	: action,
        'product_id' 	: product_id
    };

    jQuery.ajax({
		url : favorite_object.ajax_url, 
		type: 'POST', 
		data : dataPost, 
		success: function(response){
			console.log( response );
			/*if( response != 'no_action' ) {
				jQuery("#total_favorite_" + product_id).html( response );
				get_this_user_favorite( product_id );
			}*/
			jQuery("#icon-favorite-"+product_id).show();
			jQuery("#icon-spin-"+product_id).css({"visibility":"hidden"});
		}
	});
}

/*function action_favorite(action, product_id) {

	jQuery("#icon-spin-"+product_id).css({"visibility":"visible"});
	jQuery("#icon-favorite-"+product_id).hide();

	var dataPost = {
        'action'		: 'update_favorite',
        'fav_action'	: action,
        'product_id' 	: product_id
    };

    jQuery.ajax({
		url : favorite_object.ajax_url, 
		type: 'POST', 
		data : dataPost, 
		success: function(response){
			if( response != 'no_action' ) {

				if( Cookies.get('favorite_product') ) {
					var list_favorite_product = JSON.parse(Cookies.get('favorite_product'));
					var position = list_favorite_product.indexOf(product_id);
					if( position == -1 ){
					} else {
						// hapus dari daftar cookie
						list_favorite_product.splice(position, 1);
					}
					var json_str = JSON.stringify(list_favorite_product);
					Cookies.set('favorite_product', json_str);
				} else {
					var arr = [product_id];
					var json_str = JSON.stringify(arr);
					Cookies.set('favorite_product', json_str);
				}

				location.reload();
			} else {
				alert("gagal menghapus, coba lagi");
			}
		}
	});
}*/