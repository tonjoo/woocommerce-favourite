<?php
/*
* Plugin Name: WooCommerce Favourite
* Plugin URI: http://tonjoo.com/
* Description: Simple Woocommerce Product Favourite
* Version: 1.0.0
* Author: Tonjoo
* Author URI: http://tonjoo.com/
*
* Text Domain: woocommerce-favourite
* Domain Path: /languages/
*
* Requires at least: 4.1
* Tested up to: 4.8
*
* WC requires at least: 3.0
* WC tested up to: 3.2
*
* Copyright: © 2017 Tonjoo.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wf_db_version;
$wf_db_version = '1.0';

define('WF_PATH', plugin_dir_path(__FILE__));
define('WF_LINK', plugin_dir_url(__FILE__));
define('WF_PLUGIN_NAME', plugin_basename(__FILE__));


if ( !class_exists( 'Tj_Favorite_Product' ) ) {

	Class Tj_Favorite_Product {

		public function __construct() {
			
			register_activation_hook( __FILE__, array( $this, 'wf_install' ) );

			if ( $this->check_woocommerce() == 'woo_not_active' ) {
				add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			} else {
				add_action( 'wp_enqueue_scripts', array( $this, 'favorite_script' ) );
				add_shortcode( 'add_favorite_button', array( $this, 'favorite_button' ) );
				add_shortcode( 'favorite_list', array( $this, 'favorite_page' ) );	
			}
			
		}

		public function wf_install() {
			global $wpdb;
			global $wf_db_version;

			$table_name = $wpdb->prefix . 'wffavorite';

			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE ". $table_name ." (
					`ID` bigint(20) NOT NULL AUTO_INCREMENT,
					`date_add` datetime NOT NULL,
					`product_id` bigint(20) NOT NULL,
					`user_id` bigint(20) DEFAULT NULL,
					`ipaddress` varchar(30) NOT NULL,
					PRIMARY KEY  (`ID`)
				) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			}

			add_option( 'wf_db_version', $wf_db_version );
		}

		public function check_woocommerce() {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return 'woo_not_active';
			} else {
				return 'woo_active';
			}
		}

		public function admin_notices() {
			$class = 'notice notice-error';
			$message = __( 'Woocommerce Favourite disabled because Woocommerce Plugin is not active.', 'woocommerce-favourite' );

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		public function favorite_script() {
			wp_enqueue_style( 'font-awesome', WF_LINK .'assets/css/font-awesome.min.css' );
			wp_enqueue_style( 'wf-style', WF_LINK .'assets/css/style.css' );
			wp_enqueue_script( 'favorite-script', WF_LINK . 'assets/js/favorite.js', array('jquery') );
			wp_localize_script( 'favorite-script', 'favorite_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		}

		public function get_favorite( $product_id ) {
			global $wpdb;
			$favorite = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wffavorite WHERE product_id = %d", array( $product_id ) ) );

			return $favorite;
		}

		public function get_favorite_by_user( $product_id ) {
			global $wpdb;

			if ( is_user_logged_in() ) {

				$favorite = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wffavorite WHERE product_id = %d AND user_id = %s", array( $product_id, wp_get_current_user()->ID ) ) );

				return $favorite;

			} else {

				return 0;

			}
		}

		// create shortcode to display button
		public function favorite_button( $attr ) {
			global $product;
			global $wpdb;

			$table_name = $wpdb->prefix . 'wffavorite';

			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				echo 'table not installed';
				return;
			}

			if ( $product ) {

				$a = shortcode_atts( array(
			        'class' => 'product-fav pull-right',
			    ), $attr );

				$icon_fav_on = '<i class="fa fa-heart tj-favorite on" aria-hidden="true"></i>';
				$icon_fav_off = '<i class="fa fa-heart tj-favorite off" aria-hidden="true"></i>';

				// set icon favorite
				if ( isset( $_COOKIE['favorite_product'] ) ) { 
					$cookie_favorite = json_decode( $_COOKIE['favorite_product'] );
					if ( in_array( $product->get_id(), $cookie_favorite ) ) {
						$icon_favorite = $icon_fav_on;
					} else $icon_favorite = $icon_fav_off;
				} else if ( $this->get_favorite_by_user( $product->get_id() ) > 0 ) {
					$icon_favorite = $icon_fav_on;
				} else $icon_favorite = $icon_fav_off;


				// component	
				$component = '<span class=" '. esc_attr($a['class']) .' ">';

				$component .= '<i class="fa fa-spinner fa-spin loading-favorite" id="icon-spin-'. $product->get_id() .'"></i>';
				
				$component .= '<span class="icon-favorite" id="icon-favorite-'. $product->get_id() .'" onclick="ac_favorite('. $product->get_id() .');">'. $icon_favorite .'</span>';

				$component .= '<span id="total_favorite_'. $product->get_id() .'">' . $this->get_favorite( $product->get_id() ) . '</span>';

				$component .= '</span>';

				echo $component;

			} else {
				echo 'not a product';
			}
			
		}

		// create shortcode to display favourite list
		public function favorite_page( $attr ) {
			global $wpdb;

			if ( $this->get_favorite_product_by_user() > 0 ) {
				// ambil dari database
				$get_favorite = $wpdb->get_results( $wpdb->prepare( "SELECT product_id FROM {$wpdb->prefix}wffavorite WHERE user_id = %s", array( wp_get_current_user()->ID ) ), ARRAY_A );
				if ( count( $get_favorite ) > 0 ) {
					$list_favorite = array();

					foreach ( $get_favorite as $favorite ) {
						array_push( $list_favorite , $favorite['product_id'] );
					}
				}
			} else if ( isset( $_COOKIE['favorite_product'] ) ) { 
				// ambil dari cookie
				$list_favorite = json_decode( $_COOKIE['favorite_product'] );
			} else {
				$list_favorite = array();
			}

			if ( count( $list_favorite ) > 0 ) {
				$args = array(
					'post_type' 	=> 'product',
					'posts_per_page'=> -1,
					'post_status'	=> 'publish',
					'post__in'		=> $list_favorite,
				);

				$products = new WP_Query( $args );
				if ( $products->have_posts() ) {
					
					echo '<div class="columns-2">';

					echo '<ul class="products" id="content-area">';
					
					while ( $products->have_posts() ) :

						$products->the_post();

						include WF_PATH . 'templates/template-1.php';

					endwhile;

					echo '</ul>';

					echo '</div>';

					wp_reset_query();
				} else {
					_e( 'Tidak ada produk favorit', 'woocommerce-favourite' );
				}

			} else {
				_e( 'Tidak ada produk favorit', 'woocommerce-favourite' );
			} 

		}

		public function get_favorite_product_by_user() {
			global $wpdb;
			if ( is_user_logged_in() ) {
				$favorite = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wffavorite WHERE user_id = %s", array( wp_get_current_user()->ID ) ) );
				return $favorite;
			} else {
				return 0;
			}
		}

	}

	new Tj_Favorite_Product;

	
	/**
	 *  Update Favorite Status
	 */
	add_action( 'wp_ajax_update_favorite', 'ajax_update_favorite_callback' );
	add_action( 'wp_ajax_nopriv_update_favorite', 'ajax_update_favorite_callback' );
	function ajax_update_favorite_callback() {
		global $wpdb;
	    
	    $product_id 	= sanitize_text_field( $_POST['product_id'] );
	    $fav_action 	= sanitize_text_field( $_POST['fav_action'] );
	    $fav_date 		= date( "Y-m-d H:i:s" );
	    $fav_user 		= ( is_user_logged_in() ) ? wp_get_current_user()->ID : null;
	    $fav_ip 		= client_ip();
	    
	    if ( $fav_action == 'insert' ) {

	    	$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->prefix}wffavorite(date_add,product_id,user_id,ipaddress) VALUES(%s,%d,%s,%s)", array( $fav_date, $product_id, $fav_user, $fav_ip ) ) );

	    	$favorite = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wffavorite WHERE product_id = %d", array( $product_id ) ) );

			echo $favorite;
	 
	    } else if ( $fav_action == 'delete' ) {
	 
	    	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wffavorite WHERE product_id = %d AND ipaddress = %s AND user_id = %s", array( $product_id, $fav_ip, $fav_user ) ) );

	    	$favorite = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wffavorite WHERE product_id = %d", array( $product_id ) ) );

			echo $favorite;
	 
	    } else {
	 
	    	echo 'no_action';
	 
	    }

	    wp_die();
	}

	function client_ip() {
	    $ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}

}
?>