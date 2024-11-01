<?php
/*
Plugin Name: Waktu Solat
Plugin URI: https://fadli.my/waktu_solat
Description: A simple prayer time widget for WordPress based on JAKIM e-solat
Version: 1.0.3
Author: Fadli Saad
Author URI: https://fadli.my
License: GPL2
*/

// The widget class
class Waktu_Solat extends WP_Widget {

	// Main constructor
	public function __construct() {
		
		parent::__construct(
			'waktu_solat',
			__( 'Waktu Solat', 'waktu_solat' ),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	// The widget form (for the backend )
	public function form( $instance ) {	
		
		// Set widget defaults
		$defaults = array(
			'title'    => 'Waktu Solat',
			'api'     => '',
			'location' => 'WLY01',
			'method' => '1',
		);
		
		// Parse current settings with defaults
		extract( wp_parse_args( ( array ) $instance, $defaults ) ); ?>

		<?php // Widget Title ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Widget Title', 'waktu_solat' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<?php // Location Field ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>"><?php _e( 'Location:', 'waktu_solat' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'location' ) ); ?>" type="text" value="<?php echo esc_attr( $location ); ?>" />
		</p>

		<?php
	}

	// Update widget settings
	public function update( $new_instance, $old_instance ) {
		
		$instance = $old_instance;
		$instance['title'] = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : 'Waktu Solat';
		$instance['location'] = isset( $new_instance['location'] ) ? wp_strip_all_tags( $new_instance['location'] ) : 'WLY01';
		
		return $instance;
	}

	// Display the widget
	public function widget( $args, $instance ) {
		
		extract( $args );

		// Check the widget options
		$title    	= isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$location   = isset( $instance['location'] ) ? $instance['location'] : 'WLY01';
		$url 		= 'https://www.e-solat.gov.my/index.php?r=esolatApi/TakwimSolat&period=today&zone='.$location;

		// Check for transient, if none, grab remote HTML file
		if ( false === ( $html = get_transient( 'waktu_solat' ) ) ) {

			$response   = wp_remote_get($url, array('sslverify' => false ));

			if ( is_wp_error( $response ) ) {
				return;
			}

			// Parse remote HTML file
			$data = wp_remote_retrieve_body( $response );

			// Check for error
			if ( is_wp_error( $data ) ) {
				echo '<p class="error">Unable to get JSON data from JAKIM e-solat API.</p>';
			}

			// Store remote HTML file in transient, expire after 24 hours
			set_transient( 'waktu_solat', $data, 24 * HOUR_IN_SECONDS );

		}

		// WordPress core before_widget hook (always include )
		echo $before_widget;

	    // Display the widget
	    echo '<div class="widget-text wp_widget_plugin_box">';

			// Display widget title if defined
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}

			$times = json_decode($html, true);

			echo '<div class="table">
			<div class="rower">
				<div class="cell">Subuh</div><div class="cell">'.date('g:i A', strtotime($times['prayerTime'][0]['fajr'])).'</div>
				</div><div class="rower">
				<div class="cell">Zohor</div><div class="cell">'.date('g:i A', strtotime($times['prayerTime'][0]['dhuhr'])).'</div>
				</div><div class="rower">
				<div class="cell">Asar</div><div class="cell">'.date('g:i A', strtotime($times['prayerTime'][0]['asr'])).'</div>
				</div><div class="rower">
				<div class="cell">Maghrib</div><div class="cell">'.date('g:i A', strtotime($times['prayerTime'][0]['maghrib'])).'</div>
				</div><div class="rower">
				<div class="cell">Isyak</div><div class="cell">'.date('g:i A', strtotime($times['prayerTime'][0]['isha'])).'</div>
			</div></div>';

		echo '</div>';

		// WordPress core after_widget hook (always include )
		echo $after_widget;
	}

}

// Register the widget
function waktu_solat() {
	register_widget( 'Waktu_Solat' );
	wp_register_style('waktu_solat', plugins_url('style.css',__FILE__ ));
	wp_enqueue_style('waktu_solat');
}

add_action( 'widgets_init', 'waktu_solat' );