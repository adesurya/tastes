<?php
/**
 * File: includes/class-image-handler.php
 * Image Handler for AI Rewriter - Focused on Google Images
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Image_Handler {
    
    private $google_api_key;
    private $google_search_engine_id;
    
    public function __construct() {
        $this->google_api_key = get_option('ai_rewriter_google_api_key', '');
        $this->google_search_engine_id = get_option('ai_rewriter_google_search_engine_id', '');
    }
    
    public function search_and_upload_image($keyword, $post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // PRIORITIZE TITLE as primary search term
        $title_based_terms = $this->generate_title_based_search_terms($post->post_title);
        
        // Add keyword-based terms as fallback
        $keyword_based_terms = $this->generate_contextual_search_terms($post->post_title, $post->post_content, $keyword);
        
        // Combine with title terms first (higher priority)
        $all_search_terms = array_merge($title_based_terms, $keyword_based_terms);
        $all_search_terms = array_unique($all_search_terms);
        
        // Try each search term until we find a good image
        foreach ($all_search_terms as $search_term) {
            $image_url = $this->search_google_images($search_term);
            
            if ($image_url && $this->validate_image_url($image_url)) {
                $image_id = $this->upload_image_to_media_library($image_url, $search_term, $post_id);
                if ($image_id) {
                    // Log successful search term for debugging
                    error_log("AI Rewriter: Successfully found image using term: " . $search_term);
                    return $image_id;
                }
            }
            
            // Add delay between searches
            sleep(1);
        }
        
        return false;
    }
    
    private function generate_title_based_search_terms($title) {
        $search_terms = array();
        
        // Clean title and extract meaningful parts
        $clean_title = $this->clean_title_for_search($title);
        
        // Use full title as primary search term
        $search_terms[] = $clean_title;
        
        // Extract key phrases from title (2-4 words)
        $key_phrases = $this->extract_key_phrases($title);
        foreach ($key_phrases as $phrase) {
            $search_terms[] = $phrase;
            $search_terms[] = $phrase . ' Indonesia'; // Add Indonesian context
        }
        
        // Extract main subject from title
        $main_subject = $this->extract_main_subject($title);
        if ($main_subject) {
            $search_terms[] = $main_subject;
            $search_terms[] = $main_subject . ' berita';
            $search_terms[] = $main_subject . ' Indonesia';
        }
        
        // Extract location from title if present
        $location = $this->extract_location_from_title($title);
        if ($location) {
            $search_terms[] = $location;
            $search_terms[] = $location . ' Indonesia';
            
            // Combine main subject with location
            if ($main_subject && $main_subject !== $location) {
                $search_terms[] = $main_subject . ' ' . $location;
            }
        }
        
        // Extract person/organization names from title
        $entities = $this->extract_entities_from_title($title);
        foreach ($entities as $entity) {
            $search_terms[] = $entity;
            $search_terms[] = $entity . ' Indonesia';
        }
        
        return array_unique($search_terms);
    }
    
    private function clean_title_for_search($title) {
        // Remove common news prefixes/suffixes
        $patterns_to_remove = array(
            '/^(BREAKING|BERITA|NEWS|UPDATE|TERBARU|TERKINI):\s*/i',
            '/\s*-\s*(Berita|News|Update|Terbaru|Terkini).*$/i',
            '/\s*\|\s*.*$/',  // Remove everything after |
            '/\s*\-\s*.*$/',  // Remove everything after -
            '/^Video:\s*/i',
            '/^Foto:\s*/i'
        );
        
        $clean_title = $title;
        foreach ($patterns_to_remove as $pattern) {
            $clean_title = preg_replace($pattern, '', $clean_title);
        }
        
        // Remove special characters but keep Indonesian characters
        $clean_title = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $clean_title);
        
        // Clean up extra spaces
        $clean_title = preg_replace('/\s+/', ' ', $clean_title);
        $clean_title = trim($clean_title);
        
        return $clean_title;
    }
    
    private function extract_key_phrases($title) {
        $phrases = array();
        $words = explode(' ', $this->clean_title_for_search($title));
        
        // Skip common stop words
        $stop_words = array('yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'oleh', 'akan', 'telah', 'sudah', 'adalah', 'ini', 'itu', 'juga', 'hanya', 'dapat', 'bisa', 'the', 'and', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by');
        
        $meaningful_words = array();
        foreach ($words as $word) {
            if (strlen($word) > 2 && !in_array(strtolower($word), $stop_words)) {
                $meaningful_words[] = $word;
            }
        }
        
        // Create 2-3 word phrases
        for ($i = 0; $i < count($meaningful_words) - 1; $i++) {
            // 2-word phrases
            if ($i < count($meaningful_words) - 1) {
                $phrases[] = $meaningful_words[$i] . ' ' . $meaningful_words[$i + 1];
            }
            
            // 3-word phrases
            if ($i < count($meaningful_words) - 2) {
                $phrases[] = $meaningful_words[$i] . ' ' . $meaningful_words[$i + 1] . ' ' . $meaningful_words[$i + 2];
            }
        }
        
        return array_slice($phrases, 0, 5); // Limit to top 5 phrases
    }
    
    private function extract_main_subject($title) {
        $title_lower = strtolower($title);
        
        // Political subjects
        $political_patterns = array(
            '/(presiden|president)\s+(\w+)/i' => '$1 $2',
            '/(menteri|minister)\s+(\w+)/i' => '$1 $2',
            '/(gubernur|governor)\s+(\w+)/i' => '$1 $2',
            '/(bupati|mayor)\s+(\w+)/i' => '$1 $2',
            '/(wakil\s+presiden|wapres|vice\s+president)/i' => 'wakil presiden',
            '/\b(jokowi|joko\s+widodo)\b/i' => 'Jokowi',
            '/\b(prabowo)\b/i' => 'Prabowo'
        );
        
        // Sports subjects
        $sports_patterns = array(
            '/\b(timnas|tim\s+nasional)\b/i' => 'timnas Indonesia',
            '/\b(liga\s+1|liga\s+indonesia)\b/i' => 'Liga 1',
            '/\b(persija|persib|arema|bali\s+united)\b/i' => '$0',
            '/\b(sepakbola|football|soccer)\b/i' => 'sepakbola'
        );
        
        // Economic subjects
        $economic_patterns = array(
            '/\b(bank\s+indonesia|bi)\b/i' => 'Bank Indonesia',
            '/\b(rupiah)\b/i' => 'rupiah',
            '/\b(inflasi|inflation)\b/i' => 'inflasi',
            '/\b(ojk|otoritas\s+jasa\s+keuangan)\b/i' => 'OJK'
        );
        
        // Technology subjects
        $tech_patterns = array(
            '/\b(gojek|grab|tokopedia|shopee|bukalapak)\b/i' => '$0',
            '/\b(smartphone|hp|handphone)\b/i' => 'smartphone',
            '/\b(internet|digital)\b/i' => '$0'
        );
        
        $all_patterns = array_merge($political_patterns, $sports_patterns, $economic_patterns, $tech_patterns);
        
        foreach ($all_patterns as $pattern => $replacement) {
            if (preg_match($pattern, $title, $matches)) {
                return preg_replace($pattern, $replacement, $matches[0]);
            }
        }
        
        // If no specific pattern found, get the first meaningful noun
        $words = explode(' ', $this->clean_title_for_search($title));
        $stop_words = array('yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'dengan', 'oleh', 'akan', 'telah', 'sudah', 'adalah', 'ini', 'itu');
        
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array(strtolower($word), $stop_words)) {
                return $word;
            }
        }
        
        return null;
    }
    
    private function extract_location_from_title($title) {
        $locations = array(
            // Major cities
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Makassar', 'Palembang',
            'Tangerang', 'Depok', 'Bekasi', 'Bogor', 'Batam', 'Pekanbaru', 'Bandar Lampung',
            'Malang', 'Padang', 'Denpasar', 'Samarinda', 'Tasikmalaya', 'Pontianak',
            'Yogyakarta', 'Solo', 'Manado', 'Kendari', 'Mataram',
            
            // Provinces
            'Aceh', 'Sumatra Utara', 'Sumatra Barat', 'Riau', 'Jambi', 'Sumatra Selatan',
            'Bengkulu', 'Lampung', 'Bangka Belitung', 'Kepulauan Riau', 'DKI Jakarta',
            'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Banten',
            'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
            'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
            'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara',
            'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat',
            
            // International
            'Malaysia', 'Singapura', 'Thailand', 'Vietnam', 'Filipina', 'Myanmar',
            'Brunei', 'Laos', 'Kamboja', 'Amerika Serikat', 'Amerika', 'China', 'Jepang',
            'Korea Selatan', 'Korea', 'India', 'Australia', 'Inggris', 'Jerman', 'Prancis'
        );
        
        foreach ($locations as $location) {
            if (stripos($title, $location) !== false) {
                return $location;
            }
        }
        
        return null;
    }
    
    private function extract_entities_from_title($title) {
        $entities = array();
        
        // Extract capitalized words (likely proper nouns)
        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $title, $matches);
        
        if (isset($matches[0])) {
            foreach ($matches[0] as $entity) {
                // Skip common words that are capitalized at start of sentence
                $skip_words = array('Pada', 'Dalam', 'Untuk', 'Dengan', 'Oleh', 'Dari', 'Ke', 'Yang', 'Ini', 'Itu', 'The', 'And', 'Or', 'But', 'In', 'On', 'At', 'To', 'For', 'Of', 'With', 'By');
                
                if (!in_array($entity, $skip_words) && strlen($entity) > 2) {
                    $entities[] = $entity;
                }
            }
        }
        
        // Extract known organizations/companies
        $organizations = array(
            'DPR', 'MPR', 'KPK', 'Polri', 'TNI', 'BUMN', 'BUMD', 'Kemenkeu', 'Kemenkes',
            'PDI-P', 'Golkar', 'Gerindra', 'Demokrat', 'PKS', 'PAN', 'PPP',
            'Pertamina', 'Telkom', 'BCA', 'BRI', 'BNI', 'Mandiri', 'Garuda Indonesia',
            'Lion Air', 'Gojek', 'Grab', 'Tokopedia', 'Shopee', 'Bukalapak', 'Traveloka'
        );
        
        foreach ($organizations as $org) {
            if (stripos($title, $org) !== false) {
                $entities[] = $org;
            }
        }
        
        return array_unique($entities);
    }
    
    private function generate_contextual_search_terms($title, $content, $keyword) {
        $search_terms = array();
        $enhanced_keyword = $this->enhance_search_keyword($keyword, $title, $content);
        
        // Primary enhanced search term
        $search_terms[] = $enhanced_keyword;
        
        // Add location context if detected
        $location = $this->extract_primary_location($title . ' ' . $content);
        if ($location && $location !== $keyword) {
            $search_terms[] = $keyword . ' ' . $location;
        }
        
        // Add category context
        $category = $this->detect_news_category($title . ' ' . $content);
        if ($category) {
            $search_terms[] = $keyword . ' ' . $category;
        }
        
        // Add year for recent events
        if ($this->is_recent_event($title . ' ' . $content)) {
            $search_terms[] = $keyword . ' ' . date('Y');
        }
        
        // Fallback to original keyword
        $search_terms[] = $keyword;
        
        return array_unique($search_terms);
    }
    
    private function enhance_search_keyword($keyword, $title, $content) {
        $clean_keyword = preg_replace('/[^a-zA-Z0-9\s]/', '', $keyword);
        $clean_keyword = trim($clean_keyword);
        
        $context_text = strtolower($title . ' ' . $content);
        $keyword_lower = strtolower($keyword);
        
        // Add specific context modifiers based on content analysis
        if (preg_match('/\b(presiden|menteri|gubernur|bupati|walikota|pejabat)\b/i', $context_text)) {
            return $clean_keyword . ' government official indonesia';
        }
        
        if (preg_match('/\b(sepakbola|liga|pertandingan|pemain|stadion|olahraga)\b/i', $context_text)) {
            return $clean_keyword . ' football soccer indonesia sports';
        }
        
        if (preg_match('/\b(ekonomi|bisnis|bank|saham|rupiah|investasi)\b/i', $context_text)) {
            return $clean_keyword . ' business economy indonesia financial';
        }
        
        if (preg_match('/\b(teknologi|smartphone|aplikasi|digital|startup)\b/i', $context_text)) {
            return $clean_keyword . ' technology digital indonesia modern';
        }
        
        if (preg_match('/\b(kesehatan|rumah sakit|dokter|covid|vaksin)\b/i', $context_text)) {
            return $clean_keyword . ' healthcare medical indonesia hospital';
        }
        
        if (preg_match('/\b(pendidikan|sekolah|universitas|mahasiswa|guru)\b/i', $context_text)) {
            return $clean_keyword . ' education school indonesia university';
        }
        
        if (preg_match('/\b(polisi|pengadilan|hukum|jaksa|hakim)\b/i', $context_text)) {
            return $clean_keyword . ' law enforcement indonesia court';
        }
        
        if (preg_match('/\b(bencana|gempa|banjir|tsunami|kebakaran)\b/i', $context_text)) {
            return $clean_keyword . ' disaster emergency indonesia natural';
        }
        
        if (preg_match('/\b(transportasi|pesawat|kereta|kapal|bandara)\b/i', $context_text)) {
            return $clean_keyword . ' transportation indonesia travel';
        }
        
        if (preg_match('/\b(wisata|pariwisata|hotel|pantai|gunung)\b/i', $context_text)) {
            return $clean_keyword . ' tourism travel indonesia destination';
        }
        
        // Check if keyword is a person name (capitalized)
        if (ucfirst($keyword) === $keyword && strpos($keyword, ' ') !== false) {
            return $clean_keyword . ' person indonesia profile';
        }
        
        // Check if keyword is a place
        $indonesian_places = array('jakarta', 'surabaya', 'bandung', 'medan', 'semarang', 'makassar', 'palembang');
        foreach ($indonesian_places as $place) {
            if (stripos($keyword_lower, $place) !== false) {
                return $clean_keyword . ' city indonesia urban landscape';
            }
        }
        
        // Default enhancement
        return $clean_keyword . ' indonesia news';
    }
    
    private function search_google_images($keyword) {
        if (empty($this->google_api_key) || empty($this->google_search_engine_id)) {
            error_log('AI Rewriter: Google API credentials not configured');
            return false;
        }
        
        // Enhanced search parameters focused on news/journalism images
        $search_params = array(
            'key' => $this->google_api_key,
            'cx' => $this->google_search_engine_id,
            'q' => $keyword,
            'searchType' => 'image',
            'imgSize' => 'large',
            'imgType' => 'photo',
            'imgColorType' => 'color',
            'safe' => 'active',
            'rights' => 'cc_publicdomain,cc_attribute,cc_sharealike,cc_nonderived',
            'fileType' => 'jpg,png',      // Prefer standard formats
            'num' => 8                    // Get more options for better selection
        );
        
        // Add site restriction for news sources if keyword seems news-related
        if ($this->is_news_related_keyword($keyword)) {
            $search_params['siteSearch'] = 'detik.com OR kompas.com OR tempo.co OR cnnindonesia.com OR liputan6.com OR antaranews.com OR republika.co.id OR okezone.com';
            $search_params['siteSearchFilter'] = 'i'; // Include these sites
        }
        
        $url = 'https://www.googleapis.com/customsearch/v1?' . http_build_query($search_params);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
        ));
        
        if (is_wp_error($response)) {
            error_log('AI Rewriter: Google Images API error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('AI Rewriter: Google Images API HTTP error: ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['items']) && !empty($data['items'])) {
            // Sort results by relevance and quality
            $scored_items = array();
            
            foreach ($data['items'] as $item) {
                $score = $this->calculate_image_relevance_score($item, $keyword);
                if ($score > 0) {
                    $scored_items[] = array('item' => $item, 'score' => $score);
                }
            }
            
            // Sort by score (highest first)
            usort($scored_items, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            // Return the highest scoring image
            foreach ($scored_items as $scored_item) {
                if (isset($scored_item['item']['link'])) {
                    error_log('AI Rewriter: Selected image with score: ' . $scored_item['score'] . ' for keyword: ' . $keyword);
                    return $scored_item['item']['link'];
                }
            }
        }
        
        return false;
    }
    
    private function is_news_related_keyword($keyword) {
        $news_indicators = array(
            'presiden', 'menteri', 'gubernur', 'bupati', 'walikota', 'dpr', 'mpr', 'kpk', 'polri', 'tni',
            'pemilu', 'pilkada', 'politik', 'pemerintah', 'kabinet', 'partai',
            'jokowi', 'prabowo', 'megawati', 'anies', 'ganjar', 'ridwan kamil',
            'jakarta', 'surabaya', 'bandung', 'medan', 'semarang', 'makassar',
            'ekonomi', 'inflasi', 'rupiah', 'bank indonesia', 'ojk', 'sri mulyani',
            'covid', 'vaksin', 'pandemic', 'kesehatan', 'rumah sakit',
            'bencana', 'gempa', 'banjir', 'tsunami', 'kebakaran', 'bnpb',
            'timnas', 'liga 1', 'pssi', 'persija', 'persib', 'arema',
            'breaking', 'berita', 'terbaru', 'terkini', 'update'
        );
        
        $keyword_lower = strtolower($keyword);
        foreach ($news_indicators as $indicator) {
            if (strpos($keyword_lower, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function calculate_image_relevance_score($image_data, $keyword) {
        $score = 0;
        $keyword_lower = strtolower($keyword);
        $keyword_parts = explode(' ', $keyword_lower);
        
        // Get image metadata
        $title = strtolower($image_data['title'] ?? '');
        $snippet = strtolower($image_data['snippet'] ?? '');
        $display_link = strtolower($image_data['displayLink'] ?? '');
        $context_link = strtolower($image_data['image']['contextLink'] ?? '');
        
        // Title relevance (highest weight)
        foreach ($keyword_parts as $part) {
            if (strlen($part) > 2) {
                if (strpos($title, $part) !== false) {
                    $score += 5;
                }
                if (strpos($snippet, $part) !== false) {
                    $score += 3;
                }
            }
        }
        
        // Exact keyword match bonus
        if (strpos($title, $keyword_lower) !== false) {
            $score += 10;
        }
        if (strpos($snippet, $keyword_lower) !== false) {
            $score += 7;
        }
        
        // Indonesian context bonus
        $indonesian_indicators = array('indonesia', 'jakarta', 'surabaya', 'bandung', 'detik', 'kompas', 'tempo', 'liputan6', 'cnnindonesia');
        foreach ($indonesian_indicators as $indicator) {
            if (strpos($title, $indicator) !== false || strpos($snippet, $indicator) !== false || strpos($display_link, $indicator) !== false) {
                $score += 4;
                break;
            }
        }
        
        // News source bonus
        $trusted_news_domains = array(
            'detik.com' => 8,
            'kompas.com' => 8,
            'tempo.co' => 7,
            'cnnindonesia.com' => 7,
            'liputan6.com' => 6,
            'antaranews.com' => 6,
            'republika.co.id' => 5,
            'okezone.com' => 4,
            'tribunnews.com' => 4,
            'jpnn.com' => 3
        );
        
        foreach ($trusted_news_domains as $domain => $bonus) {
            if (strpos($display_link, $domain) !== false) {
                $score += $bonus;
                break;
            }
        }
        
        // Image dimension check
        if (isset($image_data['image'])) {
            $width = $image_data['image']['width'] ?? 0;
            $height = $image_data['image']['height'] ?? 0;
            
            // Penalize too small images
            if ($width < 400 || $height < 300) {
                $score -= 5;
            }
            
            // Bonus for good aspect ratios (landscape preferred for news)
            if ($width > 0 && $height > 0) {
                $ratio = $width / $height;
                if ($ratio >= 1.2 && $ratio <= 2.0) {
                    $score += 3; // Good landscape ratio
                } elseif ($ratio < 0.5 || $ratio > 3.0) {
                    $score -= 3; // Too narrow or too wide
                }
            }
            
            // Bonus for optimal size
            if ($width >= 800 && $width <= 2000 && $height >= 600 && $height <= 1500) {
                $score += 2;
            }
        }
        
        // Recent content bonus (if URL suggests it's recent)
        $current_year = date('Y');
        $last_year = date('Y', strtotime('-1 year'));
        
        if (strpos($context_link, $current_year) !== false || strpos($context_link, $last_year) !== false) {
            $score += 2;
        }
        
        // Category-specific bonuses
        $keyword_categories = array(
            'politik' => array('presiden', 'menteri', 'dpr', 'politik', 'pemilu', 'pilkada'),
            'olahraga' => array('sepakbola', 'timnas', 'liga', 'pertandingan', 'pemain'),
            'ekonomi' => array('ekonomi', 'bisnis', 'rupiah', 'inflasi', 'bank'),
            'teknologi' => array('teknologi', 'smartphone', 'aplikasi', 'digital', 'internet'),
            'kesehatan' => array('covid', 'vaksin', 'kesehatan', 'rumah sakit', 'dokter'),
            'bencana' => array('gempa', 'banjir', 'tsunami', 'kebakaran', 'bencana')
        );
        
        foreach ($keyword_categories as $category => $category_keywords) {
            foreach ($category_keywords as $cat_keyword) {
                if (strpos($keyword_lower, $cat_keyword) !== false) {
                    // Look for category-specific terms in image metadata
                    if (strpos($title . ' ' . $snippet, $category) !== false) {
                        $score += 3;
                    }
                    break 2;
                }
            }
        }
        
        return max(0, $score); // Ensure non-negative score
    }
    
    private function is_appropriate_image($image_data, $keyword) {
        // Check image dimensions
        if (isset($image_data['image'])) {
            $width = $image_data['image']['width'] ?? 0;
            $height = $image_data['image']['height'] ?? 0;
            
            if ($width < 400 || $height < 300) {
                return false; // Too small
            }
            
            if ($width > 5000 || $height > 5000) {
                return false; // Too large
            }
            
            // Check aspect ratio (avoid extremely narrow or wide images)
            $ratio = $width / $height;
            if ($ratio < 0.5 || $ratio > 3.0) {
                return false;
            }
        }
        
        // Check content relevance
        $title = $image_data['title'] ?? '';
        $snippet = $image_data['snippet'] ?? '';
        $display_link = $image_data['displayLink'] ?? '';
        
        $keyword_lower = strtolower($keyword);
        $title_lower = strtolower($title);
        $snippet_lower = strtolower($snippet);
        
        // Calculate relevance score
        $relevance_score = 0;
        
        if (strpos($title_lower, $keyword_lower) !== false) {
            $relevance_score += 3;
        }
        
        if (strpos($snippet_lower, $keyword_lower) !== false) {
            $relevance_score += 2;
        }
        
        // Check for Indonesian context
        if (strpos($title_lower, 'indonesia') !== false || strpos($snippet_lower, 'indonesia') !== false) {
            $relevance_score += 2;
        }
        
        // Prefer news/media sources
        $trusted_domains = array('detik.com', 'kompas.com', 'tempo.co', 'cnn.com', 'bbc.com', 'reuters.com');
        foreach ($trusted_domains as $domain) {
            if (strpos($display_link, $domain) !== false) {
                $relevance_score += 1;
                break;
            }
        }
        
        // Check individual keyword components
        $keyword_parts = explode(' ', $keyword_lower);
        foreach ($keyword_parts as $part) {
            if (strlen($part) > 3) {
                if (strpos($title_lower, $part) !== false) {
                    $relevance_score += 1;
                }
            }
        }
        
        return $relevance_score >= 3; // Minimum relevance threshold
    }
    
    public function validate_image_url($url) {
        $response = wp_remote_head($url, array(
            'timeout' => 15,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'headers' => array(
                'Accept' => 'image/*',
                'Referer' => get_bloginfo('url')
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $content_length = wp_remote_retrieve_header($response, 'content-length');
        
        if ($status_code !== 200) {
            return false;
        }
        
        if (!$content_type || strpos($content_type, 'image/') !== 0) {
            return false;
        }
        
        // Check file size (10KB - 3MB)
        if ($content_length) {
            $size_kb = intval($content_length) / 1024;
            if ($size_kb < 10 || $size_kb > 3072) {
                return false;
            }
        }
        
        return true;
    }
    
    private function upload_image_to_media_library($image_url, $keyword, $post_id) {
        $response = wp_remote_get($image_url, array(
            'timeout' => 60,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'headers' => array(
                'Accept' => 'image/*',
                'Referer' => get_bloginfo('url')
            )
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        
        if (empty($image_data) || strlen($image_data) < 1024) {
            return false;
        }
        
        // Determine file extension
        $extension = 'jpg';
        if (strpos($content_type, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($content_type, 'gif') !== false) {
            $extension = 'gif';
        } elseif (strpos($content_type, 'webp') !== false) {
            $extension = 'webp';
        }
        
        // Generate filename
        $clean_keyword = $this->sanitize_filename($keyword);
        $filename = $clean_keyword . '-' . time() . '.' . $extension;
        
        // Upload to media library
        $upload = wp_upload_bits($filename, null, $image_data);
        
        if ($upload['error']) {
            return false;
        }
        
        // Create attachment
        $attachment = array(
            'guid' => $upload['url'],
            'post_mime_type' => $content_type,
            'post_title' => $this->generate_image_title($keyword),
            'post_content' => $this->generate_image_description($keyword),
            'post_excerpt' => $this->generate_image_caption($keyword),
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Set metadata
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $this->generate_alt_text($keyword));
        update_post_meta($attachment_id, '_ai_rewriter_source', 'google');
        update_post_meta($attachment_id, '_ai_rewriter_keyword', $keyword);
        update_post_meta($attachment_id, '_ai_rewriter_original_url', $image_url);
        
        return $attachment_id;
    }
    
    public function add_images_to_content($post_id, $keywords) {
        $added_count = 0;
        $max_images = get_option('ai_rewriter_max_images', 2);
        
        if ($max_images <= 1) {
            return $added_count;
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return $added_count;
        }
        
        $content = $post->post_content;
        $paragraphs = explode('</p>', $content);
        $total_paragraphs = count($paragraphs);
        
        if ($total_paragraphs < 3) {
            return $added_count;
        }
        
        // Filter keywords for better relevance
        $filtered_keywords = $this->filter_keywords_for_images($keywords);
        
        foreach ($filtered_keywords as $index => $keyword) {
            if ($added_count >= ($max_images - 1)) {
                break;
            }
            
            $image_id = $this->search_and_upload_image($keyword, $post_id);
            
            if ($image_id) {
                $image_html = wp_get_attachment_image($image_id, 'large', false, array(
                    'alt' => $this->generate_alt_text($keyword),
                    'class' => 'wp-image-' . $image_id . ' aligncenter size-large',
                    'title' => $this->generate_image_title($keyword)
                ));
                
                // Calculate position
                $insert_position = $this->calculate_image_position($index, count($filtered_keywords), $total_paragraphs);
                
                if ($insert_position < count($paragraphs)) {
                    $paragraphs[$insert_position] .= '</p><figure class="wp-block-image size-large aligncenter">' . $image_html . '<figcaption>' . $this->generate_image_caption($keyword) . '</figcaption></figure>';
                    $added_count++;
                }
            }
            
            sleep(2); // Rate limiting
        }
        
        if ($added_count > 0) {
            $new_content = implode('</p>', $paragraphs);
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content
            ));
        }
        
        return $added_count;
    }
    
    // Helper functions
    private function extract_primary_location($text) {
        $locations = array(
            'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Makassar', 'Palembang',
            'Tangerang', 'Depok', 'Bekasi', 'Bogor', 'Batam', 'Pekanbaru', 'Bandar Lampung',
            'Indonesia', 'Malaysia', 'Singapura', 'Thailand', 'Amerika', 'China', 'Jepang'
        );
        
        foreach ($locations as $location) {
            if (stripos($text, $location) !== false) {
                return $location;
            }
        }
        
        return null;
    }
    
    private function detect_news_category($text) {
        $text_lower = strtolower($text);
        
        $categories = array(
            'politik' => array('pemilu', 'pilkada', 'presiden', 'menteri', 'dpr', 'politik'),
            'olahraga' => array('sepakbola', 'liga', 'pemain', 'pertandingan', 'olahraga'),
            'ekonomi' => array('ekonomi', 'bisnis', 'saham', 'rupiah', 'bank'),
            'teknologi' => array('teknologi', 'smartphone', 'aplikasi', 'digital'),
            'kesehatan' => array('kesehatan', 'rumah sakit', 'dokter', 'covid'),
            'pendidikan' => array('pendidikan', 'sekolah', 'universitas', 'mahasiswa')
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text_lower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return null;
    }
    
    private function is_recent_event($text) {
        $current_year = date('Y');
        $recent_indicators = array('hari ini', 'kemarin', 'terbaru', 'terkini', $current_year);
        
        $text_lower = strtolower($text);
        foreach ($recent_indicators as $indicator) {
            if (strpos($text_lower, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function filter_keywords_for_images($keywords) {
        $filtered = array();
        $generic_terms = array('berita', 'informasi', 'hari', 'tahun', 'kali', 'bagian');
        
        foreach ($keywords as $keyword) {
            if (!in_array(strtolower($keyword), $generic_terms) && strlen($keyword) > 4) {
                $filtered[] = $keyword;
            }
        }
        
        return array_slice($filtered, 0, 3);
    }
    
    private function calculate_image_position($keyword_index, $total_keywords, $total_paragraphs) {
        if ($total_keywords === 1) {
            return intval($total_paragraphs * 0.4);
        }
        
        $section_size = $total_paragraphs / ($total_keywords + 1);
        return intval(($keyword_index + 1) * $section_size);
    }
    
    private function sanitize_filename($keyword) {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $keyword);
        $clean = preg_replace('/\s+/', '-', $clean);
        $clean = strtolower($clean);
        return substr($clean, 0, 50) ?: 'image';
    }
    
    private function generate_image_title($keyword) {
        return ucwords(str_replace(array('-', '_'), ' ', $keyword));
    }
    
    private function generate_image_description($keyword) {
        return 'Image related to ' . $keyword . ' - sourced for article illustration';
    }
    
    private function generate_image_caption($keyword) {
        return 'Ilustrasi: ' . ucwords(str_replace(array('-', '_'), ' ', $keyword));
    }
    
    private function generate_alt_text($keyword) {
        $base_alt = ucwords(str_replace(array('-', '_'), ' ', $keyword));
        $keyword_lower = strtolower($keyword);
        
        if (strpos($keyword_lower, 'presiden') !== false) {
            return $base_alt . ' - Pejabat pemerintah Indonesia';
        } elseif (strpos($keyword_lower, 'olahraga') !== false) {
            return $base_alt . ' - Aktivitas olahraga';
        } elseif (strpos($keyword_lower, 'teknologi') !== false) {
            return $base_alt . ' - Konsep teknologi';
        } elseif (strpos($keyword_lower, 'ekonomi') !== false) {
            return $base_alt . ' - Bisnis dan ekonomi';
        }
        
        return $base_alt;
    }
}
?>