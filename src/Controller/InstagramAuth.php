<?php

namespace Drupal\instagram_feed\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for Instagram Auth Controller
 */
class InstagramAuth extends ControllerBase {

  protected $config;
  protected $request;
  protected $code;
  protected $messenger;
  protected $http_client;
  protected $time_var;

  public function __construct(ConfigFactoryInterface $config, Request $request, Messenger $messenger, ClientInterface $httpClient, $time_var) {
      $this->config = $config;
      $this->messenger = $messenger;
      $this->http_client = $httpClient;
      $this->time_var = $time_var;
      $this->request = $request;
      if ($request->query->get('code')) {
        $this->code = rtrim($request->query->get('code'), '#');
      }
    }

  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('request_stack')->getCurrentRequest(), $container->get('messenger'), $container->get('http_client'), $container->get('datetime.time'));
  }

  public function authHandler() {
    if (isset($this->code)) {
      // If we have code, send a post request to get short lived code.

      // Config object
      $insta_config = $this->config->getEditable('instagram_tokens.settings');
      $insta_config_read = \Drupal::config('instagram_tokens.settings');

      // Prepare data for post.
      $post_data = [
        'client_id' => $insta_config_read->get('app_id'),
        'client_secret' => $insta_config_read->get('app_secret'),
        'code' => $this->code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $insta_config_read->get('redirect_uri'), // This should be same as the one passed when doing authorization.
      ];

      \Drupal::logger('post_Data')->notice(print_r($post_data, 1));

      $request = $this->http_client->post('https://api.instagram.com/oauth/access_token', [
        'form_params' => $post_data,
        'headers' => [
          'Content-type' => 'application/x-www-form-urlencoded',
        ],
      ]);

      $response = json_decode($request->getBody());

      if (!empty($response->access_token)) {
        $this->messenger->addMessage($this->t('Short token refreshed successfully.'));

        $short_token = $response->access_token;

        // Save the short live token in config.
        $insta_config->set('short_token', $short_token)->save();

        $get_data = [
          'grant_type' => 'ig_exchange_token',
          'client_secret' => $insta_config_read->get('app_secret'),
          'access_token' => $short_token,
        ];
        $query_string = implode('&', $get_data);
        // Once we have short token, we need to get long lived token.

        $url = \Drupal\Core\Url::fromUri('https://graph.instagram.com/access_token', ['query' => $get_data])->toString();

        $request = $this->http_client->request('GET', $url);

        $response = json_decode($request->getBody());
        \Drupal::logger('second_request')->notice(print_r($response, 1));
        if (!empty($response->access_token)) {
          $long_token = $response->access_token;
          $this->messenger->addMessage($this->t('Long token refreshed successfully.'));
          // Save the long lived token in the config.
          $insta_config->set('long_token', $long_token)->save();
          $expires_in = $response->expires_in;

          $expires = $this->time_var->getRequestTime() + $expires_in;
          $insta_config->set('expires', \Drupal::service('date.formatter')->format($expires, 'custom', 'F d,Y - h:i A'))->save();
        }
        else {
          $this->messenger->addError($this->t('Error in refreshing long token.'));
        }
      }
      else {
        $this->messenger->addError($this->t('Error in refreshing short token.'));
      }
    }
    else {
      $this->messenger->addError($this->t('Error in receiving authorization code from Instagram. Please try again later.'));
    }

    // Redirect the user to admin page.
    return $this->redirect('instagram_feed.token_refresh');
  }
}
