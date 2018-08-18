<?php
namespace OptionKit\FieldTypes;

class CheckBox {
	
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

		$options = apply_filters( 'optionkit-field-value-'.$this->atts['id'], $this->atts['options'] ); 

		foreach( $options as $key => $val ): ?>

			<?php $checked = ''; ?>
			
			<?php $name = sprintf("%s[]", $this->atts['id']); ?>

			<?php $saved_option = get_option( $this->atts['id'] ); ?>

			<?php if ( in_array( $key, $saved_option ) ) { ?>
				<?php $checked = 'checked'; ?>
			<?php } ?>

			<?php $field_id = sprintf("%s-%s",$this->atts['id'], $key ); ?>
			
			<p class="optionkit-checkbox">
				<label for="<?php echo esc_attr( $field_id ); ?>">
				<input <?php echo esc_attr( $checked ); ?> value="<?php echo esc_attr( $key); ?>" type="checkbox" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" />
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