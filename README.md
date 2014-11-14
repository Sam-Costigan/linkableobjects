# LINKABLE OBJECTS

## Maintainer Contact
 * Sam Costigan <sam (at) stripetheweb (dot) com>

## Description

Add your custom Data Objects to the HTML Editor Field link functionality, with a Dropdown field that is populated by relevant results as the user searches.

## Requirements
 * SilverStripe 3.0 or newer

## Setup

To set up a DataObject to be linkable, first it needs to implement the Linkable interface. There are two requirements for a Linkable DataObject:
 * a Link() function which will return a relevant URL to display the Data Object.
 * a LinkTitle() function which will return a title string to display when searching for Data Objects.

The Link() function will need to return a relevant URL so that the Data Object will be displayed. For more information on how to do this, see http://www.ssbits.com/tutorials/2010/dataobjects-as-pages-part-1-keeping-it-simple/

When searching for DataObjects, the $searchable_fields array will be used to decide which fields are searched.

### Example setup

```
class Test extends DataObject implements Linkable {
	
	private static $db = array(
		'Name' => 'Text',
		'Author' => 'Varchar(150)'
	);

	public static $searchable_fields = array(
		'Name',
		'Author'
	);

	public function Link() {
		return $this->ID;
	}

	public function LinkTitle() {
		return $this->Name . ' - ' . $this->Author;
	}
}
```

Once the DataObject has been set up to properly implement the Linkable interface, you need to the following line to your mysite/_config.php file:

HtmlEditorField_LinkObjects::addLinkableObject('Test');

Your DataObject will then be added to the HTML Editor Field links section.

## Feedback

Feel free to make this module better by submitting feedback, changes, suggestions etc!