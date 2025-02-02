<?php
class APOD_Content_Analyzer {
    private $categories = array(
        'deep_space' => array(
            'name' => 'Deep Space Objects',
            'keywords' => ['galaxy', 'nebula', 'cluster', 'deep space', 'cosmic', 'ngc', 'messier', 'quasar', 'globular', 'interstellar']
        ),
        'solar_system' => array(
            'name' => 'Solar System Objects',
            'keywords' => ['planet', 'mars', 'venus', 'jupiter', 'saturn', 'mercury', 'uranus', 'neptune', 'pluto', 'moon', 'asteroid', 'comet']
        ),
        'star' => array(
            'name' => 'Stellar Objects',
            'keywords' => ['star', 'supernova', 'constellation', 'nova', 'stellar', 'sirius', 'betelgeuse', 'dwarf', 'binary']
        ),
        'earth_sky' => array(
            'name' => 'Earth and Sky Phenomena',
            'keywords' => ['aurora', 'meteor', 'eclipse', 'sky', 'atmosphere', 'rainbow', 'sunset', 'sunrise', 'clouds']
        ),
        'technology' => array(
            'name' => 'Astronomical Technology',
            'keywords' => ['telescope', 'observatory', 'spacecraft', 'satellite', 'space station', 'hubble', 'webb', 'iss', 'rocket']
        )
    );

    public function analyze_content($title, $explanation) {
        $text = strtolower($title . ' ' . $explanation);
        $analysis = array(
            'category' => $this->determine_category($text),
            'keywords' => $this->extract_keywords($text),
            'technical_terms' => $this->extract_technical_terms($text),
            'measurements' => $this->extract_measurements($text)
        );
        return $analysis;
    }

    public function find_similar_images($target, $all_images, $count = 3) {
        $target_analysis = $this->analyze_content($target['title'], $target['explanation']);
        $similarities = array();

        foreach ($all_images as $image) {
            if ($image['date'] === $target['date']) continue;

            $image_analysis = $this->analyze_content($image['title'], $image['explanation']);
            $similarity_score = $this->calculate_similarity_score(
                $target_analysis,
                $image_analysis
            );

            $similarities[] = array(
                'image' => $image,
                'score' => $similarity_score
            );
        }

        usort($similarities, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($similarities, 0, $count);
    }

    private function determine_category($text) {
        $max_score = 0;
        $best_category = 'other';

        foreach ($this->categories as $category => $info) {
            $score = 0;
            foreach ($info['keywords'] as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score > $max_score) {
                $max_score = $score;
                $best_category = $category;
            }
        }

        return array(
            'id' => $best_category,
            'name' => $this->categories[$best_category]['name'] ?? 'Other'
        );
    }

    private function extract_keywords($text) {
        $keywords = array();
        foreach ($this->categories as $info) {
            foreach ($info['keywords'] as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $keywords[] = $keyword;
                }
            }
        }
        return array_unique($keywords);
    }

    private function extract_technical_terms($text) {
        $patterns = array(
            '/(?:\d+(?:\.\d+)?\s*(?:light[- ]years?|parsecs?|AU|km))/i',
            '/(?:magnitude|luminosity|mass|temperature|radius)/i',
            '/(?:spectral[- ]type|red[- ]shift|wavelength)/i'
        );

        $terms = array();
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $terms = array_merge($terms, $matches[0]);
            }
        }
        return array_unique($terms);
    }

    private function extract_measurements($text) {
        $patterns = array(
            '/\d+(?:\.\d+)?\s*(?:light[- ]years?|parsecs?|AU|km|meters?)/i',
            '/\d+(?:\.\d+)?\s*(?:degrees?|arcminutes?|arcseconds?)/i',
            '/magnitude\s+[-+]?\d+(?:\.\d+)?/i'
        );

        $measurements = array();
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $measurements = array_merge($measurements, $matches[0]);
            }
        }
        return array_unique($measurements);
    }

    private function calculate_similarity_score($analysis1, $analysis2) {
        $score = 0;

        // Category match
        if ($analysis1['category']['id'] === $analysis2['category']['id']) {
            $score += 40;
        }

        // Keyword overlap
        $common_keywords = array_intersect($analysis1['keywords'], $analysis2['keywords']);
        $score += count($common_keywords) * 15;

        // Technical terms overlap
        $common_terms = array_intersect($analysis1['technical_terms'], $analysis2['technical_terms']);
        $score += count($common_terms) * 10;

        return min($score, 100);
    }
}