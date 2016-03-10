<?php
/**
 * Extension of the HtmlEditorField_Toolbar to add the ability
 * to link to DataObjects on the site
 *
 * @package subsites
 */
class HtmlEditorField_LinkObjects extends DataObject
{

    /**
     * The linkable_objects array stores a list of all the objects which should be able to be linked to.
     * Each row should take the format of 'Data Object Class Name' => 'Field Title'
     */
    private static $linkable_objects = array();

    private static $linkable_interface = 'Linkable';

    /**
     * The construct function can optionally take an array of objects to add to $linkable_objects
     * and a boolean variable which decides whether to overwrite the $linkable_objects field
     * or simply add the array values to it.
     */
    public function __construct($objects = null, $overwrite = false)
    {
        // Check if $objects is an array first
        if (is_array($objects)) {
            // If the overwrite variable is set
            if ($overwrite) {
                // Replace $linkable_objects with the passed array
                self::$linkable_objects = $objects;
            } else {
                // Otherwise, merge the arrays together
                self::$linkable_objects = array_merge(self::$linkable_objects, $objects);
            }
        }
    }

    /**
     * This function will add an object to the array of Linkable objects.
     * It will throw an Exception if the class doesn't exist or it doesn't
     * implement the Linkable interface.
     * 
     * @return boolean
     */
    public static function addLinkableObject($obj, $title = null)
    {
        // First, check that the class exists and that it implements the Linkable interface,
        // which ensure that the class has a Link() function to call on
        if (class_exists($obj) && in_array(self::$linkable_interface, class_implements($obj))) {
            // Then, check if a Title was passed. If it wasn't, just use the objects class name.
            $title = ($title && $title != '') ? $title : $obj;
            // Add the object to the array as 'Class Name' => 'Field Title'
            self::$linkable_objects[$obj] = $title;
            return true;
        }
        throw new InvalidArgumentException($obj . " is not a class or does not implement the 'Linkable' interface");
    }

    /**
     * This function tries to remove an object from the linkable_objects array,
     * and returns true or false depending on its success.
     *
     * @return boolean
     */
    public static function removeLinkableObject($obj)
    {
        if (in_array($obj, self::$linkable_objects)) {
            unset(self::$linkable_objects[$obj]);
            return true;
        }
        return false;
    }

    /**
     * A function to simply return the array of linkable objects
     *
     * @return array
     */
    public function getLinkableObjects()
    {
        return self::$linkable_objects;
    }

    public static function object_link($arguments, $content = null, $parser = null)
    {
        if (!isset($arguments['id']) || !is_numeric($arguments['id']) || !isset($arguments['type'])) {
            return;
        }

        // Get not found error page to link to in case of failure
        $errorPage = DataObject::get_one('ErrorPage', '"ErrorCode" = \'404\'');

        // get the document file 
        $document = DataObject::get_by_id($arguments['type'], $arguments['id']);

        if (!$document && !$errorPage) {
            return;
        } // we have nothing meaningful to return

        if ($content) {
            throw new Exception('This handler is not implemented.');
        } elseif (!$document) {
            return $errorPage->Link();
        } else {
            // Warning: medium-evil shortcode injection
            return $document->Link();
        }
    }
}
