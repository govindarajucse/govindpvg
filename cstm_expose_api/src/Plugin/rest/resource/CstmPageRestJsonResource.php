<?php
 
namespace Drupal\cstm_expose_api\Plugin\rest\resource;
 
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Component\Serialization\Json;
 
/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "expose_page_rest_resource",
 *   label = @Translation("Expose Page JSON Rest Resource"),
 *   uri_paths = {
 *     "canonical" = "/page_json/{jsonapikey}/{nid}"
 *   }
 * )
 */
class CstmPageRestJsonResource extends ResourceBase implements ContainerFactoryPluginInterface {
 
  /**
   * A current user instance.
   *
   * @var AccountProxyInterface
   */
  protected $currentUser;
  
  public $config;
 
    /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
 
  /**
   * Constructs a new CstmPageRestJsonResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param LoggerInterface $logger
   *   A logger instance.
   * @param AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    ConfigFactory $config, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    
    $this->entityTypeManager = $entity_type_manager; //The entity type manager service.
    $this->currentUser = $current_user;
    $this->config = $config;
    
  }
 
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('cstm_expose_api'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }
 
  /**
   * Responds to GET requests.
   *
   * @param EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws HttpException
   *   Throws exception expected.
   */
  public function get($jsonapikey, $nid) {
    $message = [
      'status' => 404, 
      'message' => 'Ohh ! you do not have access to this page'
    ];
    $config = $this->config->get('system.site');
    $api_key = $config->get('siteapikey');
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($nid);
    $build = array(
      '#cache' => array(
        'max-age' => 0,
      ),
    );
    /**
     * Check the current nid is node or not
     * If not shows the access denied message.
     */
    if ($node instanceof \Drupal\node\NodeInterface) {
      /**
       * Check jsonapikey and apikey matching or not.
       * Check node type is page or not.
       * Check current user has permssion to access the content or not.
       * if all matches then show the page content to user. 
       */  
      if ($jsonapikey == $api_key && $node->getType() == 'page' && $this->currentUser->hasPermission('access content')) {
        return new ResourceResponse($node, 200); // Return specific node as in json format.
      }
      else {
        // If condition has not met, it will show access denied.
        return (new ResourceResponse($message))->addCacheableDependency($build);
      }
    }
    else {
      // If condition has not met, it will show access denied.
      return (new ResourceResponse($message))->addCacheableDependency($build);
    }
  }
 
}