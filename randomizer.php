<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;
use Grav\Common\Plugin;
use Grav\Common\Page\Collection;
use Grav\Common\Uri;
use Grav\Common\Taxonomy;

/**
 * Class RandomizerPlugin
 * @package Grav\Plugin
 */
class RandomizerPlugin extends Plugin
{
     /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents(): array
    {
    return [
        'onPluginsInitialized' => [
            ['autoload', 100000], // TODO: Remove when plugin requires Grav >=1.7
            ['onPluginsInitialized', 0]
        ]
    ];
    }
    
    /**
     * Composer autoload.
     *
     * @return ClassLoader
     */
    public function autoload(): ClassLoader
    {
        return require __DIR__ . '/vendor/autoload.php';
    }

    public function onPluginsInitialized(): void
    {
        // don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        /** @var Uri $url */
        // get the current URI
        $uri = $this->grav['uri'];
        $config = $this->config(); // for convenience, get the config obj

        // get the configured route        
        $route = $config['route'] ?? null;
        // if the URI matches the route,
        if ($route && $route == $uri->path()) {
            // start listening for the onPageInitialized event
            // this helps ensure we only run code when everything is
            // properly set up
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0]
            ]);
        }
    }

    /**
     * send user to random page
    */    
    public function onPageInitialzed() : void
    {
        /** @var Taxonomy $uri */
        // get the taxonomy object
        $taxonomy_map = $this->grav['taxonomy'];
        // retrieve the config object
        $config = $this->config();

        // get the filters from the plugin config
        // this should be, in this case, an array of one item:
        // category: blog
        $filters = (array)($config['filters'] ?? []);
        // this is the logical operation we'll use to find relevant pages
        // the default is logical and, but we defer to the plugin config
        $operator = $config['filter_combinator'] ?? 'and';

        if (count($filters) > 0)) {
            // create a collection to hold our filters
            $collection = new Collection();
            // add all pages that match the filter to the collection
            $collection->append($taxonomy_map->findTaxonomy($filters, $operator)->toArray());
            // only proceed if we find matching pages
            if (count($collection) > 0) {
                // we change to random page
                // this is done by unsetting the current page...
                unset($this->grav['page']);
                //...then replacing it with a random page in the collection                
                $this->grav['page'] = $collection->random()->current();
            }
        }
    }
}