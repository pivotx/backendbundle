<?php
namespace PivotX\Backend\Component\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;

class ResourceToFieldTransformer implements DataTransformerInterface
{
    /**
     * @var Doctrine
     */
    private $entity_manager;

    /**
     * @param ObjectManager $om
     */
    public function __construct(ObjectManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * Transforms a json (appValue) to a string (..).
     *
     * @param  mixed $appValue
     * @return string
     */
    public function transform($appValue)
    {
        /*
        echo 'transforming appValue<br/>'."\n";
        var_dump($appValue); echo "<br/>\n";
        //*/

        if ($appValue instanceof \PivotX\CoreBundle\Entity\GenericResource) {
            return $appValue;
        }

        $data = json_decode($appValue);

        if (is_array($data)) {
            $id = $data[0]->id;

            $image = $this->entity_manager->getRepository('PivotX\CoreBundle\Entity\GenericResource')->find($id);
            if ($image instanceof \PivotX\CoreBundle\Entity\GenericResource) {
                return $image;
            }
        }

        return null;
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $clientValue
     * @return string|null
     */
    public function reverseTransform($clientValue)
    {
        /*
        echo 'transforming clientValue<br/>'."\n";
        var_dump($clientValue); echo "<br/>\n";
        //*/

        if (is_array($clientValue) && isset($clientValue['filesinfo'])) {
            $data = json_decode($clientValue['filesinfo']);

            if (is_array($data) && (count($data) >= 1) && isset($data[0]->tmp_name)) {
                $image = new \PivotX\CoreBundle\Entity\LocalEmbedResource;
                $image->createNewResourceForField($data[0]);

                $title = $image->getFilename();
                $title = preg_replace('|[^a-zA-Z0-9_ .-]|', '', $title);
                $title = preg_replace('|[ _]|',' ',$title);
                $title = preg_replace('|(.+)[.]([a-zA-Z0-9]+)|', '\\1', $title);
                $title = ucfirst(trim($title));

                // @todo this inits should not be here
                $image->setDateCreated(new \DateTime);
                $image->setDateModified(new \DateTime);
                $image->setTitle($title);
                $image->setAuthor('');

                $this->entity_manager->persist($image);
                $this->entity_manager->flush();
                return $image;
            }

            if (is_array($data) && (count($data) >= 1) && isset($data[0]->id)) {
                $id = $data[0]->id;

                $image = $this->entity_manager->getRepository('PivotX\CoreBundle\Entity\GenericResource')->find($id);
                if ($image instanceof \PivotX\CoreBundle\Entity\GenericResource) {
                    return $image;
                }
            }
        }

        return null;
    }
}
