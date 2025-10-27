<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\REST\V1;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Other\EWT_Model;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use EasyWPTranslator\Includes\Models\Languages;
use EasyWPTranslator\Includes\Options\Options;
use EasyWPTranslator\Modules\REST\Abstract_Controller;


/**
 * Settings REST controller.
 *
 *  
 */
class Settings extends Abstract_Controller {
	/**
	 * @var Options
	 */
	private $options;

	/**
	 * @var Languages
	 */
	private $languages;

	/**
	 * @var EWT_Model
	 */
	private $model;

	/**
	 * The list of post types to show in the form.
	 *
	 * @var string[]
	 */
	private $post_types;

	/**
	 * The list of post types to disable in the form.
	 *
	 * @var string[]
	 */
	private $disabled_post_types;

	/**
	 * The list of taxonomies to show in the form.
	 *
	 * @var string[]
	 */
	private $taxonomies;

	/**
	 * The list of taxonomies to disable in the form.
	 *
	 * @var string[]
	 */
	private $disabled_taxonomies;


	protected $namespace;
	protected $rest_base;
	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Model $model EasyWPTranslator's model.
	 */
	public function __construct( EWT_Model $model ) {
		$this->namespace = 'ewt/v1';
		$this->rest_base = 'settings';
		$this->model     = $model;
		$this->options   = $model->options;
		$this->languages = $model->languages;
	}

	
	/**
	 * Registers the routes for options.
	 *
	 *  
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema'      => array( $this, 'get_public_item_schema' ),
				'allow_batch' => array( 'v1' => true ),
			)
		);
		
		// Add specific endpoint for setup completion
		register_rest_route(
			$this->namespace,
			"/{$this->rest_base}/setup-complete",
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_setup_complete' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'complete' => array(
							'required' => true,
							'type'     => 'boolean',
						),
					),
				),
			)
		);
	}

	/**
	 * Updates setup completion status.
	 *
	 *  
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_setup_complete( $request ) {
		$complete = $request->get_param( 'complete' );
		
		$result = update_option( 'ewt_setup_complete', $complete );
		
		if ( $result ) {
			return rest_ensure_response( array(
				'success' => true,
				'ewt_setup_complete' => $complete,
				'message' => 'Setup completion status updated successfully'
			) );
		} else {
			return new WP_Error(
				'update_failed',
				'Failed to update setup completion status',
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Retrieves all options.
	 *
	 *  
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function get_item( $request ) {
		$public_post_types = get_post_types( array( 'public' => true, '_builtin' => false ) );
		/** This filter is documented in include/model.php */
		$this->post_types = array_unique( apply_filters( 'ewt_get_post_types', $public_post_types, true ) );

		/** This filter is documented in include/model.php */
		$programmatically_active_post_types = array_unique( apply_filters( 'ewt_get_post_types', array(), false ) );
		$this->disabled_post_types = array_intersect( $programmatically_active_post_types, $this->post_types );

		$public_taxonomies = get_taxonomies( array( 'public' => true, '_builtin' => false ) );
		$public_taxonomies = array_diff( $public_taxonomies, get_taxonomies( array( '_ewt' => true ) ) );
		/** This filter is documented in include/model.php */
		$this->taxonomies = array_unique( apply_filters( 'ewt_get_taxonomies', $public_taxonomies, true ) );

		/** This filter is documented in include/model.php */
		$programmatically_active_taxonomies = array_unique( apply_filters( 'ewt_get_taxonomies', array(), false ) );
		$this->disabled_taxonomies = array_intersect( $programmatically_active_taxonomies, $this->taxonomies );
		$response = $this->options->get_all();

		$available_post_types = array();
		foreach ( $this->post_types as $post_type ) {
			$pt = get_post_type_object( $post_type );
			if ( ! empty( $pt ) ) {
				array_push($available_post_types,["post_type_name"=>$pt->labels->name,"post_type_key"=>$pt->name]);
			}
		}

		$available_taxonomies = array();
		foreach ( $this->taxonomies as $taxonomy ) {
			$tx = get_taxonomy( $taxonomy );
			if ( ! empty( $tx ) ) {
				array_push($available_taxonomies,["taxonomy_name"=>$tx->labels->name,"taxonomy_key"=>$tx->name]);
			}
		}

		$disabled_post_types = array();
		foreach ( $this->disabled_post_types as $disabled_post_type ) {
			$pt = get_post_type_object( $disabled_post_type );
			if ( ! empty( $pt ) ) {
				array_push($disabled_post_types,["post_type_name"=>$pt->labels->name,"post_type_key"=>$pt->name]);
			}
		}
		$response['available_post_types'] = $available_post_types;
		$response['available_taxonomies'] = $available_taxonomies;
		$response['disabled_post_types'] = $disabled_post_types;
		
		return $response;
		// return $this->prepare_item_for_response( $this->options->get_all(), $request);
	}

	/**
	 * Updates option(s).
	 * This allows to update one or several options.
	 *
	 *  
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item( $request ) {
		$errors  = new WP_Error();
		$schema  = $this->options->get_schema();
		$options = array_intersect_key(
			$request->get_params(),
			rest_get_endpoint_args_for_schema( $schema, WP_REST_Server::EDITABLE ) // Remove fields with `readonly`.
		);

		// Validate domains before saving if force_lang is set to 3 (domains)
		$validation_errors = $this->validate_domains_before_save( $options );
		if ( $validation_errors->has_errors() ) {
			return $this->add_status_to_error( $validation_errors );
		}

		foreach ( $options as $option_name => $new_value ) {
			$previous_value = $this->options->get( $option_name );

			if ( 'default_lang' === $option_name ) {
				$result = $this->languages->update_default( $new_value );
			} elseif ( 'post_types' === $option_name ) {
				// Handle post types with programmatically active ones
				$processed_value = $this->process_post_types_for_save( $new_value );
				$result = $this->options->set( $option_name, $processed_value );
			} else {
				$result = $this->options->set( $option_name, $new_value );
			}

			if ( $result->has_errors() ) {
				$errors->merge_from( $result );
				continue;
			}

			if ( $this->options->get( $option_name ) === $previous_value ) {
				continue;
			}

			switch ( $option_name ) {
				case 'rewrite':
				case 'force_lang':
				case 'hide_default':
					delete_option( 'rewrite_rules' );
					break;
			
				case 'post_types':
				case 'taxonomies':
				case 'media_support':
					$this->trigger_mass_language_assignment( $option_name, $previous_value, $new_value );
					break;
			}
		}
		
		if ( $errors->has_errors() ) {
			return $this->add_status_to_error( $errors );
		}

		return $this->prepare_item_for_response( $this->options->get_all(), $request );
	}

	/**
	 * Validates domains before saving when force_lang is set to 3.
	 *
	 *  
	 *
	 * @param array $options The options being updated.
	 * @return WP_Error WP_Error object with validation errors, or empty if no errors.
	 */
	private function validate_domains_before_save( $options ) {
		$errors = new WP_Error();
		
		// Get current force_lang value
		$current_force_lang = $this->options->get( 'force_lang' );
		$new_force_lang = isset( $options['force_lang'] ) ? $options['force_lang'] : $current_force_lang;
		
		// Only validate if force_lang is being set to 3 (domains)
		if ( 3 !== (int) $new_force_lang ) {
			return $errors;
		}
		
		// Get domains being updated or current domains
		$domains = isset( $options['domains'] ) ? $options['domains'] : $this->options->get( 'domains' );
		
		if ( empty( $domains ) || ! is_array( $domains ) ) {
			$errors->add(
				'missing_domains',
				__( 'Domains are required when language is set from different domains.', 'easy-wp-translator' ),
				array( 'status' => 400 )
			);
			return $errors;
		}
		
		// Get all available languages
		$languages = $this->languages->get_list();
		$language_slugs = wp_list_pluck( $languages, 'slug' );
		
		// Validate each domain
		foreach ( $domains as $lang_slug => $domain_url ) {
			// Check if language exists
			if ( ! in_array( $lang_slug, $language_slugs, true ) ) {
				$errors->add(
					'invalid_language',
					// translators: %s is the language slug/code that was provided
					sprintf( __( 'Invalid language code: %s', 'easy-wp-translator' ), $lang_slug ),
					array( 'status' => 400 )
				);
				continue;
			}
			
			// Validate URL format
			if ( empty( $domain_url ) || ! is_string( $domain_url ) ) {
				$errors->add(
					'empty_domain',
					// translators: %s is the language slug/code that needs a domain URL
					sprintf( __( 'Domain URL is required for language: %s', 'easy-wp-translator' ), $lang_slug ),
					array( 'status' => 400 )
				);
				continue;
			}
			
			// Check if URL is valid
			$parsed_url = wp_parse_url( $domain_url );
			if ( false === $parsed_url || empty( $parsed_url['host'] ) ) {
				$errors->add(
					'invalid_domain_format',
					// translators: %1$s is the language slug/code, %2$s is the invalid domain URL provided
					sprintf( __( 'Invalid domain URL format for language %1$s: %2$s', 'easy-wp-translator' ), $lang_slug, $domain_url ),
					array( 'status' => 400 )
				);
				continue;
			}
			

			
		}
		
		// Check that all languages have domains
		foreach ( $language_slugs as $lang_slug ) {
			if ( empty( $domains[ $lang_slug ] ) ) {
				$errors->add(
					'missing_language_domain',
					// translators: %s is the language slug/code that is missing a domain URL
					sprintf( __( 'Domain URL is required for language: %s', 'easy-wp-translator' ), $lang_slug ),
					array( 'status' => 400 )
				);
			}
		}
		
		// Ping all URLs to make sure they are accessible - moved from Domains.php
		$failed_urls = array();
		foreach ( array_filter( $domains ) as $url ) {
			$test_url = add_query_arg( 'deactivate-easywptranslator', 1, $url );
			// Don't redefine vip_safe_wp_remote_get() as it has not the same signature as wp_remote_get().
			$response = function_exists( 'vip_safe_wp_remote_get' ) ? vip_safe_wp_remote_get( $test_url ) : wp_remote_get( $test_url );

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$failed_urls[] = $url;
			}
		}

		if ( ! empty( $failed_urls ) ) {
			// Blocking error - prevents save
			if ( 1 === count( $failed_urls ) ) {
				/* translators: %s is a URL. */
				$message = __( 'EasyWPTranslator was unable to access the %s URL. Please check that the URL is valid.', 'easy-wp-translator' );
			} else {
				/* translators: %s is a list of URLs. */
				$message = __( 'EasyWPTranslator was unable to access the %s URLs. Please check that the URLs are valid.', 'easy-wp-translator' );
			}
			$errors->add(
				'ewt_invalid_domains',
				sprintf( $message, wp_sprintf_l( '%l', $failed_urls ) ),
				array( 'status' => 400 )
			);
		}
		
		return $errors;
	}

	/**
	 * Checks if a given request has access to update the options.
	 *
	 *  
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the option, WP_Error object otherwise.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function update_item_permissions_check( $request ) {
		// Check user capabilities first
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit options.', 'easy-wp-translator' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		// Verify nonce for non-GET requests
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return true;
	}

	/**
	 * Prepares the option value for the REST response.
	 *
	 *  
	 *
	 * @param array           $item    Option values.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 *
	 * @phpstan-template T of array
	 * @phpstan-param array<non-falsy-string, mixed> $item
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields   = $this->get_fields_for_response( $request );
		$response = array();

		foreach ( $item as $option => $value ) {
			if ( rest_is_field_included( $option, $fields ) ) {
				$response[ $option ] = $value;
			}
		}
		
		/** @var WP_REST_Response */
		return rest_ensure_response( $response );
	}

	/**
	 * Process post types for saving, removing programmatically active ones.
	 *
	 *  
	 *
	 * @param array $post_types Post types from frontend.
	 * @return array Processed post types for saving.
	 */
	private function process_post_types_for_save( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return array();
		}
		
		// Get programmatically active post types
		$programmatically_active = array_unique( apply_filters( 'ewt_get_post_types', array(), false ) );
		
		// Remove programmatically active post types from the list to save
		// They should not be stored in options since they're handled by code
		return array_diff( $post_types, $programmatically_active );
	}

	/**
	 * Triggers mass language assignment for when they are updated.
	 *
	 *  
	 *
	 * @param string $type           The type of option being updated (post_types, taxonomies, media_support).
	 * @param array $previous_value Previous array.
	 * @param array $new_value      New array.
	 * @return void
	 */
	private function trigger_mass_language_assignment( $type, $previous_value, $new_value ) {
		// Ensure both are arrays where applicable
		$previous_value = is_array( $previous_value ) ? $previous_value : array();
		$new_value = is_array( $new_value ) ? $new_value : array();
	
		// Get the default language
		$default_lang = $this->languages->get_default();
		if ( ! $default_lang ) {
			return;
		}
	
		switch ( $type ) {
			case 'post_types':
				$newly_added = array_diff( $new_value, $previous_value );
				if ( ! empty( $newly_added ) ) {
					// Only assign language to posts that don't already have one
					$posts_without_lang = $this->model->get_posts_with_no_lang( $newly_added, 1000 );
					if ( ! empty( $posts_without_lang ) ) {
						$this->model->translatable_objects
							->get( 'post' )
							->set_language_in_mass( $posts_without_lang, $default_lang );
					}
				}
				break;

			case 'taxonomies':
				$newly_added = array_diff( $new_value, $previous_value );
				if ( ! empty( $newly_added ) ) {
					$terms_without_lang = $this->model->get_terms_with_no_lang( $newly_added, 1000 );
					if ( ! empty( $terms_without_lang ) ) {
						$this->model->translatable_objects
							->get( 'term' )
							->set_language_in_mass( $terms_without_lang, $default_lang );
					}
				}
				break;

			case 'media_support':
				if ( ! $previous_value && $new_value ) {
					// Only assign language to media that don't already have one
					$media_without_lang = $this->model->get_posts_with_no_lang( array( 'attachment' ), 1000 );
					if ( ! empty( $media_without_lang ) ) {
						$this->model->translatable_objects
							->get( 'post' )
							->set_language_in_mass( $media_without_lang, $default_lang );
					}
				}
				break;
		}
	}

	/**
	 * Retrieves the options' schema, conforming to JSON Schema.
	 *
	 *  
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(): array {
		return $this->add_additional_fields_schema( $this->options->get_schema() );
	}
}
