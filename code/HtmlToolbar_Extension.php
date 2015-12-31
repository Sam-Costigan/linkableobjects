<?php
/**
 * Extension of the HtmlEditorField_Toolbar to add the ability
 * to link to DataObjects on the site
 *
 * @package subsites
 */
class HtmlToolbar_Extension extends DataExtension
{

    public function updateLinkForm($form)
    {
        Requirements::javascript("linkableobjects/javascript/CustomHtmlEditorField.js");

        $count = 0;
        foreach ($form->Fields() as $field) {
            $count++;
            if ($count == 2) {
                $linkType = $field->fieldByName('LinkType');
                $types = $linkType->getSource();

                $link = new HtmlEditorField_LinkObjects();
                $linkableObjects = $link->getLinkableObjects();

                foreach ($linkableObjects as $object => $title) {
                    $types[$object] = $title;

                    $picker = new DataObjectPicker($object . 'LinkID', $title);
                    $picker->setConfig('limit', 5);
                    $picker->setConfig('classToPick', $object);
                    $picker->setForm($form);
                    $field->insertBefore($picker, 'Description');
                }

                $linkMap = new HiddenField('LinkableObjects');
                $linkMap->setAttribute('data-map', json_encode($linkableObjects));

                $field->push($linkMap);

                $linkType->setSource($types);
            }
        }
    }
}
