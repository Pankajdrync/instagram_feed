<?php

namespace Drupal\instagram_feed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Cache\UncacheableDependencyTrait;
/**
 * Provides a 'Instagram Feed' Block.
 *
 * @Block(
 *   id = "instagram_block",
 *   admin_label = @Translation("Instagram Feed block"),
 *   category = @Translation("Instagram Feed block"),
 * )
 */
class InstagramBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    // Get count and access tokens.
    $count = \Drupal::config('instagram_tokens.settings')->get('feed_count');
    $access_token = \Drupal::config('instagram_tokens.settings')->get('long_token');

    $output .= '';

    // Prepare the URL to fetch data.
    $fetch_url = 'https://graph.instagram.com/me/media';
    $query = [
      'fields' => 'permalink,id,caption,media_url,media_type,thumbnail_url',
      'access_token' => $access_token,
      'limit' => $count,
    ];

    $url = \Drupal\Core\Url::fromUri($fetch_url, ['query' => $query])->toString();

    // Fetch data.
    $jsonData = json_decode(file_get_contents($url));
    // Prepare output.
    $output = '';
    $output .= '<div class="insta-feed-outer">';
    $output .= '<div class="insta-feed-items">';
    foreach ($jsonData->data as $post) {
      if ($post->media_type == 'IMAGE') {
        $permalink = $post->permalink;
        $pic_src = $post->media_url;
        $output .= '<div class="col-md-4 item_box">';
        $output .= '<a href="' . $permalink . '"" target="_blank">';
        $output .= '<img class="img-responsive photo-thumb" src="' . $pic_src . '">';
        $output .= '</a>';
        $output .= '</div>';
      }
      if ($post->media_type == 'VIDEO') {
        $permalink = $post->permalink;
        $pic_src = $post->thumbnail_url;
        $output .= '<div class="col-md-4 item_box">';
        $output .= '<a href="' . $permalink . '"" target="_blank">';
        $output .= '<img class="img-responsive photo-thumb" src="' . $pic_src . '">';
        $output .= '</a>';
        $output .= '</div>';
      }
    }
    $output .= '</div>';
    $output .= '</div>';

    return [
        '#type' => 'markup',
        '#children' => $output,
        '#attached' => [
          'library' => ['instagram_feed/instagram_feed'],
        ],
        '#cache' => [
          'max-age' => 0,
        ],
    ];
  }

  public function getCacheMaxAge() {
    return 0;
  }
}