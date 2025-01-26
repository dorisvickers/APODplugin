<?php
/**
* Plugin Name: APOD AI Analysis
* Description: Analyses Astronomy Picture of the Day 
* Version: 1.4
* Author: <a href="https://ucrisportal.univie.ac.at/de/persons/doris-magdalena-vickers">Doris Vickers</a>
*/
if (!defined('ABSPATH')) exit;

class APOD_AI_Analysis {
   private $nasa_api_key;
   private $days_to_fetch = 100;
   
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
       $this->nasa_api_key = get_option('apod_ai_nasa_key', 'rhj4OobdMJlBOI78oYEJ6L3CL9a4p3C5aZtJqEvK');
       $this->init();
   }

   private function init() {
       add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
       add_shortcode('apod_ai_analysis', array($this, 'render_shortcode'));
       add_action('wp_ajax_get_apod_data', array($this, 'get_apod_data'));
       add_action('wp_ajax_nopriv_get_apod_data', array($this, 'get_apod_data'));
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

   private function fetch_images($start_date, $end_date) {
       $results = array();
       for ($date = clone $start_date; $date <= $end_date; $date->modify('+1 day')) {
           $current_date = $date->format('Y-m-d');
           $response = wp_remote_get("https://api.nasa.gov/planetary/apod?api_key={$this->nasa_api_key}&date={$current_date}");
           
           if (!is_wp_error($response)) {
               $data = json_decode(wp_remote_retrieve_body($response), true);
               if ($data && isset($data['media_type']) && $data['media_type'] === 'image') {
                   $results[] = array(
                       'date' => $current_date,
                       'title' => $data['title'],
                       'imageUrl' => $data['url'],
                       'explanation' => substr($data['explanation'], 0, 200) . '...'
                   );
               }
           }
       }
       return $results;
   }

    public function get_apod_data() {
        check_ajax_referer('apod_nonce', 'nonce');
       try {
           $end_date = new DateTime();
           $start_date = clone $end_date;
           $start_date->modify('-100 days');
           
           // Fetch all images for similarity comparison
           $all_results = $this->fetch_images($start_date, $end_date);
           
           // Filter last 30 days for display
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
               'allImages' => $all_results // For similarity comparison
           ));
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
}

new APOD_AI_Analysis();