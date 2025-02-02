<?php
/*
Plugin Name: A(I)POD
Description: Analyses Astronomy Picture of the Day with AI explanations
Version: 1.5
Author: Doris Vickers
Author URI: https://ucrisportal.univie.ac.at/de/persons/doris-magdalena-vickers
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-vertex-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-content-analyzer.php';

class APOD_AI_Analysis {
    private $nasa_api_key;
    private $vertex_api;
    private $content_analyzer;
    private $days_to_fetch = 100;
    private $days_to_display = 30;
    
    public function __construct() {
        $this->nasa_api_key = get_option('apod_ai_nasa_key', 'DEMO_KEY');
        $this->vertex_api = new APOD_Vertex_API();
        $this->content_analyzer = new APOD_Content_Analyzer();
        $this->init();
    }

    private function init() {
        // Setup WordPress hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('apod_ai_analysis', array($this, 'render_shortcode'));
        add_action('wp_ajax_get_apod_data', array($this, 'get_apod_data'));
        add_action('wp_ajax_nopriv_get_apod_data', array($this, 'get_apod_data'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add cache cleanup
        add_action('apod_cleanup_cache', array($this, 'cleanup_old_cache'));
        
        // Schedule cache cleanup if not already scheduled
        if (!wp_next_scheduled('apod_cleanup_cache')) {
            wp_schedule_event(time(), 'daily', 'apod_cleanup_cache');
        }
    }

    public function enqueue_scripts() {
        // Enqueue main styles
        wp_enqueue_style(
            'apod-analysis',
            plugin_dir_url(__FILE__) . 'assets/css/apod-analysis.css',
            array(),
            '1.5'
        );

        // Enqueue React and our script
        wp_enqueue_script('wp-element');
        wp_enqueue_script(
            'apod-analysis',
            plugin_dir_url(__FILE__) . 'assets/js/apod-analysis.js',
            array('jquery', 'wp-element'),
            '1.5',
            true
        );

        // Localize script
        wp_localize_script('apod-analysis', 'apodAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apod_nonce'),
            'cache_time' => get_option('apod_cache_duration', 24)
        ));
    }

    public function add_admin_menu() {
        add_options_page(
            'APOD AI Settings',
            'APOD AI',
            'manage_options',
            'apod-ai-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('apod_ai_options', 'apod_ai_nasa_key');
        register_setting('apod_ai_options', 'apod_ai_vertex_key');
        register_setting('apod_ai_options', 'apod_ai_vertex_project');
        register_setting('apod_ai_options', 'apod_ai_vertex_endpoint');
        register_setting('apod_ai_options', 'apod_cache_duration');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h2>APOD AI Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('apod_ai_options');
                do_settings_sections('apod-ai-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">NASA API Key</th>
                        <td>
                            <input type="text" name="apod_ai_nasa_key" 
                                   value="<?php echo esc_attr(get_option('apod_ai_nasa_key')); ?>" class="regular-text">
                            <p class="description">Get your API key from <a href="https://api.nasa.gov/" target="_blank">api.nasa.gov</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vertex AI API Key</th>
                        <td>
                            <input type="text" name="apod_ai_vertex_key" 
                                   value="<?php echo esc_attr(get_option('apod_ai_vertex_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vertex AI Project ID</th>
                        <td>
                            <input type="text" name="apod_ai_vertex_project" 
                                   value="<?php echo esc_attr(get_option('apod_ai_vertex_project')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vertex AI Endpoint ID</th>
                        <td>
                            <input type="text" name="apod_ai_vertex_endpoint" 
                                   value="<?php echo esc_attr(get_option('apod_ai_vertex_endpoint')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cache Duration (hours)</th>
                        <td>
                            <input type="number" name="apod_cache_duration" 
                                   value="<?php echo esc_attr(get_option('apod_cache_duration', 24)); ?>" 
                                   min="1" max="72" class="small-text">
                            <p class="description">How long to cache API responses (1-72 hours)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_shortcode($atts) {
        ob_start();
        ?>
        <div class="apod-analysis-container">
            <div class="apod-loading">Loading APOD analysis...</div>
            <div class="apod-error" style="display: none;"></div>
            <div class="apod-content" style="display: none;">
                <div class="apod-stats"></div>
                <div class="apod-results-grid"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_cache_key($date_range) {
        return 'apod_data_' . md5(serialize($date_range));
    }

    private function get_cached_data($cache_key) {
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            $cached_data = json_decode($cached, true);
            if ($cached_data && isset($cached_data['timestamp'])) {
                $cache_age = time() - $cached_data['timestamp'];
                $cache_duration = get_option('apod_cache_duration', 24) * HOUR_IN_SECONDS;
                if ($cache_age < $cache_duration) {
                    return $cached_data['data'];
                }
            }
        }
        return null;
    }

    private function set_cached_data($cache_key, $data) {
        $cache_data = array(
            'timestamp' => time(),
            'data' => $data
        );
        set_transient($cache_key, json_encode($cache_data), 72 * HOUR_IN_SECONDS);
    }

    public function cleanup_old_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_apod_data_%' 
             AND option_value LIKE '%\"timestamp\"%' 
             AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(option_value, '\"timestamp\":', -1), ',', 1) AS UNSIGNED) < " . 
             (time() - (72 * HOUR_IN_SECONDS))
        );
    }

    private function fetch_images($start_date, $end_date) {
        $results = array();
        $nasa_key = !empty($this->nasa_api_key) ? $this->nasa_api_key : 'DEMO_KEY';

        for ($date = clone $start_date; $date <= $end_date; $date->modify('+1 day')) {
            $current_date = $date->format('Y-m-d');
            $url = "https://api.nasa.gov/planetary/apod?api_key={$nasa_key}&date={$current_date}";
            
            $response = wp_remote_get($url);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data && isset($data['media_type']) && $data['media_type'] === 'image') {
                    // Get AI analysis
                    $ai_analysis = $this->vertex_api->analyze_apod($data['title'], $data['explanation']);
                    
                    // Get content analysis
                    $content_analysis = $this->content_analyzer->analyze_content($data['title'], $data['explanation']);
                    
                    $results[] = array(
                        'date' => $current_date,
                        'title' => $data['title'],
                        'imageUrl' => $data['url'],
                        'explanation' => $data['explanation'],
                        'aiAnalysis' => $ai_analysis,
                        'contentAnalysis' => $content_analysis
                    );
                }
            }
            usleep(100000); // 100ms delay
        }

        // Add similar images after collecting all results
        foreach ($results as &$result) {
            $similar = $this->content_analyzer->find_similar_images($result, $results);
            $result['similarImages'] = $similar;
        }

        return $results;
    }

    public function get_apod_data() {
        check_ajax_referer('apod_nonce', 'nonce');
        
        try {
            $end_date = new DateTime();
            $start_date = clone $end_date;
            $start_date->modify('-' . $this->days_to_fetch . ' days');
            
            $date_range = array(
                'start' => $start_date->format('Y-m-d'),
                'end' => $end_date->format('Y-m-d')
            );

            $cache_key = $this->get_cache_key($date_range);
            $cached_data = $this->get_cached_data($cache_key);

            if ($cached_data !== null) {
                wp_send_json_success($cached_data);
                return;
            }

            $all_results = $this->fetch_images($start_date, $end_date);
            
            $display_results = array_filter($all_results, function($result) {
                $date = new DateTime($result['date']);
                $cutoff_date = new DateTime();
                $cutoff_date->modify('-' . $this->days_to_display . ' days');
                return $date >= $cutoff_date;
            });

            $response_data = array(
                'dateRange' => 'Last ' . $this->days_to_display . ' Days',
                'totalImages' => count($display_results),
                'lastUpdate' => current_time('Y-m-d H:i:s'),
                'results' => array_values($display_results)
            );

            $this->set_cached_data($cache_key, $response_data);
            wp_send_json_success($response_data);

        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

new APOD_AI_Analysis();