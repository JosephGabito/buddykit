<?php
namespace OptionKit\FieldTypes;

class Radio {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		
		do_action('optionkit-before-field');

		if ( empty( $this->atts['options'] ) ) {
			esc_html_e('options argument for field type "radio" is not defined', 'optionkit');
			return;
		}

		$value = get_option( $this->atts['id'], $this->atts['default'] );
		
		$options = apply_filters( 'optionkit-field-value-'.$this->atts['id'], $this->atts['options'] ); 
		
		foreach( $options as $key => $val ): ?>

			<?php $checked = ''; ?>

			<?php if ( $key === $value ) {?>
				<?php $checked = 'checked'; ?>
			<?php } ?>

			<?php $field_id = sprintf("%s-%s",$this->atts['id'], $key ); ?>

			<p class="optionkit-radio">
				<label for="<?php echo esc_attr( $field_id ); ?>">
				<input value="<?php echo esc_attr( $key ); ?>" <?php echo esc_attr( $checked ); ?> type="radio" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $this->atts['id'] ); ?>" />
				<?php echo esc_html( $val ); ?>
				</label>
			</p>

		<?php endforeach; ?>

		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}