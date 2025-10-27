<?php
/**
 * Language Switcher EasyWPTranslator Elementor Widget
 *
 * @package LanguageSwitcherEasyWPTranslatorElementorWidget
 *  
 */

namespace EasyWPTranslator\Integrations\elementor;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class EWT_Widget
 *
 * Main widget class for the Language Switcher EasyWPTranslator Elementor widget.
 *
 *  
 */
class EWT_Widget extends Widget_Base
{

    /**
     * Constructor for the widget.
     *
     * @param array $data Widget data.
     * @param array $args Widget arguments.
     */
    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);
        wp_register_style(
            'ewt-style',
            EASY_WP_TRANSLATOR_URL . '/admin/assets/css/build/language-switcher-style.css',
            [],
            EASY_WP_TRANSLATOR_VERSION
        );

        add_action('elementor/editor/after_enqueue_scripts', [$this, 'ewt_language_switcher_icon_css']);
    }

    public function ewt_language_switcher_icon_css()
    {
        wp_enqueue_style('ewt-style');

        $inline_css = "
        .ewt-widget-icon {
            display: inline-block;
            width: 25px;
            height: 25px;
            background-image: url('" . esc_url(EASY_WP_TRANSLATOR_URL . 'assets/logo/lang_switcher.svg') . "');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
    ";

        wp_add_inline_style('ewt-style', $inline_css);
    }

    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'ewt_widget';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('Language Switcher', 'easy-wp-translator');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'ewt-widget-icon';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['basic'];
    }

    /**
     * Get widget style dependencies.
     *
     * @return array Widget style dependencies.
     */
    public function get_style_depends()
    {
        return ['ewt-style'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Language Switcher', 'easy-wp-translator'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'ewt_language_switcher_type',
            [
                'label'   => __('Language Switcher Type', 'easy-wp-translator'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'dropdown'   => __('Dropdown', 'easy-wp-translator'),
                    'vertical'   => __('Vertical', 'easy-wp-translator'),
                    'horizontal' => __('Horizontal', 'easy-wp-translator'),
                ],
                'default' => 'dropdown',
            ]
        );

        $this->add_control(
            'ewt_language_switcher_show_flags',
            [
                'label'   => __('Show Flags', 'easy-wp-translator'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'ewt_language_switcher_show_names',
            [
                'label'   => __('Show Language Names', 'easy-wp-translator'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'ewt_languages_switcher_show_code',
            [
                'label'   => __('Show Language Codes', 'easy-wp-translator'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'ewt_language_switcher_hide_current_language',
            [
                'label'   => __('Hide Current Language', 'easy-wp-translator'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->add_control(
            'ewt_language_hide_untranslated_languages',
            [
                'label'   => __('Hide Untranslated Languages', 'easy-wp-translator'),
                'type'    => Controls_Manager::SWITCHER,
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Language Switcher Style', 'easy-wp-translator'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'ewt_language_switcher_alignment',
            [
                'label'     => __('Switcher Alignment', 'easy-wp-translator'),
                'type'      => Controls_Manager::CHOOSE,
                'options'   => [
                    'left'   => [
                        'title' => esc_html__('Left', 'easy-wp-translator'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'easy-wp-translator'),
                        'icon'  => 'eicon-h-align-center',
                    ],
                    'right'  => [
                        'title' => esc_html__('Right', 'easy-wp-translator'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'default'   => 'left',
                'condition' => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ewt-main-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_flag_ratio',
            [
                'label'        => __('Flag Ratio', 'easy-wp-translator'),
                'type'         => Controls_Manager::SELECT,
                'options'      => [
                    '11' => __('1/1', 'easy-wp-translator'),
                    '43' => __('4/3', 'easy-wp-translator'),
                ],
                'prefix_class' => 'ewt-switcher--aspect-ratio-',
                'default'      => '43',
                'selectors'    => [
                    '{{WRAPPER}} .ewt-lang-image' => '--ewt-flag-ratio: {{VALUE}};',
                ],
                'condition'    => [
                    'ewt_language_switcher_show_flags' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_flag_width',
            [
                'label'      => __('Flag Width', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'default'    => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors'  => [
                    '{{WRAPPER}}.ewt-switcher--aspect-ratio-11 .ewt-lang-image img' => 'height: {{SIZE}}{{UNIT}} !important; width: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}}.ewt-switcher--aspect-ratio-43 .ewt-lang-image img' => 'width: {{SIZE}}{{UNIT}}!important; height: calc({{SIZE}}{{UNIT}} * 0.75) !important;',
                ],
                'condition'  => [
                    'ewt_language_switcher_show_flags' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_flag_radius',
            [
                'label'      => __('Flag Radius', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => '%',
                    'size' => 0,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-lang-image img' => '--ewt-flag-radius: {{SIZE}}{{UNIT}};',
                ],
                'condition'  => [
                    'ewt_language_switcher_show_flags' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_margin',
            [
                'label'      => esc_html__('Margin', 'easy-wp-translator'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default'    => [
                    'top'    => 0,
                    'right'  => 0,
                    'bottom' => 0,
                    'left'   => 0,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown'                     => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a'   => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_padding',
            [
                'label'      => __('Padding', 'easy-wp-translator'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default'    => [
                    'top'    => 10,
                    'right'  => 10,
                    'bottom' => 10,
                    'left'   => 10,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown'                     => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-lang-item'     => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'ewt_language_switcher_border',
                'label'    => __('Border', 'easy-wp-translator'),
                'selector' => '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal li a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical li a',
            ]
        );

        $this->add_control(
            'ewt_language_switcher_border_radius',
            [
                'label'      => __('Border Radius', 'easy-wp-translator'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em', 'rem'],
                'default'    => [
                    'top'    => 0,
                    'right'  => 0,
                    'bottom' => 0,
                    'left'   => 0,
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown'                     => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-language-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal li a'              => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical li a'                => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],

            ]
        );
        $this->start_controls_tabs('ewt_language_switcher_style_tabs');
        $this->start_controls_tab(
            'ewt_language_switcher_style_tab_normal',
            [
                'label' => __('Normal', 'easy-wp-translator'),
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'ewt_language_switcher_typography',
                'label'    => __('Typography', 'easy-wp-translator'),
                'selector' => '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-active-language a div:not(.ewt-lang-image), {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-lang-item a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a',
            ]
        );
        $this->add_control(
            'ewt_language_switcher_background_color',
            [
                'label'     => __('Switcher Background Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown'                     => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown ul li'               => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a' => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a'   => '--ewt-normal-bg-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_text_color',
            [
                'label'     => __('Switcher Text Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-active-language,{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-lang-item a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a' => '--ewt-normal-text-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab(
            'ewt_language_switcher_style_tab_hover',
            [
                'label' => __('Hover', 'easy-wp-translator'),
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'ewt_language_switcher_typography_hover',
                'label'    => __('Typography', 'easy-wp-translator'),
                'selector' => '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-active-language:hover,{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-lang-item a:hover, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a:hover, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a:hover',
            ]
        );
        $this->add_control(
            'ewt_language_switcher_background_color_hover',
            [
                'label'     => __('Switcher Background Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown:hover'                     => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown ul li:hover'               => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a:hover' => '--ewt-normal-bg-color: {{VALUE}};',
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a:hover'   => '--ewt-normal-bg-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_text_color_hover',
            [
                'label'     => __('Switcher Text Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown:hover .ewt-active-language,{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown .ewt-lang-item:hover a, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.horizontal .ewt-lang-item a:hover, {{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.vertical .ewt-lang-item a:hover' => '--ewt-normal-text-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        $this->start_controls_section(
            'section_dropdown_style',
            [
                'label'     => __('Dropdown Style', 'easy-wp-translator'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_dropown_direction',
            [
                'label'        => __('Dropdown Direction', 'easy-wp-translator'),
                'type'         => Controls_Manager::SELECT,
                'options'      => [
                    'up'   => __('Up', 'easy-wp-translator'),
                    'down' => __('Down', 'easy-wp-translator'),
                ],
                'default'      => 'down',
                'condition'    => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
                'prefix_class' => 'ewt-dropdown-direction-',
            ]
        );

        $this->add_control(
            'ewt_language_switcher_icon',
            [
                'label'                  => __('Switcher Icon', 'easy-wp-translator'),
                'type'                   => Controls_Manager::ICONS,
                'default'                => [
                    'value'   => 'fas fa-caret-down',
                    'library' => 'fa-solid',
                ],
                'include'                => ['fa-solid', 'fa-regular', 'fa-brands'],
                'exclude_inline_options' => 'svg',
                'label_block'            => false,
                'skin'                   => 'inline',
                'condition'              => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_icon_size',
            [
                'label'      => __('Icon Size', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                    '%'  => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'condition'  => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-dropdown-icon' => 'font-size: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_icon_color',
            [
                'label'     => __('Icon Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
                'selectors' => [
                    '{{WRAPPER}} .ewt-dropdown-icon' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_icon_spacing',
            [
                'label'      => __('Icon Spacing', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 100,
                        'step' => 1,
                    ],
                ],
                'condition'  => [
                    'ewt_language_switcher_type' => 'dropdown',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-dropdown-icon' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_dropdwon_spacing',
            [
                'label'      => __('Dropdown Spacing', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'default'    => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors'  => [
                    '{{WRAPPER}}.ewt-dropdown-direction-down .ewt-wrapper.dropdown ul' => 'margin-top: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.ewt-dropdown-direction-up .ewt-wrapper.dropdown ul'   => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'           => 'ewt_language_switcher_dropdown_list_border',
                'label'          => __('Dropdown List Border', 'easy-wp-translator'),
                'separator'      => 'before',
                'selector'       => '{{WRAPPER}} .ewt-main-wrapper .ewt-wrapper.dropdown ul',
                'fields_options' => [
                    'border' => [
                        'label' => __('Dropdown List Border', 'easy-wp-translator'),
                    ],
                    'width'  => [
                        'label' => __('Border Width', 'easy-wp-translator'),
                    ],
                    'color'  => [
                        'label' => __('Border Color', 'easy-wp-translator'),
                    ],
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_dropdown_language_item_separator',
            [
                'label'      => __('Language Item Separator', 'easy-wp-translator'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => [
                    'px' => [
                        'min'  => 0,
                        'max'  => 50,
                        'step' => 1,
                    ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .ewt-wrapper.dropdown ul.ewt-language-list li.ewt-lang-item:not(:last-child)' => 'border-bottom: {{SIZE}}{{UNIT}} solid;',
                ],
            ]
        );

        $this->add_control(
            'ewt_language_switcher_dropdown_language_item_separator_color',
            [
                'label'     => __('Separator Color', 'easy-wp-translator'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .ewt-wrapper.dropdown ul.ewt-language-list li.ewt-lang-item:not(:last-child)' => 'border-bottom-color: {{VALUE}};',
                ],
            ]
        );
        $this->end_controls_section();
    }

    /**
     * Localize EasyWPTranslator data for the widget.
     *
     * @param array $data Data to be localized.
     * @return array Localized data.
     */
    public function ewt_localize_ewt_data($data)
    {
        try {
                // Try different approach - get languages without show_flags first
                $languages_raw = ewt_the_languages(['raw' => 1, 'show_flags' => 0]);
                if (empty($languages_raw)) {
                    return $data; // If no languages, exit early
                }
                $lang_curr = strtolower(ewt_current_language());
                if (empty($lang_curr)) {
                    $lang_curr = strtolower(ewt_default_language());
                }

                
                $languages = array_map(
                    function ($language) {
                        // Get flag HTML directly from language object if available
                        $flag_html = '';
                        if (function_exists('EWT') && !empty(EWT()->model)) {
                            $lang_objects = EWT()->model->get_languages_list();
                            foreach ($lang_objects as $lang_obj) {
                                if ($lang_obj->slug === $language['slug']) {
                                    $flag_html = $lang_obj->get_display_flag();
                                    break;
                                }
                            }
                        }
                        
                        // Fallback to original flag if available
                        if (empty($flag_html) && !empty($language['flag'])) {
                            $flag_html = $language['flag'];
                        }
                        

                        
                        return $language['name'] = [
                            'slug'           => esc_html($language['slug']),
                            'name'           => esc_html($language['name']),
                            'no_translation' => esc_html($language['no_translation']),
                            'url'            => esc_url($language['url']),
                            'flag'           => $flag_html, // Use our generated flag HTML
                        ];
                    },
                    $languages_raw
                );
                $custom_data = [
                    'ewtLanguageData' => $languages,
                    'ewtCurrentLang'  => esc_html($lang_curr),
                    'ewtPluginUrl'    => esc_url(EASY_WP_TRANSLATOR_URL),
                ];
                $custom_data_json = $custom_data;
                $data['ewtGlobalObj'] = $custom_data_json;
        } catch (Exception $e) {
            // Handle exception if needed
        }
        return $data;
    }
    /**
     * Render the widget output on the frontend.
     */
    protected function render()
    {
        $settings = $this->get_active_settings();

        // Get the localized data
        $data      = $this->ewt_localize_ewt_data([]);
        $ewt_data = isset($data['ewtGlobalObj']) ? $data['ewtGlobalObj'] : [];
        if (empty($ewt_data)) {
            return;
        }
        if ($settings['ewt_language_switcher_show_flags'] !== 'yes' && $settings['ewt_language_switcher_show_names'] !== 'yes' && $settings['ewt_languages_switcher_show_code'] !== 'yes') {
            return;
        }
        $switcher_html = '';
        $switcher_html .= '<div class="ewt-main-wrapper">';
        if ($settings['ewt_language_switcher_type'] == 'dropdown') {
            $switcher_html .= '<div class="ewt-wrapper dropdown">';
            $switcher_html .= $this->ewt_render_dropdown_switcher($settings, $ewt_data);
            $switcher_html .= '</div>';
        } else {
            $switcher_html .= '<div class="ewt-wrapper ' . esc_attr($settings['ewt_language_switcher_type']) . '">';
            $switcher_html .= $this->ewt_render_switcher($settings, $ewt_data);
            $switcher_html .= '</div>';
        }
        $switcher_html .= '</div>';
        echo wp_kses( 
            $switcher_html, 
            array( 
                'div' => array( 'class' => true, 'id' => true ),
                'ul' => array( 'class' => true ),
                'li' => array( 'class' => true ),
                'a' => array( 'href' => true, 'class' => true, 'lang' => true, 'hreflang' => true, 'aria-current' => true ),
                'span' => array( 'class' => true ),
                'img' => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true, 'style' => true, 'decoding' => true, 'title' => true ),
                'i' => array( 'class' => true )
            ), 
            array_merge( wp_allowed_protocols(), array( 'data' ) ) 
        );
    }

    /**
     * Render dropdown switcher.
     *
     * @param array $settings Widget settings.
     * @param array $ewt_data Language data.
     * @return string HTML output.
     */
    public function ewt_render_dropdown_switcher($settings, $ewt_data)
    {
        $languages    = $ewt_data['ewtLanguageData'];
        $current_lang = $ewt_data['ewtCurrentLang'];

        // If current language should be shown, use it as active language
        if ($settings['ewt_language_switcher_hide_current_language'] !== 'yes') {
            $active_language = isset($languages[$current_lang]) ? $languages[$current_lang] : null;
        } else {
            // Find first available language that's not the current language
            $active_language = null;
            foreach ($languages as $lang) {
                if ($current_lang !== $lang['slug'] &&
                    ! ($lang['no_translation'] && $settings['ewt_language_hide_untranslated_languages'] === 'yes')) {
                    $active_language = $lang;
                    break;
                }
            }
        }

        // If no language found, return empty
        if (! $active_language) {
            return '';
        }

        $active_html    = self::ewt_get_active_language_html($active_language, $settings);
        $languages_html = '';

        foreach ($languages as $lang) {
            
            // Skip if it's the current language (when hidden), active language, or untranslated language
            if (($current_lang === $lang['slug'] && $settings['ewt_language_switcher_hide_current_language'] === 'yes') ||
                $active_language['slug'] === $lang['slug'] ||
                ($lang['no_translation'] && $settings['ewt_language_hide_untranslated_languages'] === 'yes')) {
                continue;
            }

            $languages_html .= '<li class="ewt-lang-item">';
            $languages_html .= '<a href="' . esc_url($lang['url']) . '">';
            if (! empty($settings['ewt_language_switcher_show_flags']) && $settings['ewt_language_switcher_show_flags'] === 'yes') {
                $languages_html .= '<div class="ewt-lang-image">' . $lang['flag'] . '</div>';
            }
            if (! empty($settings['ewt_language_switcher_show_names']) && $settings['ewt_language_switcher_show_names'] === 'yes') {
                $languages_html .= '<div class="ewt-lang-name">' . esc_html($lang['name']) . '</div>';
            }
            if (! empty($settings['ewt_languages_switcher_show_code']) && $settings['ewt_languages_switcher_show_code'] === 'yes') {
                $languages_html .= '<div class="ewt-lang-code">' . esc_html($lang['slug']) . '</div>';
            }
            $languages_html .= '</a></li>';
        }

        return $active_html . '<ul class="ewt-language-list">' . $languages_html . '</ul>';
    }

    /**
     * Get active language HTML.
     *
     * @param array  $language Language data.
     * @param array  $settings Widget settings.
     * @return string HTML output.
     */
    public static function ewt_get_active_language_html($language, $settings)
    {
        $html = '<span class="ewt-active-language">';
        $html .= '<a href="' . esc_url($language['url']) . '">';
        if (! empty($settings['ewt_language_switcher_show_flags']) && $settings['ewt_language_switcher_show_flags'] === 'yes') {
            $html .= '<div class="ewt-lang-image">' . $language['flag'] . '</div>';
        }
        if (! empty($settings['ewt_language_switcher_show_names']) && $settings['ewt_language_switcher_show_names'] === 'yes') {
            $html .= '<div class="ewt-lang-name">' . esc_html($language['name']) . '</div>';
        }
        if (! empty($settings['ewt_languages_switcher_show_code']) && $settings['ewt_languages_switcher_show_code'] === 'yes') {
            $html .= '<div class="ewt-lang-code">' . esc_html($language['slug']) . '</div>';
        }
        if (! empty($settings['ewt_language_switcher_icon'])) {
            $html .= '<i class="ewt-dropdown-icon ' . esc_attr($settings['ewt_language_switcher_icon']['value']) . '"></i>';
        }
        $html .= '</a></span>';
        return $html;
    }

    /**
     * Render Vertcal and Horizontal switcher.
     *
     * @param array $settings Widget settings.
     * @param array $ewt_data Language data.
     * @return string HTML output.
     */
    public static function ewt_render_switcher($settings, $ewt_data)
    {
        $html         = '';
        $languages    = $ewt_data['ewtLanguageData'];
        $current_lang = $ewt_data['ewtCurrentLang'];
        foreach ($languages as $lang) {
            if (($current_lang === $lang['slug'] && $settings['ewt_language_switcher_hide_current_language'] === 'yes') ||
                ($lang['no_translation'] && $settings['ewt_language_hide_untranslated_languages'] === 'yes')) {
                continue;
            }

            $anchor_open  = '<a href="' . esc_url($lang['url']) . '">';
            $anchor_close = '</a>';

            $html .= '<li class="ewt-lang-item">';
            $html .= $anchor_open;
            if (! empty($settings['ewt_language_switcher_show_flags']) && $settings['ewt_language_switcher_show_flags'] === 'yes') {
                $html .= '<div class="ewt-lang-image">' . $lang['flag'] . '</div>';
            }
            if (! empty($settings['ewt_language_switcher_show_names']) && $settings['ewt_language_switcher_show_names'] === 'yes') {
                $html .= '<div class="ewt-lang-name">' . esc_html($lang['name']) . '</div>';
            }
            if (! empty($settings['ewt_languages_switcher_show_code']) && $settings['ewt_languages_switcher_show_code'] === 'yes') {
                $html .= '<div class="ewt-lang-code">' . esc_html($lang['slug']) . '</div>';
            }
            $html .= $anchor_close;
            $html .= '</li>';
        }
        return $html;
    }
}
