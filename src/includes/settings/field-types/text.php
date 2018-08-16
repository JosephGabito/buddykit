<?php
namespace OptionKit\FieldTypes;

class Text {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		do_action('optionkit-before-field');
		?>
		<input
			type="text" 
			id="<?php echo esc_attr( $this->atts['id'] ); ?>"
			name="<?php echo esc_attr( $this->atts['name'] ); ?>"
			class="<?php echo esc_attr( $this->atts['class'] ); ?>"
			value="<?php echo esc_attr( get_option( $this->atts['id'], $this->atts['default'] ) ); ?>" 
		/>
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}