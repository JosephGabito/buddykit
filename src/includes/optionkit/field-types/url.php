<?php
namespace OptionKit\FieldTypes;

class Url {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		do_action('optionkit-before-field');

		?>
		<input
			type="url" 
			id="<?php echo esc_attr( $this->atts['id'] ); ?>"
			name="<?php echo esc_attr( $this->atts['name'] ); ?>"
			value="<?php echo sanitize_text_field( get_option( $this->atts['id'], $this->atts['default'] ) ); ?>" 
			<?php echo $this->atts['attributes'] ; ?>
		/>
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}