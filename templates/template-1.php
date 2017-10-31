<?php
/**
 * Template Product 1
 * @package woocommerce favourite
 * @since 1.0.0
 * @version 1.0.0
 */

$product = new WC_Product( get_the_ID() );
// Ensure visibility
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php post_class(); ?>>
	<div class="lm-item">
	
		<?php
			do_action( 'woocommerce_before_shop_loop_item' );

			do_action( 'woocommerce_before_shop_loop_item_title' );

			do_action( 'woocommerce_shop_loop_item_title' );

			do_action( 'woocommerce_after_shop_loop_item_title' );

			do_action( 'woocommerce_after_shop_loop_item' );
		?>
	</div>
</li>