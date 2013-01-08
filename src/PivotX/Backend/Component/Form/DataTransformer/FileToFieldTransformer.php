<?php
namespace PivotX\Backend\Component\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;

class FileToFieldTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    //private $om;

    /**
     * @param ObjectManager $om
     */
    public function __construct()
    {
        //$this->om = $om;
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

        return array(
            'valid' => true,
            'mimetype' => false,
            'size' => false,
            'name' => $appValue
        );

        return $appValue;
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

        return $clientValue['filesinfo'];
    }
}
