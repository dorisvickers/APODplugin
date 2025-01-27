<?php
/*
Plugin Name: A(I)POD
Plugin URI: https://ucrisportal.univie.ac.at/
Description: Analyses Astronomy Picture of the Day with AI explanations
Version: 1.5
Author: Doris Vickers
Author URI: https://ucrisportal.univie.ac.at/de/persons/doris-magdalena-vickers
*/

if (!defined('ABSPATH')) exit;

class APOD_AI_Analysis {
    private $nasa_api_key;
    private $vertex_api_key;
    private $days_to_fetch = 100;
    private $vertex_endpoint = 'https://us-central1-aiplatform.googleapis.com/v1/projects/{PROJECT_ID}/locations/us-central1/endpoints/{ENDPOINT_ID}:predict';
    
    private $categories = array(
        'deep_space' => array(
            'name' => 'Deep Space Objects',
            'description' => 'Galaxies, nebulae, and distant cosmic structures'
        ),
        'solar_system' => array(
            'name' => 'Solar System Objects',
            'description' => 'Planets, moons, and other objects in our solar system'
        ),
        'star' => array(
            'name' => 'Stellar Objects',
            'description' => 'Individual stars and stellar phenomena'
        ),
        'earth_sky' => array(
            'name' => 'Earth and Sky Phenomena',
            'description' => 'Atmospheric and near-Earth celestial events'
        ),
        'technology' => array(
            'name' => 'Astronomical Technology',
            'description' => 'Tools and instruments for space exploration'
        ),
        'other' => array(
            'name' => 'Other Astronomical Objects',
            'description' => 'Other space-related imagery'
        )
    );

    public function __construct() {
        $this->nasa_api_key = get_option('apod_ai_nasa_key', 'DEMO_KEY');
        $this->vertex_api_key = get_option('apod_ai_vertex_key');
        $this->init();
    }

    private function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('apod_ai_analysis', array($this, 'render_shortcode'));
        add_action('wp_ajax_get_apod_data', array($this, 'get_apod_data'));
        add_action('wp_ajax_nopriv_get_apod_data', array($this, 'get_apod_data'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
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
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vertex AI API Key</th>
                        <td>
                            <input type="text" name="apod_ai_vertex_key" 
                                   value="<?php echo esc_attr(get_option('apod_ai_vertex_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'apod-analysis',
            plugin_dir_url(__FILE__) . 'assets/css/apod-analysis.css',
            array(),
            '1.4'
        );

        wp_enqueue_script(
            'apod-analysis',
            plugin_dir_url(__FILE__) . 'assets/js/apod-analysis.js',
            array('jquery'),
            '1.4',
            true
        );

        wp_localize_script('apod-analysis', 'apodAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apod_nonce')
        ));
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

    private function generate_basic_explanation($title, $explanation) {
        $shorter_explanation = $this->summarize_text($explanation, 150);
        $type = $this->identify_object_type($title, $explanation);
        $interesting_fact = $this->find_interesting_fact($explanation);
        
        return "This image shows {$title}, which is {$type}. {$shorter_explanation} {$interesting_fact}";
    }

    private function generate_detailed_explanation($title, $explanation) {
        $key_terms = $this->extract_astronomical_terms($explanation);
        $context = $this->get_detailed_context($title, $explanation);
        $features = $this->extract_features($explanation);
        
        return "This fascinating image features {$title}. {$context} " . 
               ($features ? "Notable features include: {$features}. " : "") .
               ($key_terms ? "Key astronomical elements observed: {$key_terms}." : "");
    }

    private function generate_technical_explanation($title, $explanation) {
        $technical_terms = $this->extract_technical_terms($explanation);
        $measurements = $this->extract_measurements($explanation);
        $scientific_context = $this->get_scientific_context($explanation);
        
        $explanation = "Technical analysis of {$title}: ";
        if ($technical_terms) {
            $explanation .= "Key technical aspects: {$technical_terms}. ";
        }
        if ($measurements) {
            $explanation .= "Observed measurements: {$measurements}. ";
        }
        if ($scientific_context) {
            $explanation .= $scientific_context;
        }
        
        return $explanation;
    }

    private function identify_object_type($title, $explanation) {
        $text = strtolower($title . ' ' . $explanation);
        
        if (strpos($text, 'galaxy') !== false) {
            return "a vast collection of stars, gas, and dust bound together by gravity";
        } elseif (strpos($text, 'nebula') !== false) {
            return "a magnificent cloud of gas and dust in space";
        } elseif (strpos($text, 'cluster') !== false) {
            return "a group of stars bound together by gravity";
        } elseif (strpos($text, 'planet') !== false) {
            return "a world in our solar system";
        } elseif (strpos($text, 'moon') !== false) {
            return "a natural satellite orbiting a planet";
        } elseif (strpos($text, 'star') !== false) {
            return "a luminous sphere of plasma held together by its own gravity";
        } elseif (strpos($text, 'comet') !== false) {
            return "an icy body that releases gas and dust as it approaches the Sun";
        }
        
        return "an intriguing astronomical object";
    }

    private function find_interesting_fact($explanation) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $explanation, -1, PREG_SPLIT_NO_EMPTY);
        $interesting_keywords = array(
            'discover', 'first', 'largest', 'brightest', 'unique', 
            'rare', 'mysterious', 'unusual', 'remarkable', 'spectacular'
        );
        
        foreach ($sentences as $sentence) {
            foreach ($interesting_keywords as $keyword) {
                if (stripos($sentence, $keyword) !== false) {
                    return $sentence;
                }
            }
        }
        return "";
    }

    private function get_detailed_context($title, $explanation) {
        $text = strtolower($title . ' ' . $explanation);
        
        if (strpos($text, 'galaxy') !== false) {
            return "Galaxies are enormous cosmic cities containing billions of stars, along with vast amounts of gas, dust, and dark matter. They are the fundamental building blocks of our universe.";
        } elseif (strpos($text, 'nebula') !== false) {
            return "Nebulae are vast cosmic clouds where stars are either being born or have died. They are essentially the nurseries and graveyards of the stellar world.";
        } elseif (strpos($text, 'planet') !== false) {
            return "Planets are worlds that orbit stars, each with their own unique characteristics and potential for scientific discovery.";
        } elseif (strpos($text, 'star') !== false) {
            return "Stars are massive balls of gas that generate energy through nuclear fusion in their cores, lighting up the cosmos and providing the building blocks for life.";
        }
        
        return "This celestial object represents an important piece of our cosmic puzzle.";
    }

    private function extract_features($explanation) {
        $features = array();
        $patterns = array(
            '/(?:bright|dim|luminous|dark)\s+(?:region|area|spot|feature|structure)/i',
            '/(?:spiral|elliptical|irregular)\s+(?:arm|structure|shape|pattern)/i',
            '/(?:gas|dust|cloud|ring|belt|storm)\s+(?:formation|structure|pattern)/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $explanation, $matches)) {
                $features = array_merge($features, $matches[0]);
            }
        }
        return implode(', ', array_unique($features));
    }

    private function get_scientific_context($explanation) {
        $scientific_patterns = array(
            '/(?:research|study|observation|analysis) (?:shows|reveals|indicates|suggests) [^.!?]+[.!?]/i',
            '/(?:scientists|astronomers|researchers) (?:believe|think|theorize) [^.!?]+[.!?]/i',
            '/(?:discovery|finding|observation) (?:helps|allows|enables) [^.!?]+[.!?]/i'
        );
        
        foreach ($scientific_patterns as $pattern) {
            if (preg_match($pattern, $explanation, $match)) {
                return $match[0];
            }
        }
        return "";
    }

    private function summarize_text($text, $length) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $summary = '';
        foreach ($sentences as $sentence) {
            if (strlen($summary . $sentence) > $length) {
                break;
            }
            $summary .= $sentence . ' ';
        }
        return trim($summary);
    }

    private function extract_astronomical_terms($text) {
        $terms = array();
        $patterns = array(
            '/(?:galaxy|galaxies|nebula|nebulae|cluster|star|constellation|planet|moon)/i',
            '/(?:supernova|nova|quasar|pulsar|black hole)/i',
            '/(?:eclipse|transit|conjunction|opposition)/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $terms = array_merge($terms, $matches[0]);
            }
        }
        return implode(', ', array_unique($terms));
    }

    private function extract_technical_terms($text) {
        $terms = array();
        $patterns = array(
            '/(?:\d+(?:\.\d+)?\s*(?:light[- ]years?|parsecs?|AU|km))/i',
            '/(?:magnitude|luminosity|mass|temperature|radius)/i',
            '/(?:spectral[- ]type|red[- ]shift|wavelength)/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $terms = array_merge($terms, $matches[0]);
            }
        }
        return implode(', ', array_unique($terms));
    }

    private function extract_measurements($text) {
        $measurements = array();
        $patterns = array(
            '/\d+(?:\.\d+)?\s*(?:light[- ]years?|parsecs?|AU|km|meters?)/i',
            '/\d+(?:\.\d+)?\s*(?:degrees?|arcminutes?|arcseconds?)/i',
            '/magnitude\s+[-+]?\d+(?:\.\d+)?/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $measurements = array_merge($measurements, $matches[0]);
            }
        }
        return implode(', ', array_unique($measurements));
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
                    $ai_explanations = array(
                        'basic' => $this->generate_basic_explanation($data['title'], $data['explanation']),
                        'detailed' => $this->generate_detailed_explanation($data['title'], $data['explanation']),
                        'technical' => $this->generate_technical_explanation($data['title'], $data['explanation'])
                    );
                    
                    $results[] = array(
                        'date' => $current_date,
                        'title' => $data['title'],
                        'imageUrl' => $data['url'],
                        'explanation' => $data['explanation'],
                        'aiExplanations' => $ai_explanations
                    );
                }
            }
            usleep(100000); // 100ms delay
        }
        return $results;
    }

    public function get_apod_data() {
        check_ajax_referer('apod_nonce', 'nonce');
        try {
            $end_date = new DateTime();
            $start_date = clone $end_date;
            $start_date->modify('-100 days');
            
            $all_results = $this->fetch_images($start_date, $end_date);
            
            $display_results = array_filter($all_results, function($result) {
                $date = new DateTime($result['date']);
                $thirtyDaysAgo = new DateTime();
                $thirtyDaysAgo->modify('-30 days');
                return $date >= $thirtyDaysAgo;
            });

            wp_send_json_success(array(
                'dateRange' => 'Last 30 Days',
                'totalImages' => count($display_results),
                'lastUpdate' => current_time('Y-m-d H:i:s'),
                'results' => array_values($display_results),
                'allImages' => $all_results
            ));
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

new APOD_AI_Analysis();