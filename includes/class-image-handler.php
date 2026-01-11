<?php
/**
 * Image Handler for AI Rewriter
 * Handles image search and upload
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Image_Handler {
    private $source = 'google';
    
    public function __construct() {
        $this->source = get_option('ai_rewriter_image_source', 'google');
    }
    
    public function search_and_upload_image($keyword, $post_id) {
        try {
            $image_url = $this->search_image($keyword);
            
            if (!$image_url) {
                return false;
            }
            
            return $this->upload_to_media_library($image_url, $keyword, $post_id);
            
        } catch (Exception $e) {
            error_log('Image handler error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function search_image($keyword) {
        if ($this->source === 'google') {
            return $this->search_google_image($keyword);
        } elseif ($this->source === 'pexels') {
            return $this->search_pexels_image($keyword);
        }
        
        return false;
    }
    
    private function search_google_image($keyword) {
        $api_key = get_option('ai_rewriter_google_api_key', '');
        $search_engine_id = get_option('ai_rewriter_google_search_engine_id', '');
        
        if (empty($api_key) || empty($search_engine_id)) {
            return false;
        }
        
        $url = add_query_arg(array(
            'key' => $api_key,
            'cx' => $search_engine_id,
            'q' => urlencode($keyword),
            'searchType' => 'image',
            'num' => 1,
            'imgSize' => 'large',
            'safe' => 'active'
        ), 'https://www.googleapis.com/customsearch/v1');
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['items'][0]['link'] ?? false;
    }
    
    private function search_pexels_image($keyword) {
        $api_key = get_option('ai_rewriter_pexels_api_key', '');
        
        if (empty($api_key)) {
            return false;
        }
        
        $url = add_query_arg(array(
            'query' => urlencode($keyword),
            'per_page' => 1,
            'orientation' => 'landscape'
        ), 'https://api.pexels.com/v1/search');
        
        $response = wp_remote_get($url, array(
            'headers' => array('Authorization' => $api_key),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data['photos'][0]['src']['large'] ?? false;
    }
    
    private function upload_to_media_library($image_url, $keyword, $post_id) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => sanitize_file_name($keyword . '.jpg'),
            'tmp_name' => $tmp
        );
        
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($tmp);
            return false;
        }
        
        return $id;
    }
}