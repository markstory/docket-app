<?php
/**
 * Sluggable Behavior class file.
 *
 * @filesource
 * @author Mariano Iglesias
 * @link http://cake-syrup.sourceforge.net/ingredients/sluggable-behavior/
 * @version $Revision$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app
 * @subpackage app.models.behaviors
 * @revision $Revision$
 */
namespace App\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Log\LogTrait;
use Cake\Utility\Text;

/**
 * Model behavior to support generation of slugs for models.
 */
class SluggableBehavior extends Behavior
{
    use LogTrait;

    /**
     * Initiate behavior for the model using specified settings. Available settings:
     *
     * - label: (array | string, optional) set to the field name that contains the
     * string from where to generate the slug, or a set of field names to
     * concatenate for generating the slug. DEFAULTS TO: title
     *
     * - slug: (string, optional) name of the field name that holds generated slugs.
     * DEFAULTS TO: slug
     *
     * - separator: (string, optional) separator character / string to use for replacing
     * non alphabetic characters in generated slug. DEFAULTS TO: -
     *
     * - length:(integer, optional) maximum length the generated slug can have.
     * DEFAULTS TO: 100
     *
     * - overwrite: (boolean, optional) set to true if slugs should be re-generated when
     * updating an existing record. DEFAULTS TO: false
     *
     * - reserved: (string[]) A list of values that cannot be used as a slug.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'label' => array('title'),
        'slug' => 'slug',
        'separator' => '-',
        'length' => 100,
        'overwrite' => true,
        'reserved' => [],
        'implementedMethods' => [
            'slug' => 'slug'
        ]
    ];

    /**
     * Run before a model is saved, used to set up slug for model.
     */
    function beforeSave(Event $event, $entity)
    {
        // Make label fields an array
        if (!is_array($this->_config['label'])) {
            $this->_config['label'] = array($this->_config['label']);
        }

        $alias = $this->_table->getAlias();
        $pk = (array)$this->_table->getPrimaryKey();
        $pk = $pk[0];

        // See if we should be generating a slug
        if ($this->getConfig('overwrite') || $entity->isNew()) {
            // Build label out of data in label fields, if available, or using a default slug otherwise
            $label = '';
            foreach ($this->getConfig('label') as $field) {
                $label .= $entity->get($field);
            }

            // Keep on going only if we've got something to slug
            if (empty($label)) {
                $this->log('No label, skipping slug generation', 'debug');
                return;
            }

            // Get the slug
            $slug = $this->slug($label, $this->getConfig());

            // Look for slugs that start with the same slug we've just generated
            $conditions = array($alias . '.' . $this->getConfig('slug') . ' LIKE' => $slug . '%');

            if (!empty($entity[$pk])) {
                $conditions[$alias . '.' . $pk . ' !='] = $entity[$pk];
            }

            $result = $this->_table->find('all', array(
                'conditions' => $conditions,
                'fields' => array($pk, $this->getConfig('slug')),
            ));
            $sameUrls = $result->extract($this->getConfig('slug'))->toArray();

            $accepted = !empty($sameUrls) || !in_array($slug, $this->getConfig('reserved'));

            // If we have collisions
            if (!$accepted) {
                $begginingSlug = $slug;
                $index = 1;

                // Attach an ending incremental number until we find a free slug

                while ($index > 0) {
                    if (!in_array($begginingSlug . $this->getConfig('separator') . $index, $sameUrls)) {
                        $slug = $begginingSlug . $this->getConfig('separator') . $index;
                        $index = -1;
                    }

                    $index++;
                }
            }

            // Now set the slug as part of the model data to be saved.
            $entity->set($this->getConfig('slug'), $slug, ['guard' => false]);
        }
    }

    /**
     * Generate a slug for the given string using specified settings.
     *
     * @param string $string String from where to generate slug
     * @param array $settings Settings to use (looks for 'separator' and 'length')
     * @return string Slug for given string
     * @access private
     */
    public function slug($string, $settings)
    {
        return Text::slug($string);
    }
}
