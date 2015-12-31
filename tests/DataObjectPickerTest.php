<?php

class DataObjectPickerTest extends FunctionalTest
{

    public static $fixture_file = 'dataobjectpicker/tests/DataObjectPickerTest.yml';

    protected $extraDataObjects = array(
        'DogOwner',
        'Dog',
    );

    public function testFieldWithMagicAndValue()
    {
        $dog = $this->objFromFixture('Dog', 'Bello');
        $dogcontroller = new DogController();
        $dogform = $dogcontroller->getEditForm();
        $dogform->loadDataFrom($dog);
        $field = $dogform->Fields()->fieldByName('OwnerID');
        $this->assertContains('Wolfgang', $field->FieldHolder(), 'Parent DataObject found.');
    }

    public function testSuggestions()
    {
        $field = new DataObjectPicker('OwnerID', "Owner");
        $field->setConfig('classToPick', 'DogOwner');
        $suggestions = $field->Suggest(new SS_HTTPRequest('GET', 'someurl', array('request' => 'Ma')));
        $this->assertContains('Martin', $suggestions, 'Suggest Martin.');
        $this->assertContains('Mateusz', $suggestions, 'Suggest Mateusz.');
        $this->assertNotContains('Wolfgang', $suggestions, 'Do not suggest Wolfgang.');
    }

    public function testMakeReadonly()
    {
        $field = new DataObjectPicker('OwnerID', "Owner");
        $field->setConfig('classToPick', 'DogOwner');
        $field = $field->performReadonlyTransformation();

        $this->assertContains('readonly', $field->Field(), 'Perform readonly transformation.');
    }
}

class DogOwner extends DataObject implements TestOnly
{

    public static $db = array(
        'Name' => 'Varchar',
    );

    public static $has_many = array(
        'Dogs' => 'Dog',
    );

    public static $summary_fields = array(
        'Name' => 'Name',
    );
}

class Dog extends DataObject implements TestOnly
{

    public static $db = array(
        'Name' => 'Varchar',
    );

    public static $has_one = array(
        'Owner' => 'DogOwner',
    );
    
    public static $summary_fields = array(
        'Name',
    );

    public function getCMSFields()
    {
        return new FieldList(new DataObjectPicker('OwnerID', "Owner"));
    }
}

class DogController extends Controller implements TestOnly
{

    public function getEditForm($id = null)
    {
        return new Form($this, 'getEditForm', new FieldList(new DataObjectPicker('OwnerID', "Owner")), new FieldList());
    }

    public function Link()
    {
        return 'DogController';
    }
}
