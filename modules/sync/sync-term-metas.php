<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A class to manage the copy and synchronization of term metas.
 *
 *  
 */
class EWT_Sync_Term_Metas extends EWT_Sync_Metas {

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->meta_type = 'term';

		parent::__construct( $easywptranslator );
	}
}
