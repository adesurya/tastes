<?php
/**
 * Content Parser for AI Rewriter
 * Handles prompt generation and content parsing
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Content_Parser {
    private $language = 'Indonesian';
    private $writing_style = 'professional';
    
    public function set_language($lang) {
        $this->language = $lang;
    }
    
    public function set_writing_style($style) {
        $this->writing_style = $style;
    }
    
    public function generate_prompt($title, $content, $custom_prompt = '', $instructions = '') {
        $clean_content = wp_strip_all_tags($content);
        $clean_content = preg_replace('/\s+/', ' ', $clean_content);
        
        if (!empty($custom_prompt)) {
            $prompt = str_replace(
                array('{title}', '{content}'),
                array($title, $clean_content),
                $custom_prompt
            );
        } else {
            $prompt = $this->get_default_prompt($title, $clean_content);
        }
        
        if (!empty($instructions)) {
            $prompt .= "\n\nAdditional Instructions: " . $instructions;
        }
        
        return $prompt;
    }
    
    private function get_default_prompt($title, $content) {
        return "Rewrite the following article in {$this->language} with a {$this->writing_style} tone. 
        
Title: {$title}

Content:
{$content}

Please provide a completely rewritten version with:
1. A new engaging title
2. Fresh perspective and wording
3. Maintain the core message and key information
4. Use natural language and flow
5. Format with proper paragraphs

Response format:
TITLE: [new title]
CONTENT: [rewritten content]";
    }
    
    public function parse_rewritten_content($content) {
        $title = '';
        $body = $content;
        
        // Try to extract title
        if (preg_match('/TITLE:\s*(.+?)(?=CONTENT:|$)/s', $content, $matches)) {
            $title = trim($matches[1]);
            $body = preg_replace('/TITLE:\s*.+?CONTENT:\s*/s', '', $content);
        } elseif (preg_match('/^(.+?)\n\n/s', $content, $matches)) {
            $title = trim($matches[1]);
            $body = substr($content, strlen($matches[0]));
        }
        
        $body = str_replace('CONTENT:', '', $body);
        $body = trim($body);
        
        if (empty($title)) {
            $title = $this->extract_title_from_content($body);
        }
        
        return array(
            'title' => $title,
            'content' => $body
        );
    }
    
    private function extract_title_from_content($content) {
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, 2);
        return !empty($sentences[0]) ? substr($sentences[0], 0, 100) : 'Rewritten Article';
    }
    
    public function format_for_wordpress($content) {
        $paragraphs = explode("\n\n", $content);
        $formatted = '';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            $formatted .= '<p>' . nl2br(esc_html($paragraph)) . '</p>' . "\n";
        }
        
        return $formatted;
    }
    
    public function extract_keywords($title, $content, $count = 5) {
        $text = strtolower($title . ' ' . wp_strip_all_tags($content));
        
        $stopwords = array('the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'but', 'in', 'with', 'to', 'for', 'of', 'as', 'by', 'that', 'this', 'it', 'from', 'be', 'are', 'was', 'were', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should');
        
        $words = str_word_count($text, 1);
        $words = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        $word_counts = array_count_values($words);
        arsort($word_counts);
        
        return array_slice(array_keys($word_counts), 0, $count);
    }
}