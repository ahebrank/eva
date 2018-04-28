<?php

namespace Drupal\eva;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;

class TokenHandler {

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Undocumented function
   *
   * @param Token $token
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token')
    );
  }

  /**
   * Get view arguments array from string that contains tokens.
   *
   * @param string $string
   *   The token string defined by the view.
   * @param string $type
   *   The token type.
   * @param object $object
   *   The object being used for replacement data (typically a node).
   *
   * @return array
   *   An array of argument values.
   */
  function get_arguments_from_token_string($string, $type, $object) {
    $args = trim($string);
    if (empty($args)) {
      return [];
    }
    $args = $this->token->replace($args, [$type => $object], ['sanitize' => FALSE]);
    return explode('/', $args);
  }

}