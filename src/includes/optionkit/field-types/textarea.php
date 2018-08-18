<?php
namespace OptionKit\FieldTypes;

class TextArea {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		do_action('optionkit-before-field');
		
		?>
		<textarea
			id="<?php echo esc_attr( $this->atts['id'] ); ?>"
			name="<?php echo esc_attr( $this->atts['name'] ); ?>"
			<?php  echo $this->atts['attributes'] ; ?>
		><?php echo sanitize_textarea_field( get_option( $this->atts['id'], $this->atts['default'] ) ); ?></textarea>
	
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}