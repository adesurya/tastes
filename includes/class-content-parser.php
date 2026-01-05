<?php
/**
 * File: includes/class-content-parser.php
 * Content Parser for AI Rewriter - FIXED VERSION
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Content_Parser {
    
    private $language = 'Indonesian';
    private $writing_style = 'professional';
    
    public function set_language($language) {
        $this->language = $language;
    }
    
    public function set_writing_style($style) {
        $this->writing_style = $style;
    }
    
    public function generate_prompt($title, $content, $custom_prompt = '', $instructions = '') {
        // Use custom prompt if provided
        if (!empty($custom_prompt)) {
            $prompt = $custom_prompt;
            $prompt = str_replace('{title}', $title, $prompt);
            $prompt = str_replace('{content}', $content, $prompt);
            $prompt = str_replace('{language}', $this->language, $prompt);
            $prompt = str_replace('{style}', $this->writing_style, $prompt);
            
            if (!empty($instructions)) {
                $prompt .= "\n\nAdditional Instructions: " . $instructions;
            }
            
            return $prompt;
        }
        
        // Enhanced default prompt for Indonesian news rewriting
        $prompt = "Please ignore all previous instructions. I want you to respond only in language Indonesian. I want you to act as an experienced and expert news writer that speaks and writes fluently Indonesian. You will re-write the content in Anderson Cooper's formal, engaging, and straight news style, as if you are talking to human.\n\n";
        
        $prompt .= "I want you to pretend that you can write content so well in Indonesian that it can outrank other websites. Your task is to write an article starting with SEO Title with a bold letter and rewrite the content and include subheadings using related keywords. Make sure your title is different from the original title without changing the meaning of the title. Add the words \"CryptoMarket.id -\" at the beginning of article not in title. Don't make a \"Kesimpulan -\" or summary at the end of the news\n\n";
        
        $prompt .= "IMPORTANT REQUIREMENTS:\n";
        $prompt .= "- You will not add any fact from your end about the topic\n";
        $prompt .= "- The article must be related to the original article for example place, and names\n";
        $prompt .= "- The article must be 1000 to 1800 words\n";
        $prompt .= "- All output shall be in Indonesian and must be 100% human writing style\n";
        $prompt .= "- Fix grammar errors like Grammarly.com\n";
        $prompt .= "- Make sure the rewritten content is different from the previous results\n";
        $prompt .= "- Do not include the word 'Judul:' in the article title\n";
        $prompt .= "- Use markdown formatting for better readability\n";
        $prompt .= "- Include relevant subheadings with ## format\n";
        $prompt .= "- Use **bold** for important points\n";
        $prompt .= "- Use proper paragraph spacing\n\n";
        
        $prompt .= "WRITING STYLE GUIDELINES:\n";
        $prompt .= "- Write in " . $this->writing_style . " tone\n";
        $prompt .= "- Use engaging opening paragraph that hooks readers\n";
        $prompt .= "- Include compelling subheadings that break up content\n";
        $prompt .= "- Write in active voice whenever possible\n";
        $prompt .= "- Use short to medium sentences for readability\n";
        $prompt .= "- Include transitions between paragraphs\n";
        $prompt .= "- End with a strong conclusion\n\n";
        
        $prompt .= "SEO OPTIMIZATION:\n";
        $prompt .= "- Create catchy, SEO-friendly title. Use the full title without any punctuation marks.\n";
        $prompt .= "- Use relevant keywords naturally throughout content\n";
        $prompt .= "- Include location-specific terms if applicable\n";
        $prompt .= "- Write meta-description worthy introduction\n\n";
        
        if (!empty($instructions)) {
            $prompt .= "ADDITIONAL INSTRUCTIONS: " . $instructions . "\n\n";
        }
        
        $prompt .= "Original Title: {$title}\n\n";
        $prompt .= "Original Content:\n{$content}\n\n";
        $prompt .= "Please provide the rewritten article with proper markdown formatting:";
        
        return $prompt;
    }
    
    public function parse_rewritten_content($ai_response) {
        // Clean up the response
        $content = trim($ai_response);
        
        // Extract title from markdown or text patterns
        $title = '';
        $body = $content;
        
        // Look for markdown title patterns first
        $title_patterns = array(
            '/^# (.+?)$/im',              // # Title
            '/^\*\*(.+?)\*\*$/im',        // **Title**
            '/^Title:\s*(.+?)$/im',       // Title: Something
            '/^Judul:\s*(.+?)$/im',       // Judul: Something
            '/^New Title:\s*(.+?)$/im',   // New Title: Something
            '/^Rewritten Title:\s*(.+?)$/im' // Rewritten Title: Something
        );
        
        foreach ($title_patterns as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $title = trim($matches[1]);
                $body = preg_replace($pattern, '', $content, 1);
                break;
            }
        }
        
        // If no title found with patterns, try to extract first bold or heading line
        if (empty($title)) {
            $lines = explode("\n", $content);
            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (!empty($line) && strlen($line) < 200) {
                    // Check if it's a heading or bold text
                    if (preg_match('/^#+\s*(.+)$/', $line, $matches)) {
                        $title = trim($matches[1]);
                        unset($lines[$index]);
                        $body = implode("\n", $lines);
                        break;
                    } elseif (preg_match('/^\*\*(.+?)\*\*$/', $line, $matches)) {
                        $title = trim($matches[1]);
                        unset($lines[$index]);
                        $body = implode("\n", $lines);
                        break;
                    }
                }
            }
        }
        
        // Clean up body content
        $body = preg_replace('/^(Content:|New Content:|Rewritten Content:)/im', '', $body);
        $body = trim($body);
        
        // If still no title, generate one from content
        if (empty($title)) {
            $title = $this->generate_title_from_content($body);
        }
        
        return array(
            'title' => $this->clean_title($title),
            'content' => $this->clean_content($body)
        );
    }
    
    // MISSING METHODS - FIXED
    public function clean_title($title) {
        // Remove markdown formatting
        $title = preg_replace('/[#*_`]/', '', $title);
        
        // Remove HTML tags
        $title = strip_tags($title);
        
        // Clean up multiple spaces
        $title = preg_replace('/\s+/', ' ', $title);
        
        // Remove unwanted prefixes
        $title = preg_replace('/^(BREAKING|BERITA|NEWS|UPDATE|TERBARU|TERKINI):\s*/i', '', $title);
        
        // Trim and ensure proper length
        $title = trim($title);
        
        // Limit title length for SEO
        if (strlen($title) > 80) {
            $title = substr($title, 0, 77) . '...';
        }
        
        return $title;
    }
    
    public function clean_content($content) {
        // Remove unwanted patterns
        $content = preg_replace('/^(Content:|New Content:|Rewritten Content:)/im', '', $content);
        
        // Clean up extra line breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Remove trailing spaces
        $content = preg_replace('/[ \t]+$/m', '', $content);
        
        // Trim
        $content = trim($content);
        
        return $content;
    }
    
    public function generate_title_from_content($content) {
        // Extract first meaningful sentence or heading
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and markdown syntax
            if (empty($line) || preg_match('/^[#*\-_]/', $line)) {
                continue;
            }
            
            // Clean the line
            $line = strip_tags($line);
            $line = preg_replace('/[#*_`]/', '', $line);
            $line = trim($line);
            
            // Check if it's a good title candidate
            if (strlen($line) > 10 && strlen($line) < 100) {
                return $line;
            }
        }
        
        // Fallback: generate from first few words
        $words = explode(' ', strip_tags($content));
        $title_words = array_slice($words, 0, 8);
        return implode(' ', $title_words);
    }
    
    public function format_for_wordpress($content) {
        // Convert markdown to HTML for WordPress
        $content = $this->convert_markdown_to_html($content);
        
        // Convert line breaks to WordPress paragraphs if not already HTML
        if (strpos($content, '<p>') === false) {
            $content = wpautop($content);
        }
        
        return $content;
    }
    
    private function convert_markdown_to_html($content) {
        // Convert markdown headings
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        
        // Convert bold text
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $content);
        
        // Convert links
        $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $content);
        
        // Convert lists
        $content = preg_replace('/^- (.+)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/^(\d+)\. (.+)$/m', '<li>$2</li>', $content);
        
        // Wrap consecutive list items in ul/ol tags
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);
        $content = preg_replace('/<\/ul>\s*<ul>/', '', $content);
        
        // Convert blockquotes
        $content = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $content);
        
        // Convert horizontal rules
        $content = preg_replace('/^---$/m', '<hr>', $content);
        $content = preg_replace('/^\*\*\*$/m', '<hr>', $content);
        
        // Convert code blocks
        $content = preg_replace('/```(\w+)?\n(.*?)\n```/s', '<pre><code>$2</code></pre>', $content);
        $content = preg_replace('/`([^`]+)`/', '<code>$1</code>', $content);
        
        // Clean up extra whitespace but preserve paragraph breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        // Convert remaining double line breaks to paragraph breaks
        $content = str_replace("\n\n", "</p>\n<p>", $content);
        
        // Wrap in paragraphs if not already wrapped
        if (strpos($content, '<p>') !== 0) {
            $content = '<p>' . $content . '</p>';
        }
        
        // Clean up empty paragraphs
        $content = preg_replace('/<p>\s*<\/p>/', '', $content);
        
        // Fix heading tags that got wrapped in paragraphs
        $content = preg_replace('/<p>(<h[1-6]>.*?<\/h[1-6]>)<\/p>/', '$1', $content);
        $content = preg_replace('/<p>(<hr>)<\/p>/', '$1', $content);
        $content = preg_replace('/<p>(<blockquote>.*?<\/blockquote>)<\/p>/s', '$1', $content);
        $content = preg_replace('/<p>(<ul>.*?<\/ul>)<\/p>/s', '$1', $content);
        $content = preg_replace('/<p>(<ol>.*?<\/ol>)<\/p>/s', '$1', $content);
        $content = preg_replace('/<p>(<pre>.*?<\/pre>)<\/p>/s', '$1', $content);
        
        return $content;
    }
    
    public function enhance_content_formatting($content) {
        // Add reading time estimation
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200); // 200 words per minute average
        
        // Add schema markup for articles
        $schema_markup = '<!-- Article Schema Markup -->';
        
        // Enhance subheadings with better styling
        $content = preg_replace('/<h2>(.*?)<\/h2>/', '<h2 class="article-subheading">$1</h2>', $content);
        $content = preg_replace('/<h3>(.*?)<\/h3>/', '<h3 class="article-subheading-small">$1</h3>', $content);
        
        // Add CSS classes for better styling
        $content = preg_replace('/<strong>(.*?)<\/strong>/', '<strong class="highlight-text">$1</strong>', $content);
        $content = preg_replace('/<blockquote>(.*?)<\/blockquote>/s', '<blockquote class="article-quote">$1</blockquote>', $content);
        
        // Add responsive image classes if images are present
        $content = preg_replace('/<img([^>]+)>/', '<img$1 class="responsive-image">', $content);
        
        return $content;
    }
    
    public function add_table_of_contents($content) {
        // Extract headings for table of contents
        preg_match_all('/<h([2-3]).*?>(.*?)<\/h[2-3]>/i', $content, $matches, PREG_SET_ORDER);
        
        if (count($matches) >= 3) {
            $toc = '<div class="table-of-contents">';
            $toc .= '<h3>Daftar Isi</h3>';
            $toc .= '<ul>';
            
            foreach ($matches as $index => $match) {
                $level = $match[1];
                $heading = strip_tags($match[2]);
                $anchor = sanitize_title($heading);
                
                // Add anchor to the original heading
                $content = str_replace($match[0], str_replace('>', ' id="' . $anchor . '">', $match[0]), $content);
                
                $class = $level == '2' ? 'toc-main' : 'toc-sub';
                $toc .= '<li class="' . $class . '"><a href="#' . $anchor . '">' . $heading . '</a></li>';
            }
            
            $toc .= '</ul>';
            $toc .= '</div>';
            
            // Insert TOC after first paragraph
            $content = preg_replace('/(<\/p>)/', '$1' . $toc, $content, 1);
        }
        
        return $content;
    }
    
    public function optimize_for_seo($content, $title) {
        // Extract focus keyword from title
        $focus_keyword = $this->extract_focus_keyword($title);
        
        // Ensure keyword density is appropriate (1-3%)
        $total_words = str_word_count(strip_tags($content));
        $keyword_count = substr_count(strtolower(strip_tags($content)), strtolower($focus_keyword));
        $density = ($keyword_count / $total_words) * 100;
        
        // Add keyword to first paragraph if not present
        if ($density < 1) {
            $content = preg_replace('/(<p>.*?<\/p>)/', '$1', $content, 1, $count);
            if ($count > 0) {
                $first_para = preg_match('/<p>(.*?)<\/p>/', $content, $matches);
                if ($first_para && strpos(strtolower($matches[1]), strtolower($focus_keyword)) === false) {
                    $enhanced_para = str_replace('</p>', ' ' . $focus_keyword . '.</p>', $matches[0]);
                    $content = str_replace($matches[0], $enhanced_para, $content);
                }
            }
        }
        
        // Add related keywords to subheadings
        $related_keywords = $this->generate_related_keywords($focus_keyword);
        foreach ($related_keywords as $related) {
            $content = preg_replace('/<h2>(.*?)<\/h2>/', '<h2>$1</h2>', $content, 1);
        }
        
        return $content;
    }
    
    private function extract_focus_keyword($title) {
        // Extract main keyword from title (usually first 2-3 meaningful words)
        $words = explode(' ', $title);
        $meaningful_words = array();
        
        $stop_words = array('dan', 'atau', 'yang', 'untuk', 'dalam', 'pada', 'dengan', 'adalah', 'akan', 'telah', 'sudah', 'juga', 'hanya', 'dapat', 'bisa', 'the', 'and', 'or', 'for', 'in', 'on', 'with', 'is', 'will', 'has', 'have', 'can', 'only');
        
        foreach ($words as $word) {
            $word = strtolower(trim($word, '.,!?'));
            if (!in_array($word, $stop_words) && strlen($word) > 2) {
                $meaningful_words[] = $word;
                if (count($meaningful_words) >= 2) break;
            }
        }
        
        return implode(' ', $meaningful_words);
    }
    
    private function generate_related_keywords($focus_keyword) {
        // Generate related keywords based on focus keyword
        $related = array();
        $keyword_parts = explode(' ', $focus_keyword);
        
        // Add location-based related keywords for Indonesian news
        $locations = array('Indonesia', 'Jakarta', 'nasional', 'regional');
        $categories = array('terbaru', 'update', 'informasi', 'berita');
        
        foreach ($keyword_parts as $part) {
            foreach ($locations as $location) {
                $related[] = $part . ' ' . $location;
            }
            foreach ($categories as $category) {
                $related[] = $part . ' ' . $category;
            }
        }
        
        return array_slice($related, 0, 5);
    }
    
    public function extract_keywords($title, $content, $limit = 5) {
        $text = $title . ' ' . strip_tags($content);
        $text = strtolower($text);
        
        // Enhanced stop words untuk Indonesian dan English
        $stop_words = array(
            // English stop words
            'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'cannot', 'this', 'that', 'these', 'those', 'they', 'them', 'their', 'there', 'here', 'where', 'when', 'what', 'who', 'why', 'how', 'then', 'than', 'also', 'only', 'just', 'very', 'much', 'more', 'most', 'some', 'any', 'all', 'each', 'every', 'both', 'either', 'neither', 'one', 'two', 'first', 'last', 'next', 'previous', 'new', 'old', 'good', 'bad', 'big', 'small', 'long', 'short', 'high', 'low', 'up', 'down', 'out', 'over', 'under', 'again', 'further', 'then', 'once',
            
            // Indonesian stop words
            'dan', 'atau', 'tetapi', 'dalam', 'pada', 'di', 'ke', 'untuk', 'dari', 'dengan', 'oleh', 'adalah', 'itu', 'ini', 'yang', 'akan', 'sudah', 'telah', 'sedang', 'dapat', 'bisa', 'harus', 'tidak', 'juga', 'saja', 'hanya', 'semua', 'setiap', 'beberapa', 'sebagai', 'seperti', 'lebih', 'paling', 'sangat', 'tapi', 'kalau', 'jika', 'bila', 'ketika', 'saat', 'waktu', 'tempat', 'dimana', 'kemana', 'darimana', 'bagaimana', 'mengapa', 'kenapa', 'siapa', 'apa', 'mana', 'berapa', 'kapan', 'dimana', 'bagaimana', 'mereka', 'dia', 'ia', 'kami', 'kita', 'saya', 'aku', 'kamu', 'anda', 'beliau', 'nya', 'mu', 'ku', 'kah', 'lah', 'pun', 'per', 'an', 'wan', 'wati', 'man', 'mens'
        );
        
        // Extract potential keywords with better pattern
        preg_match_all('/\b[a-zA-Z]{3,}\b/', $text, $matches);
        $words = $matches[0];
        
        // Filter stop words and short words
        $words = array_filter($words, function($word) use ($stop_words) {
            return !in_array(strtolower($word), $stop_words) && strlen($word) > 2;
        });
        
        // Extract named entities (capitalized words - likely proper nouns)
        $capitalized_pattern = '/\b[A-Z][a-z]{2,}\b/';
        preg_match_all($capitalized_pattern, $title . ' ' . strip_tags($content), $capitalized_matches);
        $named_entities = $capitalized_matches[0];
        
        // Extract location names and proper nouns
        $location_keywords = $this->extract_location_keywords($title . ' ' . $content);
        $organization_keywords = $this->extract_organization_keywords($title . ' ' . $content);
        $event_keywords = $this->extract_event_keywords($title . ' ' . $content);
        
        // Combine and prioritize keywords
        $all_keywords = array_merge($named_entities, $location_keywords, $organization_keywords, $event_keywords);
        
        // Count frequency with priority for named entities
        $word_count = array_count_values($words);
        $entity_count = array_count_values($all_keywords);
        
        // Give higher weight to named entities
        foreach ($entity_count as $entity => $count) {
            $word_count[$entity] = ($word_count[$entity] ?? 0) + ($count * 3);
        }
        
        // Priority patterns for news context
        $news_patterns = array(
            'politik' => 3, 'pemilu' => 3, 'pilkada' => 3, 'presiden' => 3, 'menteri' => 3, 'gubernur' => 3, 'bupati' => 3, 'walikota' => 3,
            'ekonomi' => 3, 'bisnis' => 3, 'saham' => 3, 'rupiah' => 3, 'inflasi' => 3, 'investasi' => 3, 'bank' => 3,
            'olahraga' => 3, 'sepakbola' => 3, 'liga' => 3, 'pemain' => 3, 'pelatih' => 3, 'pertandingan' => 3,
            'teknologi' => 3, 'smartphone' => 3, 'aplikasi' => 3, 'startup' => 3, 'digital' => 3,
            'kesehatan' => 3, 'rumah sakit' => 3, 'dokter' => 3, 'covid' => 3, 'vaksin' => 3,
            'pendidikan' => 3, 'universitas' => 3, 'sekolah' => 3, 'mahasiswa' => 3, 'guru' => 3,
            'hukum' => 3, 'polisi' => 3, 'pengadilan' => 3, 'hakim' => 3, 'jaksa' => 3,
            'bencana' => 3, 'gempa' => 3, 'banjir' => 3, 'tsunami' => 3, 'kebakaran' => 3,
            'transportasi' => 3, 'pesawat' => 3, 'kereta' => 3, 'kapal' => 3, 'bandara' => 3,
            'entertainment' => 2, 'artis' => 2, 'film' => 2, 'musik' => 2, 'konser' => 2
        );
        
        // Apply news context weighting
        foreach ($word_count as $word => $count) {
            $word_lower = strtolower($word);
            foreach ($news_patterns as $pattern => $weight) {
                if (strpos($word_lower, $pattern) !== false) {
                    $word_count[$word] += $weight;
                }
            }
        }
        
        // Sort by frequency/importance
        arsort($word_count);
        
        // Get top keywords and ensure they are contextually relevant
        $top_keywords = array_slice(array_keys($word_count), 0, $limit * 2);
        $contextual_keywords = $this->filter_contextual_keywords($top_keywords, $title, $content);
        
        return array_slice($contextual_keywords, 0, $limit);
    }
    
    private function extract_location_keywords($text) {
        // Indonesian cities and regions
        $locations = array();
        $location_patterns = array(
            // Major Indonesian cities
            '/\b(Jakarta|Surabaya|Bandung|Medan|Semarang|Makassar|Palembang|Tangerang|Depok|Bekasi|Bogor|Batam|Pekanbaru|Bandar Lampung|Malang|Padang|Denpasar|Samarinda|Tasikmalaya|Pontianak|Cimahi|Balikpapan|Jambi|Surakarta|Serang|Yogyakarta|Manado|Cilegon|Mataram|Kendari)\b/i',
            // Provinces
            '/\b(Aceh|Sumatra|Riau|Jambi|Bengkulu|Lampung|Bangka|Belitung|Jakarta|Banten|Jawa Barat|Jawa Tengah|Jawa Timur|Yogyakarta|Bali|Nusa Tenggara|Kalimantan|Sulawesi|Maluku|Papua)\b/i',
            // Countries
            '/\b(Indonesia|Malaysia|Singapura|Thailand|Vietnam|Filipina|Myanmar|Brunei|Laos|Kamboja|Amerika|China|Jepang|Korea|India|Australia|Eropa|Afrika|Arab|Turki)\b/i'
        );
        
        foreach ($location_patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $locations = array_merge($locations, $matches[1]);
        }
        
        return array_unique($locations);
    }
    
    private function extract_organization_keywords($text) {
        $organizations = array();
        $org_patterns = array(
            // Government institutions
            '/\b(DPR|MPR|KPK|Polri|TNI|Kemendikbud|Kemenkes|Kemenkeu|BUMN|BUMD|Pemda|Pemerintah|Kabinet)\b/i',
            // Political parties
            '/\b(PDI-P|Golkar|Gerindra|Demokrat|PKS|PAN|PPP|Hanura|Perindo|PSI|Berkarya)\b/i',
            // Companies (common Indonesian companies)
            '/\b(Pertamina|Telkom|BCA|BRI|BNI|Mandiri|Garuda|Lion Air|Gojek|Tokopedia|Bukalapak|Shopee|Grab|Traveloka)\b/i',
            // International organizations
            '/\b(WHO|UNESCO|UNICEF|IMF|World Bank|ASEAN|G20|PBB|UN|EU|NATO)\b/i'
        );
        
        foreach ($org_patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $organizations = array_merge($organizations, $matches[1]);
        }
        
        return array_unique($organizations);
    }
    
    private function extract_event_keywords($text) {
        $events = array();
        $event_patterns = array(
            // Sports events
            '/\b(Piala Dunia|Olimpiade|Asian Games|Sea Games|Liga Champions|Premier League|Serie A|La Liga|Bundesliga|Euro|Copa America)\b/i',
            // Religious/Cultural events
            '/\b(Ramadan|Idul Fitri|Idul Adha|Nyepi|Imlek|Natal|Paskah|Waisak|Galungan|Kuningan)\b/i',
            // National events
            '/\b(Kemerdekaan|Proklamasi|Sumpah Pemuda|Pancasila|Reformasi|Pilkada|Pemilu|Pilpres)\b/i',
            // International events
            '/\b(KTT|Summit|Konferensi|Conference|Festival|Concert|Expo|Fair|Championship|Tournament)\b/i'
        );
        
        foreach ($event_patterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            $events = array_merge($events, $matches[1]);
        }
        
        return array_unique($events);
    }
    
    private function filter_contextual_keywords($keywords, $title, $content) {
        $filtered = array();
        $title_lower = strtolower($title);
        $content_lower = strtolower($content);
        
        foreach ($keywords as $keyword) {
            $keyword_lower = strtolower($keyword);
            
            // Skip generic words that don't add context
            $generic_words = array('berita', 'news', 'informasi', 'laporan', 'artikel', 'post', 'update', 'terbaru', 'terkini', 'hari', 'tahun', 'bulan', 'minggu', 'jam', 'menit', 'detik', 'kali', 'orang', 'banyak', 'sedikit', 'besar', 'kecil', 'baik', 'buruk', 'bagus', 'jelek');
            
            if (in_array($keyword_lower, $generic_words)) {
                continue;
            }
            
            // Prioritize keywords that appear in title
            $in_title = strpos($title_lower, $keyword_lower) !== false;
            
            // Check keyword density in content
            $keyword_count = substr_count($content_lower, $keyword_lower);
            $total_words = str_word_count($content);
            $density = $total_words > 0 ? ($keyword_count / $total_words) * 100 : 0;
            
            // Include keyword if it's contextually relevant
            if ($in_title || $density > 0.5 || strlen($keyword) > 6) {
                $filtered[] = $keyword;
            }
        }
        
        return $filtered;
    }
    
    public function generate_contextual_search_terms($title, $content, $keywords) {
        $search_terms = array();
        
        foreach ($keywords as $keyword) {
            $base_term = $keyword;
            $contextual_terms = array();
            
            // Add location context if location is mentioned
            $locations = $this->extract_location_keywords($title . ' ' . $content);
            if (!empty($locations)) {
                $contextual_terms[] = $base_term . ' ' . $locations[0];
            }
            
            // Add category context based on content analysis
            $category = $this->detect_news_category($title . ' ' . $content);
            if ($category) {
                $contextual_terms[] = $base_term . ' ' . $category;
            }
            
            // Add temporal context for recent events
            if ($this->is_recent_event($title . ' ' . $content)) {
                $contextual_terms[] = $base_term . ' ' . date('Y');
            }
            
            // Use base term if no context found
            if (empty($contextual_terms)) {
                $contextual_terms[] = $base_term;
            }
            
            $search_terms = array_merge($search_terms, $contextual_terms);
        }
        
        return array_unique($search_terms);
    }
    
    private function detect_news_category($text) {
        $text_lower = strtolower($text);
        
        $categories = array(
            'politik' => array('pemilu', 'pilkada', 'presiden', 'menteri', 'dpr', 'politik', 'pemerintah', 'partai'),
            'olahraga' => array('sepakbola', 'liga', 'pemain', 'pertandingan', 'turnamen', 'olahraga', 'atlet'),
            'ekonomi' => array('ekonomi', 'bisnis', 'saham', 'rupiah', 'bank', 'investasi', 'finansial'),
            'teknologi' => array('teknologi', 'smartphone', 'aplikasi', 'digital', 'internet', 'startup'),
            'kesehatan' => array('kesehatan', 'rumah sakit', 'dokter', 'covid', 'vaksin', 'obat'),
            'pendidikan' => array('pendidikan', 'sekolah', 'universitas', 'mahasiswa', 'guru', 'ujian'),
            'hukum' => array('hukum', 'polisi', 'pengadilan', 'hakim', 'jaksa', 'kejahatan'),
            'bencana' => array('gempa', 'banjir', 'tsunami', 'kebakaran', 'bencana', 'korban')
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
        $recent_indicators = array(
            'hari ini', 'kemarin', 'tadi', 'baru saja', 'terbaru', 'terkini', 
            'breaking news', 'update', $current_year, 'minggu ini', 'bulan ini'
        );
        
        $text_lower = strtolower($text);
        foreach ($recent_indicators as $indicator) {
            if (strpos($text_lower, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
?>