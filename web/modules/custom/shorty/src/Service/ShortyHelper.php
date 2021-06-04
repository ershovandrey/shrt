<?php

namespace Drupal\shorty\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\State\StateInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\shorty\Entity\Shorty;
use Drupal\shorty\ShortyInterface;

/**
 * ShortyHelper service.
 */
class ShortyHelper {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Entity Storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The path_alias.manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected AliasManagerInterface $pathAliasManager;

  /**
   * The path.validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected PathValidatorInterface $pathValidator;

  /**
   * Constructs a ShortyHelper object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\path_alias\AliasManagerInterface $path_alias_manager
   *   The path_alias.manager service.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path.validator service.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AliasManagerInterface $path_alias_manager, PathValidatorInterface $path_validator) {
    $this->state = $state;
    $this->config = $config_factory->get('shorty.settings');
    $this->entityStorage = $entity_type_manager->getStorage('shorty');
    $this->languageManager = $language_manager;
    $this->pathAliasManager = $path_alias_manager;
    $this->pathValidator = $path_validator;
  }

  /**
   * Validate a long URL
   *
   * @param
   * $long url - the long URL entered by user
   *
   * @return bool
   *  TRUE if valid, FALSE if invalid
   */
  public function validateLongUrl(&$long_url): bool {
    $return = TRUE;

    // if the person didn't remove the original http:// from the field, pull it out
    $long_url = preg_replace('!^http\://(http\://|https\://)!i', '\\1', $long_url);
    $long_parse = parse_url($long_url);

    if ($long_parse === FALSE || !isset($long_parse['host'])) {
      // malformed URL or no host in the URL
      $return = FALSE;
    }
    elseif ($long_parse['scheme'] !== 'http' && $long_parse['scheme'] !== 'https') {
      $return = FALSE;
    }

    return $return;
  }

  /**
   * Validate short URL.
   *
   * @param string $url
   *  Short URL to validate.
   *
   * @return bool
   *   TRUE if short URL is valid, FALSE otherwise.
   */
  public function validateShortUrl(string $url): bool {
    // check the length of the url
    if ($url === '') {
      return FALSE;
    }
    // disallow: #%&@*{}\:;<>?/+.,'"$|`^[] and space character
    return !preg_match('/[\/#%&\@\*{}\\:\;<>\?\+ \.\,\'\"\$\|`^\[\]]/u', $url);
  }

  /**
   * Return next available short URL
   *
   * @return string
   *   Short path.
   */
  public function getNextShortUrl(): string {
    $count = $this->state->get('shorty_counter', 3); // starts the URLs with 3 characters
    do {
      $count++;
      // counter is stored as base 10
      // $index is a-z, A-Z, 0-9, sorted randomly, with confusing characters (01lIO) removed - 57 characters
      // a custom index can be created as a variable override in settings.php
      $index = $this->config->get('shorty_index');
      $str = $this->dec2any($count, 0, $index);

      // check that this string hasn't been used already
      // check that the string is a valid (available) path
    } while ($this->shortUrlExists($str) !== FALSE || $this->isShortUrlAvailable($str) === FALSE);

    $this->state->set('shorty_counter', $count);

    return $str;
  }


  /**
   * Check to see if this short URL already exists
   *
   * @param string $short
   *   Short URL.
   * @param null $long
   *   Long URL.
   *
   * @return false|string
   *  FALSE if not found, 'found' if found and 'match' if short === long.
   */
  private function shortUrlExists(string $short, $long = NULL) {
    try {
      $shorty = $this->getShortyByShortUrl($short);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return FALSE;
    }
    if (!$shorty) {
      return FALSE;
    }

    $return = 'found';
    if (
      $long &&
      $shorty instanceof ShortyInterface &&
      $shorty->getDestinationUrl()->toString() === $long
    ) {
      $return = 'match';
    }
    return $return;
  }


  /**
   * Get Shorty by Short URL.
   *
   * @param string $short_url
   *   Short URL.
   * @param bool $get_only_active
   *   Set to TRUE to return only active shorties.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Shorty entity or FALSE if not found
   */
  public function getShortyByShortUrl(string $short_url, bool $get_only_active = FALSE) {
    $query = $this->entityStorage->getQuery();
    $query->condition('source', $short_url);
    if ($get_only_active) {
      $query->condition('status', Shorty::SHORTY_STATUS_ACTIVE);
    }
    $ids = $query->accessCheck(FALSE)->execute();
    $result = $this->entityStorage->loadMultiple($ids);
    return reset($result);
  }

  /**
   * Checks to see if there's a menu handler, path alias, or language prefix for a given path
   *
   * @return bool
   *   TRUE if there are no conflicts, FALSE otherwise.
   */
  private function isShortUrlAvailable(string $path): bool {
    // check to see if path represents an enabled language
    $languages =  $this->languageManager->getLanguages();
    if (array_key_exists($path, $languages)) {
      return FALSE;
    }

    $return = TRUE;

    // see if $path is an alias
    $source = $this->pathAliasManager->getAliasByPath('/'.$path);
    if ($source !== $path) {
      // if so, set alias source to $path
      $path = $source;
    }

    $url_object = $this->pathValidator->getUrlIfValid($path);

    if ($url_object) {
      $return = FALSE;
    }

    return $return;
  }

  /**
   * From http://www.php.net/manual/en/function.base-convert.php#52450
   *
   * Parameters:
   * $num - your decimal integer
   * $base - base to which you wish to convert $num (leave it 0 if you are providing $index or omit if you're using default (62))
   * $index - if you wish to use the default list of digits (0-1a-zA-Z), omit this option, otherwise provide a string (ex.: "zyxwvu")
   */
  private function dec2any(int $num, int $base = 62, string $index = ''): string {
    if (!$base) {
      $base = strlen($index);
    }
    elseif ($index === '') {
      // note: we could rearrange this string to get more random looking URLs
      // another note, to create printable URLs, omit the following characters: 01lIO
      $index = substr("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", 0, $base);
    }
    $out = "";
    for ( $t = floor( log10( $num ) / log10( $base ) ); $t >= 0; $t-- ) {
      $a = floor( $num / ($base ** $t));
      $out .= $index[$a];
      $num -= ($a * ($base ** $t));
    }
    return $out;
  }

}
